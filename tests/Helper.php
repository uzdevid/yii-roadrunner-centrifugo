<?php declare(strict_types=1);

namespace UzDevid\Yii\Runner\Centrifugo\Tests;


use UzDevid\Yii\Runner\Centrifugo\Handler\ConnectHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\InvalidRequestHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\PublishHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\RefreshHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\RpcHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\SubRefreshHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\SubscribeHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo\ConnectHandler;
use UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo\InvalidRequestHandler;
use UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo\PublishHandler;
use UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo\RefreshHandler;
use UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo\RpcHandler;
use UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo\SubRefreshHandler;
use UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo\SubscribeHandler;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

class Helper {
    /**
     * @throws InvalidConfigException
     */
    public static function createContainer(): Container {
        $config = ContainerConfig::create()->withDefinitions([
            ConnectHandlerInterface::class => ConnectHandler::class,
            RefreshHandlerInterface::class => RefreshHandler::class,
            InvalidRequestHandlerInterface::class => InvalidRequestHandler::class,
            SubscribeHandlerInterface::class => SubscribeHandler::class,
            SubRefreshHandlerInterface::class => SubRefreshHandler::class,
            PublishHandlerInterface::class => PublishHandler::class,
            RpcHandlerInterface::class => RpcHandler::class
        ]);

        return new Container($config);
    }
}
