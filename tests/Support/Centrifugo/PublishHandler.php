<?php declare(strict_types=1);

namespace UzDevid\Yii\Runner\Centrifugo\Tests\Support\Centrifugo;

use RoadRunner\Centrifugo\Payload\PublishResponse;
use RoadRunner\Centrifugo\Request\RequestInterface;
use UzDevid\Yii\Runner\Centrifugo\Handler\PublishHandlerInterface;

class PublishHandler implements PublishHandlerInterface {
    public function handle(RequestInterface $request): void {
        $request->respond(new PublishResponse($request->getAttributes()));
    }
}
