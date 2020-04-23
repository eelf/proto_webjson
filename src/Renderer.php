<?php

namespace Eelf\WebJson;

class Renderer {
    public function __invoke(\google\protobuf\compiler\CodeGeneratorRequest $cgr, \google\protobuf\compiler\CodeGeneratorResponse $resp) {
        foreach ($cgr->getProtoFile() as $proto_file) {
            /** @var \google\protobuf\FileDescriptorProto $proto_file */

            if (!$proto_file->hasService()) continue;

            $ns = \Eelf\Protobuf\Util::protoToPhpclass($proto_file->getPackage());

            foreach ($proto_file->getService() as $service) {
                /** @var \google\protobuf\ServiceDescriptorProto $service */

                $methods = [];
                foreach ($service->getMethod() as $method) {
                    /** @var \google\protobuf\MethodDescriptorProto $method */
                    $input_type = \Eelf\Protobuf\Util::protoToPhpclass($method->getInputType());
                    if (\Eelf\Protobuf\Util::hasPrefix($input_type, $ns)) $input_type = substr($input_type, strlen($ns) + 1);
                    else $input_type = '\\' . $input_type;

                    $output_type = \Eelf\Protobuf\Util::protoToPhpclass($method->getOutputType());
                    if (\Eelf\Protobuf\Util::hasPrefix($output_type, $ns)) $output_type = substr($output_type, strlen($ns) + 1);
                    else $output_type = '\\' . $output_type;

                    $methods[] = [
                        'name' => $method->getName(),
                        'input_type' => $input_type,
                        'output_type' => $output_type,
                    ];
                }

                $filename = \Eelf\Protobuf\Util::protoToPath($proto_file->getPackage()) . '/' . $service->getName() . '.php';
                $content = "<?php\n" . \Eelf\Protobuf\Util::render(
                        __DIR__ . '/php_service.php',
                        [
                            'ns' => $ns ? "namespace $ns;" : '',
                            'name' => $service->getName(),
                            'methods' => $methods,
                        ]
                    );

                $file = new \google\protobuf\compiler\CodeGeneratorResponse\File;
                $file->setName($filename);
                $file->setContent($content);

                $resp->appendFile($file);
            }
        }
    }
}
