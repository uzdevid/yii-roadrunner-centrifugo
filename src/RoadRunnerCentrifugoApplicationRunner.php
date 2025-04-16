<?php declare(strict_types=1);

namespace Yiisoft\Runner\RoadRunner\Centrifugo;

use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RoadRunner\Centrifugo\CentrifugoWorker;
use RoadRunner\Centrifugo\Request\RequestFactory;
use RoadRunner\Centrifugo\Request\RequestType;
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\WorkerInterface;
use Throwable;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Di\StateResetter;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Exception\ErrorException;
use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Yii\Runner\ApplicationRunner;

class RoadRunnerCentrifugoApplicationRunner extends ApplicationRunner {
    private ErrorHandler|null $temporaryErrorHandler = null;
    private Worker $worker;
    private RequestFactory $requestFactory;

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
        string  $rootPath,
        bool    $debug = false,
        bool    $checkEvents = false,
        ?string $environment = null,
        string  $bootstrapGroup = 'bootstrap-centrifugo',
        string  $eventsGroup = 'events-centrifugo',
        string  $diGroup = 'di-centrifugo',
        string  $diProvidersGroup = 'di-providers-centrifugo',
        string  $diDelegatesGroup = 'di-delegates-centrifugo',
        string  $diTagsGroup = 'di-tags-centrifugo',
        string  $paramsGroup = 'params-centrifugo',
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

    /**
     * Returns a new instance with the specified temporary error handler instance {@see ErrorHandler}.
     *
     * A temporary error handler is needed to handle the creation of configuration and container instances,
     * then the error handler configured in your application configuration will be used.
     *
     * @param ErrorHandler $temporaryErrorHandler The temporary error handler instance.
     */
    public function withTemporaryErrorHandler(ErrorHandler $temporaryErrorHandler): self {
        $new = clone $this;
        $new->temporaryErrorHandler = $temporaryErrorHandler;
        return $new;
    }

    /**
     * @param Worker $worker
     * @return $this
     */
    public function withWorker(Worker $worker): self {
        $new = clone $this;
        $new->worker = $worker;
        return $new;
    }

    /**
     * @param RequestFactory $requestFactory
     * @return $this
     */
    public function withRequestFactory(RequestFactory $requestFactory): self {
        $new = clone $this;
        $new->requestFactory = $requestFactory;
        return $new;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotFoundExceptionInterface
     * @throws \ErrorException
     * @throws JsonException
     */
    public function run(): void {
        // Register temporary error handler to catch error while container is building.
        $temporaryErrorHandler = $this->createTemporaryErrorHandler();
        $this->registerErrorHandler($temporaryErrorHandler);

        $container = $this->getContainer();

        // Register error handler with real container-configured dependencies.
        /** @var ErrorHandler $actualErrorHandler */
        $actualErrorHandler = $container->get(ErrorHandler::class);
        $this->registerErrorHandler($actualErrorHandler, $temporaryErrorHandler);

        $this->runBootstrap();
        $this->checkEvents();

        $this->runWorker($container, $temporaryErrorHandler);
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

    private function createTemporaryErrorHandler(): ErrorHandler {
        if ($this->temporaryErrorHandler !== null) {
            return $this->temporaryErrorHandler;
        }

        $logger = new Logger([new FileTarget("$this->rootPath/runtime/logs/centrifugo.log")]);
        return new ErrorHandler($logger, new HtmlRenderer());
    }

    /**
     * @param LoggerInterface $logger
     * @return Worker
     */
    private function createWorker(LoggerInterface $logger): Worker {
        return $this->worker ?? Worker::create(logger: $logger);
    }

    /**
     * @param WorkerInterface $worker
     * @return RequestFactory
     */
    private function createRequestFactory(WorkerInterface $worker): RequestFactory {
        return $this->requestFactory ?? new RequestFactory($worker);
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
    public function runWorker(ContainerInterface $container, ErrorHandler $errorHandler): void {
        /** @var Application $application */
        $application = $container->get(Application::class);

        $worker = $this->createWorker($container->get(LoggerInterface::class));
        $centrifugoWorker = new CentrifugoWorker($worker, $this->createRequestFactory($worker));

        while (true) {
            $request = $centrifugoWorker->waitRequest();

            if ($request === null) {
                break;
            }

            $requestType = RequestType::createFrom($request);

            /** @var ServiceInterface|null $service */
            $service = $application->getServiceHandler($requestType);

            if ($service === null) {
                $request->error(100, 'Handler for this message not implemented');
                continue;
            }

            try {
                $service->handle($request);
            } catch (Throwable $e) {
                $request->error((int) $e->getCode(), (string) $errorHandler->handle($e));
            }

            $this->afterRespond($container);
        }
    }
}
