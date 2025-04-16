<?php declare(strict_types=1);

namespace Yiisoft\Runner\RoadRunner\Centrifugo\Tests\Support\Centrifugo;

use RoadRunner\Centrifugo\Payload\ConnectResponse;
use RoadRunner\Centrifugo\Request\RequestInterface;
use Yiisoft\Runner\RoadRunner\Centrifugo\ServiceInterface;

class ConnectService implements ServiceInterface {
    public function handle(RequestInterface $request): void {
        $request->respond(new ConnectResponse('diko', null, ['ok' => true]));
    }
}
