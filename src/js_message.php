
export default class <?= $class ?> {
    static ds = <?= $tags ?>;
    static tag_by_name = <?= $tag_by_name ?>;
    fields = {};

<?php foreach ($methods as ['php_name' => $php_name, 'name' => $name]):?>
    has<?= $php_name ?>() {
        return this.fields.hasOwnProperty('<?= $name ?>');
    }

    get<?= $php_name ?>() {
        return this.fields['<?= $name ?>'];
    }

    set<?= $php_name ?>(value) {
        this.fields['<?= $name ?>'] = value;
        return this;
    }

    append<?= $php_name ?>(value) {
        this.fields['<?= $name ?>'].push(value);
        return this;
    }
<?php endforeach ?>
};
