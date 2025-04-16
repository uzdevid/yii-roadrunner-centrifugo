<?php declare(strict_types=1);

namespace UzDevid\Yii\Runner\Centrifugo;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RoadRunner\Centrifugo\Request\RequestType;
use UzDevid\Yii\Runner\Centrifugo\Exception\HandlerNotFoundException;
use UzDevid\Yii\Runner\Centrifugo\Handler\ConnectHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\HandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\InvalidRequestHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\PublishHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\RefreshHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\RpcHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\SubRefreshHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\SubscribeHandlerInterface;

final class Application {
    /**
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * @param RequestType $requestType
     * @return HandlerInterface
     * @throws ContainerExceptionInterface
     * @throws HandlerNotFoundException
     */
    public function getServiceHandler(RequestType $requestType): HandlerInterface {
        $handlerInterfaceClass = match ($requestType) {
            RequestType::Connect => ConnectHandlerInterface::class,
            RequestType::Refresh => RefreshHandlerInterface::class,
            RequestType::Invalid => InvalidRequestHandlerInterface::class,
            RequestType::Subscribe => SubscribeHandlerInterface::class,
            RequestType::SubRefresh => SubRefreshHandlerInterface::class,
            RequestType::Publish => PublishHandlerInterface::class,
            RequestType::RPC => RpcHandlerInterface::class,
        };

        try {
            return $this->container->get($handlerInterfaceClass);
        } catch (NotFoundExceptionInterface $e) {
            throw new HandlerNotFoundException(sprintf('Handler %s not found for handling %s request', $handlerInterfaceClass, $requestType->value), $e->getCode(), $e);
        }
    }
}
