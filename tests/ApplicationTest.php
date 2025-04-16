<?php

namespace UzDevid\Yii\Runner\Centrifugo\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RoadRunner\Centrifugo\Request\RequestType;
use UzDevid\Yii\Runner\Centrifugo\Application;
use UzDevid\Yii\Runner\Centrifugo\Exception\HandlerNotFoundException;
use UzDevid\Yii\Runner\Centrifugo\Handler\ConnectHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\InvalidRequestHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\PublishHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\RefreshHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\RpcHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\SubRefreshHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\SubscribeHandlerInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;

class ApplicationTest extends TestCase {
    /**
     * @throws ContainerExceptionInterface
     * @throws HandlerNotFoundException
     * @throws InvalidConfigException
     */
    public function testGetServiceHandler(): void {
        $application = new Application(Helper::createContainer());

        $this->assertInstanceOf(ConnectHandlerInterface::class, $application->getServiceHandler(RequestType::Connect));
        $this->assertInstanceOf(RefreshHandlerInterface::class, $application->getServiceHandler(RequestType::Refresh));
        $this->assertInstanceOf(InvalidRequestHandlerInterface::class, $application->getServiceHandler(RequestType::Invalid));
        $this->assertInstanceOf(SubscribeHandlerInterface::class, $application->getServiceHandler(RequestType::Subscribe));
        $this->assertInstanceOf(SubRefreshHandlerInterface::class, $application->getServiceHandler(RequestType::SubRefresh));
        $this->assertInstanceOf(PublishHandlerInterface::class, $application->getServiceHandler(RequestType::Publish));
        $this->assertInstanceOf(RpcHandlerInterface::class, $application->getServiceHandler(RequestType::RPC));
    }
}
