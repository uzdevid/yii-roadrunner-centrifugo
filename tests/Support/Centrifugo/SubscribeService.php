<?php
declare(strict_types=1);

namespace Yiisoft\Runner\RoadRunner\Centrifugo\Tests\Support\Centrifugo;

use RoadRunner\Centrifugo\Payload\SubscribeResponse;
use RoadRunner\Centrifugo\Request\RequestInterface;
use Yiisoft\Runner\RoadRunner\Centrifugo\ServiceInterface;

class SubscribeService implements ServiceInterface {

    public function handle(RequestInterface $request): void {
        $request->respond(new SubscribeResponse(['ok' => true]));
    }
}
