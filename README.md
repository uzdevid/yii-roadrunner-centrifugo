<p align="center">
    <a href="https://github.com/uzdevid" target="_blank">
        <img src="https://github.com/user-attachments/assets/e29daa5f-ac8f-47aa-b927-40400a6b5626" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii RoadRunner Centrifugo</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/uzdevid/yii-runner-centrifugo/v)](https://packagist.org/packages/yiisoft/yii-runner-centrifugo)
[![Total Downloads](https://poser.pugx.org/uzdevid/yii-runner-centrifugo/downloads)](https://packagist.org/packages/uzdevid/yii-runner-centrifugo)
[![Build status](https://github.com/uzdevid/yii-runner-centrifugo/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/uzdevid/yii-runner-centrifugo/actions/workflows/build.yml?query=branch%3Amaster)
[![Code Coverage](https://codecov.io/gh/uzdevid/yii-runner-centrifugo/branch/master/graph/badge.svg)](https://codecov.io/gh/uzdevid/yii-runner-centrifugo)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fuzdevid%2Fyii-runner-centrifugo%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/uzdevid/yii-runner-centrifugo/master)
[![Static analysis](https://github.com/uzdevid/yii-runner-centrifugo/actions/workflows/static.yml/badge.svg?branch=master)](https://github.com/uzdevid/yii-runner-centrifugo/actions/workflows/static.yml?query=branch%3Amaster)
[![type-coverage](https://shepherd.dev/github/uzdevid/yii-runner-centrifugo/coverage.svg)](https://shepherd.dev/github/uzdevid/yii-runner-centrifugo)
[![psalm-level](https://shepherd.dev/github/uzdevid/yii-runner-centrifugo/level.svg)](https://shepherd.dev/github/uzdevid/yii-runner-centrifugo)

The package is designed to process web sockets on the basis of [Centrifugo](http://centrifugal.dev/) and [Roadrunner](https://roadrunner.dev/)

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require uzdevid/yii-runner-centrifugo
```

## General usage

### Centrifugo

Download binary [file](https://centrifugal.dev/docs/getting-started/installation)

Generate Centrifugo config file: `config.json`

```bash
./centrifugo genconfig
```

Example config 
```json
{
  "log": {
    "level": "debug"
  },
  "client": {
    "token": {
      "hmac_secret_key": "<secret>"
    },
    "allowed_origins": [
      "*"
    ],
    "proxy": {
      "connect": {
        "enabled": true,
        "endpoint": "grpc://127.0.0.1:30000",
        "timeout": "10s"
      },
      "refresh": {
        "enabled": true,
        "endpoint": "grpc://127.0.0.1:30000",
        "timeout": "3s"
      }
    }
  },
  "channel": {
    "namespaces": [
      {
        "name": "<channel>",
        "allow_subscribe_for_client": true,
        "allow_publish_for_client": true,
        "subscribe_proxy_enabled": true,
        "publish_proxy_enabled": true
      }
    ],
    "proxy": {
      "subscribe": {
        "endpoint": "grpc://127.0.0.1:30000",
        "timeout": "3s"
      },
      "publish": {
        "endpoint": "grpc://127.0.0.1:30000",
        "timeout": "3s"
      },
      "sub_refresh": {
        "endpoint": "grpc://127.0.0.1:30000",
        "timeout": "3s"
      }
    }
  },
  "admin": {
    "enabled": true,
    "password": "<password>",
    "secret": "<secret>"
  },
  "grpc_api": {
    "address": "127.0.0.1",
    "port": 30000
  }
}
```

> We set up the proxy for all requests for our Roadrunner server at `grpc://127.0.0.1:30000`, according to the gRPC protocol

See also: https://centrifugal.dev/docs/server/proxy

---

### RoadRunner

Download roadrunner binary [file](https://docs.roadrunner.dev/docs/general/install)

Road runner example config: `.rr.yaml`
```yaml
version: '3'

rpc:
    listen: tcp://127.0.0.1:6001

server:
    command: "php ./worker.php"
    relay: pipes

centrifuge:
    proxy_address: tcp://127.0.0.1:30000
    grpc_api_address: tcp://127.0.0.1:30000
    use_compressor: true

service:
    centrifuge:
        service_name_in_log: true
        remain_after_exit: true
        restart_sec: 1
        command: "./centrifugo"

```

> We listen to the proxies in the address `tcp://127.0.0.1:30000` from the Centrifugo server

---

###  Runner file

Create `/worker.php` file

```php
use RoadRunner\Centrifugo\Request\RequestFactory;
use Spiral\RoadRunner\Worker;
use UzDevid\Yii\Runner\Centrifugo\CentrifugoApplicationRunner;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Renderer\PlainTextRenderer;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;

ini_set('display_errors', 'stderr');

require_once dirname(__DIR__) . '/vendor/autoload.php';

$logger = new Logger([new FileTarget(sprintf('%s/runtime/logs/centrifugo.log', __DIR__))]);
$errorHandler = new ErrorHandler($logger, new PlainTextRenderer());

$worker = Worker::create(logger: $logger);

$application = new CentrifugoApplicationRunner(
    rootPath: __DIR__,
    temporaryErrorHandler: $errorHandler,
    worker: $worker,
    requestFactory: new RequestFactory($worker),
    debug: true
);

$application->run();
```

### Handlers

Handling connect request and response with specific payload
```php
use UzDevid\Yii\Runner\Centrifugo\Handler\ConnectHandlerInterface;
use RoadRunner\Centrifugo\Payload\ConnectResponse;

class ConnectHandler implements ConnectHandlerInterface {
    public function handle(RequestInterface $request): void {
        // ...
        
        $request->respond(new ConnectResponse('user-id', null, ['ok' => true]))
    }
}
```

In the same way, implement the rest of the handlers:

- `ConnectHandlerInterface` - called when a client connects to Centrifugo, so it's possible to authenticate user, return custom initial data to a client, subscribe connection to server-side channels, attach meta information to the connection, and so on. This proxy hook available for both bidirectional and unidirectional transports.
- `RefreshHandlerInterface` - called when a client session is going to expire, so it's possible to prolong it or just let it expire. Can also be used as a periodical connection liveness callback from Centrifugo to the app backend. Works for bidirectional and unidirectional transports.
- `SubscribeHandlerInterface` - called when clients try to subscribe on a channel, so it's possible to check permissions and return custom initial subscription data. Works for bidirectional transports only.
- `PublishHandlerInterface` - called when a client tries to publish into a channel, so it's possible to check permissions and optionally modify publication data. Works for bidirectional transports only.
- `SubRefreshHandlerInterface` - called when a client subscription is going to expire, so it's possible to prolong it or just let it expire. Can also be used just as a periodical subscription liveness callback from Centrifugo to app backend. Works for bidirectional and unidirectional transports.
- `InvalidRequestInterface` - Handle invalid request
- `RpcHandlerInterface` - called when a client sends RPC, you can do whatever logic you need based on a client-provided RPC method and data. Works for bidirectional transports only (and bidirectional emulation), since data is sent from client to the server in this case.

> See also: https://centrifugal.dev/docs/server/proxy

### Run RoadRunner

```bash
./rr serve
```

You can test the [WebSocketing](https://websocketking.com/) service or use the  [Client SDK](https://centrifugal.dev/docs/transports/client_api)

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place
for that. You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

Maintained by [UzDevid](https://uzdevid.com/).

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
