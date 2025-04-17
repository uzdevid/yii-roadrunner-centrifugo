<?php declare(strict_types=1);

namespace UzDevid\Yii\Runner\Centrifugo;

use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RoadRunner\Centrifugo\CentrifugoWorker;
use RoadRunner\Centrifugo\Request\RequestFactory;
use RoadRunner\Centrifugo\Request\RequestType;
use Spiral\RoadRunner\Worker;
use Throwable;
use UzDevid\Yii\Runner\Centrifugo\Exception\HandlerNotFoundException;
use UzDevid\Yii\Runner\Centrifugo\Exception\MessageExceptionInterface;
use Yiisoft\Di\StateResetter;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Exception\ErrorException;
use Yiisoft\Yii\Runner\ApplicationRunner;

class CentrifugoApplicationRunner extends ApplicationRunner {
    /**
     * @param string $rootPath The absolute path to the project root.
     * @param bool $debug Whether the debug mode is enabled.
     * @param bool $checkEvents Whether to check events' configuration.
     * @param string|null $environment The environment name.
     * @param string $bootstrapGroup The bootstrap configuration group name.
     * @param string $eventsGroup The events' configuration group name.
     * @param string $diGroup The container definitions' configuration group name.
     * @param string $diProvidersGroup The container providers' configuration group name.
     * @param string $diDelegatesGroup The container delegates' configuration group name.
     * @param string $diTagsGroup The container tags' configuration group name.
     * @param string $paramsGroup The configuration parameters group name.
     * @param array $nestedParamsGroups Configuration group names that are included into configuration parameters group.
     * This is needed for recursive merging of parameters.
     * @param array $nestedEventsGroups Configuration group names that are included into events' configuration group.
     * This is needed for reverse and recursive merge of events' configurations.
     *
     * @psalm-param list<string> $nestedParamsGroups
     * @psalm-param list<string> $nestedEventsGroups
     */
    public function __construct(
        string                          $rootPath,
        private readonly ErrorHandler   $temporaryErrorHandler,
        private readonly Worker         $worker,
        private readonly RequestFactory $requestFactory,
        bool                            $debug = false,
        bool                            $checkEvents = false,
        string|null                     $environment = null,
        string                          $bootstrapGroup = 'bootstrap-centrifugo',
        string                          $eventsGroup = 'events-centrifugo',
        string                          $diGroup = 'di-centrifugo',
        string                          $diProvidersGroup = 'di-providers-centrifugo',
        string                          $diDelegatesGroup = 'di-delegates-centrifugo',
        string                          $diTagsGroup = 'di-tags-centrifugo',
        string                          $paramsGroup = 'params-centrifugo',
        array   $nestedParamsGroups = ['params'],
        array   $nestedEventsGroups = ['events'],
    ) {
        parent::__construct(
            $rootPath,
            $debug,
            $checkEvents,
            $environment,
            $bootstrapGroup,
            $eventsGroup,
            $diGroup,
            $diProvidersGroup,
            $diDelegatesGroup,
            $diTagsGroup,
            $paramsGroup,
            $nestedParamsGroups,
            $nestedEventsGroups,
        );
    }

    public function run(): void {
        // Register temporary error handler to catch error while container is building.
        try {
            $this->registerErrorHandler($this->temporaryErrorHandler);
        } catch (ErrorException $e) {
            $this->temporaryErrorHandler->handle($e);
        }

        try {
            $container = $this->getContainer();
        } catch (Throwable $e) {
            $this->temporaryErrorHandler->handle($e);
            return;
        }

        // Register error handler with real container-configured dependencies.
        try {
            /** @var ErrorHandler $actualErrorHandler */
            $actualErrorHandler = $container->get(ErrorHandler::class);
        } catch (Throwable $e) {
            $this->temporaryErrorHandler->handle($e);
            return;
        }

        try {
            $this->registerErrorHandler($actualErrorHandler, $this->temporaryErrorHandler);
        } catch (ErrorException $e) {
            $this->temporaryErrorHandler->handle($e);
            return;
        }

        try {
            $this->runBootstrap();
        } catch (Throwable $e) {
            $actualErrorHandler->handle($e);
            return;
        }

        try {
            $this->checkEvents();
        } catch (Throwable $e) {
            $actualErrorHandler->handle($e);
            return;
        }

        try {
            $this->runCentrifugo($container, $actualErrorHandler);
        } catch (Throwable $e) {
            $actualErrorHandler->handle($e);
            return;
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function afterRespond(ContainerInterface $container): void {
        /** @psalm-suppress MixedMethodCall */
        $container->get(StateResetter::class)->reset(); // We should reset the state of such services every request.
        gc_collect_cycles();
    }

    /**
     * @param ErrorHandler $registered
     * @param ErrorHandler|null $unregistered
     * @throws ErrorException
     */
    private function registerErrorHandler(ErrorHandler $registered, ErrorHandler $unregistered = null): void {
        $unregistered?->unregister();

        if ($this->debug) {
            $registered->debug();
        }

        $registered->register();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws JsonException
     */
    private function runCentrifugo(ContainerInterface $container, ErrorHandler $errorHandler): void {
        /** @var Application $application */
        $application = $container->get(Application::class);

        $centrifugoWorker = new CentrifugoWorker($this->worker, $this->requestFactory);

        while (true) {
            $request = $centrifugoWorker->waitRequest();

            if ($request === null) {
                break;
            }

            $requestType = RequestType::createFrom($request);

            try {
                $service = $application->getServiceHandler($requestType);
            } catch (HandlerNotFoundException $e) {
                $request->error(0, (string) $errorHandler->handle($e));
                continue;
            }

            try {
                $service->handle($request);
            } catch (MessageExceptionInterface $e) {
                $errorHandler->handle($e);
                $request->error((int) $e->getCode(), $e->getMessage());
            }

            $this->afterRespond($container);
        }
    }
}
