<?php declare(strict_types=1);

namespace UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo;

use RoadRunner\Centrifugo\Payload\ConnectResponse;
use RoadRunner\Centrifugo\Request\RequestInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\ConnectHandlerInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\SubRefreshHandlerInterface;

class SubRefreshHandler implements SubRefreshHandlerInterface {
    public function handle(RequestInterface $request): void {

    }
}
