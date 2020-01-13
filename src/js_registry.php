class Proto {
    static messages = {};
    static services = {};
}

<?php foreach ($types as [
   'ns_class_underscore' => $ns_class_underscore,
   'package' => $package,
   'name' => $name,
   'path' => $path,
]): ?>
import <?= $ns_class_underscore ?> from './<?= $path ?>';
Proto.messages['<?= $package ?>.<?= $name ?>'] = <?= $ns_class_underscore ?>;

<?php endforeach ?>
<?php foreach ($services as [
    'ns_class_underscore' => $ns_class_underscore,
    'package' => $package,
    'name' => $name,
    'path' => $path,
]): ?>
import <?= $ns_class_underscore ?> from './<?= $path ?>';
<?= $ns_class_underscore ?>.prototype.proto = Proto;
Proto.services['<?= $package ?>.<?= $name ?>'] = <?= $ns_class_underscore ?>;
<?php endforeach ?>

export default Proto;
