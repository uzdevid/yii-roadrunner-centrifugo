<?php declare(strict_types=1);

namespace UzDevid\Yii\Runner\Centrifugo\Exception;

use Throwable;

interface MessageExceptionInterface extends Throwable {
    /**
     * @inheritDoc
     * @psalm-external-mutation-free
     */
    public function getCode(): int;
}
