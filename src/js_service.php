
export class <?= $name ?> {
    /** @type Api */
    transport;

    /** @param Api transport */
    constructor(transport) {
        this.transport = transport;
    }
<?php foreach ($methods as ['name' => $name, 'input_type' => $input_type, 'output_type' => $output_type]):?>

    /** @param <?= $input_type ?> request */
    /** @param <?= $output_type ?> response */
    <?= $name ?>(request, response) {
        return this.transport.call('<?= $name ?>', request, response);
    }
<?php endforeach ?>
};
