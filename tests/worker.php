<?php declare(strict_types=1);

use RoadRunner\Centrifugo\Request\RequestFactory;
use Spiral\RoadRunner\Worker;
use UzDevid\Yii\Runner\Centrifugo\CentrifugoApplicationRunner;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Renderer\PlainTextRenderer;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;

ini_set('display_errors', 'stderr');

require_once dirname(__DIR__) . '/vendor/autoload.php';

$rootPath = __DIR__ . '/Support';

$logger = new Logger([new FileTarget(sprintf('%s/runtime/logs/centrifugo.log', $rootPath))]);
$errorHandler = new ErrorHandler($logger, new PlainTextRenderer());

$worker = Worker::create(logger: $logger);

$application = new CentrifugoApplicationRunner(
    rootPath: $rootPath,
    temporaryErrorHandler: $errorHandler,
    worker: $worker,
    requestFactory: new RequestFactory($worker),
    debug: true
);

$application->run();
