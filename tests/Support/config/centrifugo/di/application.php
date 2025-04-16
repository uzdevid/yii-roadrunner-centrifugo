<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\ErrorHandler\Renderer\PlainTextRenderer;
use Yiisoft\ErrorHandler\ThrowableRendererInterface;
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
];
