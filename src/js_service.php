export default class {
    static proto;
    /** @type Api */
    transport;
    constructor(transport) {
        this.transport = transport;
    }
<?php foreach ($methods as ['name' => $name, 'input_type' => $input_type, 'output_type' => $output_type]):?>

    /** @param <?= $input_type ?> request */
    <?= $name ?>(request) {
        return this.transport.call('<?= $name ?>', request, this.constructor.prototype.proto.messages['<?= $output_type ?>']);
    }
<?php endforeach ?>
};
