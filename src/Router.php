<?php

namespace Eelf\WebJson;

class Router {
    public function __invoke($method, $message, $service) {
        $class = (new \ReflectionMethod($service, $method))->getParameters()[0]->getClass()->getName();
        [$request, $err] = $class::fromBytes($message);
        if ($err !== null) {
            throw $err;
        }

        [$response, $err] = $service->$method($request)->toBytes();
        if ($err !== null) {
            throw $err;
        }
        return $response;
    }
}
