<?php

namespace Yiisoft\Runner\RoadRunner\Centrifugo;

use RoadRunner\Centrifugo\Request\RequestInterface;
use Throwable;

interface ServiceInterface {
    /**
     * @param RequestInterface $request
     * @return void
     * @throws Throwable
     */
    public function handle(RequestInterface $request): void;
}
