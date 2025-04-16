<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\Definitions\Reference;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\ErrorHandler\Renderer\PlainTextRenderer;
use Yiisoft\ErrorHandler\ThrowableRendererInterface;
use UzDevid\Yii\Runner\RoadRunner\Centrifugo\Application;
use UzDevid\Yii\Runner\RoadRunner\Centrifugo\Tests\Support\Centrifugo\ConnectService;
use UzDevid\Yii\Runner\RoadRunner\Centrifugo\Tests\Support\Centrifugo\SubscribeService;
use Yiisoft\Test\Support\Log\SimpleLogger;

return [
    LoggerInterface::class => SimpleLogger::class,
    ThrowableRendererInterface::class => PlainTextRenderer::class,

    ErrorCatcher::class => [
        'forceContentType()' => ['text/plain'],
        'withRenderer()' => ['text/plain', PlainTextRenderer::class],
    ],

    ErrorHandler::class => [
        'reset' => function () {
            /** @var ErrorHandler $this */
            $this->debug(false);
        },
    ],

    Application::class => [
        '__construct()' => [
            'connect' => Reference::to(ConnectService::class),
            'subscribe' => Reference::to(SubscribeService::class)
        ],
    ],
];
