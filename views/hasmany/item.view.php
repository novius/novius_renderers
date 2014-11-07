<?php
\Nos\I18n::current_dictionary('novius_renderers::default');
?>
<div class="hasmany_item" data-item-index="<?= $index ?>">
<?php
/**
 * @var $fieldset Fieldset
 */
echo $fieldset->build_hidden_fields();
?>
    <span
        class="hasmany_icon hasmany_delete_item"
        data-question="<?= __('Are you sure you want to delete this item?') ?>"
        data-removed="<?= empty($is_new) ? __('This item will be deleted when the form is saved') : '' ?>"
        >
    </span>
<?php
if (!empty($options['order'])) {
?>
    <span class="hasmany_icon hasmany_icon_arrow item-up-js"></span>
    <span class="hasmany_icon hasmany_icon_arrow item-down-js"></span>
<?php
}
?>
    <table>
<?php
foreach ($fields as $field) {
    echo $field->build();
}
?>
    </table>
    <button class="dupli-item-js button-dupli-item" data-order="<?= (!empty($options['order']) ? 1 : 0) ?>"><?= __('Duplicate this item') ?></button>
<?php
echo $fieldset->build_append();
?>
</div>