<?php

namespace UzDevid\Yii\Runner\Centrifugo\Handler;

use RoadRunner\Centrifugo\Request\RequestInterface;
use UzDevid\Yii\Runner\Centrifugo\Exception\MessageExceptionInterface;

interface HandlerInterface {
    /**
     * @param RequestInterface $request
     * @return void
     * @throws MessageExceptionInterface
     */
    public function handle(RequestInterface $request): void;
}
