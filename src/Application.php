<?php declare(strict_types=1);

namespace Yiisoft\Runner\RoadRunner\Centrifugo;

use RoadRunner\Centrifugo\Request\RequestType;

final class Application {
    public function __construct(
        private readonly ServiceInterface|null $connect = null,
        private readonly ServiceInterface|null $refresh = null,
        private readonly ServiceInterface|null $invalid = null,
        //
        private readonly ServiceInterface|null $subscribe = null,
        private readonly ServiceInterface|null $subRefresh = null,
        private readonly ServiceInterface|null $publish = null,
        private readonly ServiceInterface|null $rpc = null
    ) {
    }

    /**
     * @param RequestType $requestType
     * @return ServiceInterface|null
     */
    public function getServiceHandler(RequestType $requestType): ServiceInterface|null {
        return match ($requestType) {
            RequestType::Connect => $this->connect,
            RequestType::Refresh => $this->refresh,
            RequestType::Invalid => $this->invalid,
            RequestType::Subscribe => $this->subscribe,
            RequestType::SubRefresh => $this->subRefresh,
            RequestType::Publish => $this->publish,
            RequestType::RPC => $this->rpc,
        };
    }
}
