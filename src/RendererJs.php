<?php

namespace Eelf\WebJson;

class RendererJs {
    private function mapField($f) {
        /** @var $f \google\protobuf\FieldDescriptorProto */
        return [
            'number' => $f->getNumber(),
            'repeated' => $f->getLabel() == 3 ? true : false,
            'type' => $f->getType() == 11 ? trim($f->getTypeName(), '.') : (int)$f->getType(),
            'name' => $f->getName(),
        ];
    }

    private function getMessages(string $package, array $messages) {
        foreach ($messages as $m) {
            /** @var $m \google\protobuf\DescriptorProto */
            yield [
                'package' => $package,
                'name' => $m->getName(),
                'fields' => $m->hasField() ? array_map([$this, 'mapField'], $m->getField()) : [],
            ];
            if ($m->hasNestedType()) {
                yield from $this->getMessages($package . '.' . $m->getName(), $m->getNestedType());
            }
        }
    }

    private static function generateClass($package, $name, $fields) {
        $tags = $tag_by_name = $methods = [];
        foreach ($fields as $num => $tag) {
            $tag_by_name[$tag['name']] = $tag['number'];

            $type = explode('.', $tag['type']);
            $tags[$tag['number']] = [
                'repeated' => $tag['repeated'],
                'type' => count($type) == 1 ? (int)$type[0] : implode('.', $type),
                'name' => $tag['name'],
            ];
            $methods[] = [
                'name' => $tag['name'],
                'php_name' => \Eelf\Protobuf\Util::protoToPhpName($tag['name']),
            ];
        }

        return \Eelf\Protobuf\Util::render(__DIR__ . '/js_message.php', [
            'ns' => $package,
            'class' => $name,
            'tags' => json_encode($tags),
            'tag_by_name' => json_encode($tag_by_name),
            'methods' => $methods,
        ]);
    }

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

    private static function generateProtoRegistry($types, $services)
    {
        return \Eelf\Protobuf\Util::render(
            __DIR__ . '/js_registry.php',
            ['types' => $types, 'services' => $services]
        );
    }

    public function __invoke(\google\protobuf\compiler\CodeGeneratorRequest $cgr, \google\protobuf\compiler\CodeGeneratorResponse $resp) {
        \Eelf\Protobuf\Util::ensure_extensions(['json']);

        $its = array_map(
            function ($proto_file) {
                /** @var $proto_file \google\protobuf\FileDescriptorProto */
                return $this->getMessages($proto_file->hasPackage() ? $proto_file->getPackage() : '', $proto_file->getMessageType());
            },
            $cgr->getProtoFile()
        );
        $types = [];
        foreach (\Eelf\Protobuf\Util::iterateGenerators($its) as ['package' => $package, 'name' => $name, 'fields' => $fields]) {
            $file_name = \Eelf\Protobuf\Util::protoToPath($package) . '/' . $name . '.js';

            $types[] = ['package' => $package, 'name' => $name, 'path' => $file_name, 'ns_class_underscore' => str_replace('.', '_', "$package.$name")];

            $content = self::generateClass($package, $name, $fields);

            $file = new \google\protobuf\compiler\CodeGeneratorResponse\File;
            $file->setName($file_name);
            $file->setContent($content);

            $resp->appendFile($file);
        }


        $services = [];
        foreach ($cgr->getProtoFile() as $proto_file) {
            /** @var \google\protobuf\FileDescriptorProto $proto_file */

            if (!$proto_file->hasService()) continue;

            foreach ($proto_file->getService() as $service) {
                /** @var \google\protobuf\ServiceDescriptorProto $service */

                $file_name = \Eelf\Protobuf\Util::protoToPath($proto_file->getPackage()) . '/' . $service->getName() . '.js';

                $services[] = ['package' => $proto_file->getPackage(), 'name' => $service->getName(), 'path' => $file_name,
                    'ns_class_underscore' => str_replace('.', '_', $proto_file->getPackage() . '.' . $service->getName())];

                $content = self::generateService($proto_file->getPackage(), $service->getName(), $service->getMethod());

                $file = new \google\protobuf\compiler\CodeGeneratorResponse\File;
                $file->setName($file_name);
                $file->setContent($content);

                $resp->appendFile($file);
            }
        }

        $content = self::generateProtoRegistry($types, $services);

        $file = new \google\protobuf\compiler\CodeGeneratorResponse\File;
        $file->setName('proto.js');
        $file->setContent($content);
        $resp->appendFile($file);
    }
}
