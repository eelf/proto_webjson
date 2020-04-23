<?php

namespace Eelf\WebJson;

class RendererJs {
    private static function generateService($package, $name, $methods) {
        $methods = array_map(function ($method) {
            /** @var \google\protobuf\MethodDescriptorProto $method */
            return [
                'name' => $method->getName(),
                'input_type' => trim($method->getInputType(), '.'),
                'output_type' => trim($method->getOutputType(), '.'),
            ];
        }, $methods);

        return \Eelf\Protobuf\Util::render(
            __DIR__ . '/js_service.php',
            [
                'ns' => $package,
                'name' => $name,
                'methods' => $methods,
            ]
        );
    }

    public function __invoke(\google\protobuf\compiler\CodeGeneratorRequest $cgr, \google\protobuf\compiler\CodeGeneratorResponse $resp) {
        $services = [];
        foreach ($cgr->getProtoFile() as $proto_file) {
            /** @var \google\protobuf\FileDescriptorProto $proto_file */

            if (!$proto_file->hasService()) continue;

            $file_name = pathinfo($proto_file->getName(), PATHINFO_FILENAME) . '_grpc_pb.js';
            $content = '';

            foreach ($proto_file->getService() as $service) {
                /** @var \google\protobuf\ServiceDescriptorProto $service */

                $services[] = ['package' => $proto_file->getPackage(), 'name' => $service->getName(), 'path' => $file_name,
                    'ns_class_underscore' => str_replace('.', '_', $proto_file->getPackage() . '.' . $service->getName())];

                $content .= self::generateService($proto_file->getPackage(), $service->getName(), $service->getMethod());

            }

            $file = new \google\protobuf\compiler\CodeGeneratorResponse\File;
            $file->setName($file_name);
            $file->setContent($content);

            $resp->appendFile($file);
        }
    }
}
