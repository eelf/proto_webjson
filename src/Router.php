<?php

namespace Eelf\WebJson;

class Router {
    private function jsonToMessage($json, \Eelf\Protobuf\Message $message) {
        foreach ($message::desc() as $tag => ['name' => $name, 'type' => $type, 'repeated' => $repeated]) {
            if (!array_key_exists($name, $json)) continue;

            if ($repeated) {
                if (!is_array($json[$name])) throw new \Exception('repeated expected got ' . gettype($json[$name]));
                foreach ($json[$name] as $item) {
                    if (is_string($type)) {
                        $value = new $type;
                        $this->jsonToMessage($item, $value);
                    } else {
                        $value = $item;
                    }
                    $message->fields[$name][] = $value;
                }
                continue;
            }

            if (is_string($type)) {
                $value = new $type;
                $this->jsonToMessage($json[$name], $value);
            } else {
                $value = $json[$name];
            }
            $message->fields[$name] = $value;
        }
    }
    private function messageToJson(\Eelf\Protobuf\Message $message) : array {
        $res = [];
        foreach ($message::desc() as $tag => ['name' => $name, 'type' => $type, 'repeated' => $repeated]) {
            if (!array_key_exists($name, $message->fields)) continue;
            if (is_string($type)) {
                if ($repeated) {
                    $value = array_map([$this, 'messageToJson'], $message->fields[$name]);
                } else {
                    $value = $this->messageToJson($message->fields[$name]);
                }
            } else {
                $value = $message->fields[$name];
            }
            $res[$name] = $value;
        }
        return $res;
    }
    public function __invoke($body, $service) {
        [$method, $message] = explode(' ', $body, 2);
        $m = json_decode($message, true);
        $Rm = new \ReflectionMethod($service, $method);
        $Rp = $Rm->getParameters()[0];
        $class = $Rp->getClass()->getName();
        $request = new $class;

        try {
            $this->jsonToMessage($m, $request);

            $response = $service->$method($request);

            $json = $this->messageToJson($response);
        } catch (\Throwable $e) {
            return "FAIL $e";
        }
        return 'OK ' . json_encode($json);
    }
}
