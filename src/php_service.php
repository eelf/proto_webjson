<?= $ns ?>

interface <?= $name ?> {
<?php
foreach ($methods as ['name' => $name, 'input_type' => $input_type, 'output_type' => $output_type]):?>
    public function <?= $name ?>(<?= $input_type ?> $request) : <?= $output_type ?>;

<?php
endforeach ?>
}
