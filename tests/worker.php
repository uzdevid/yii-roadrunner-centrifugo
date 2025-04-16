<?php declare(strict_types=1);

use Yiisoft\Runner\RoadRunner\Centrifugo\RoadRunnerCentrifugoApplicationRunner;

ini_set('display_errors', 'stderr');

require_once dirname(__DIR__) . '/vendor/autoload.php';

$application = new RoadRunnerCentrifugoApplicationRunner(
    rootPath: __DIR__ . '/Support',
    debug: true
);

try {
    $application->run();
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/err.txt', $e->getMessage());
}
