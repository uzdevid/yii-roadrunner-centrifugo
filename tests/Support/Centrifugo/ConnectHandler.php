<?php declare(strict_types=1);

namespace UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo;

use RoadRunner\Centrifugo\Payload\ConnectResponse;
use RoadRunner\Centrifugo\Request\RequestInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\ConnectHandlerInterface;

class ConnectHandler implements ConnectHandlerInterface {
    public function handle(RequestInterface $request): void {
        $request->respond(new ConnectResponse('diko', null, ['ok' => true]));
    }
}
