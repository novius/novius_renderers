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
    <span class="hasmany_icon hasmany_icon_arrow qa-up-js"></span>
    <span class="hasmany_icon hasmany_icon_arrow qa-down-js"></span>
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
</div>