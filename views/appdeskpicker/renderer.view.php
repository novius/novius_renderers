<?php
    \Nos\I18n::current_dictionary(array('novius_renderers::appdeskpicker'));
    $id = uniqid('appdeskpicker_');
?>

<div id="<?= $id ?>">
    <?= \Form::hidden($name.'[id]', $item->{$options['field_id']}, array('class' => 'field-id')) ?>
    <?= \Form::hidden($name.'[class]', $item->{$options['field_class']}, array('class' => 'field-class')) ?>
    <div>
        <span class="field-title"><?php
            if (!empty($selectedItem)) {
                echo $selectedTypeConfig['label'].' &gt; '.$selectedItem->title_item();
            } else {
                echo __('Nothing selected yet');
            }
        ?></span>
        <button
            type="button"
            class="deselect-item"
            style="margin-left: 1rem; <?= empty($selectedItem) ? 'display: none;' : '' ?>"
        ><?= __('Deselect') ?></button>
    </div>
    <button
        type="button"
        class="select-item"
    ><?= __('Select an item') ?></button>
</div>

<script type="text/javascript">
    require([
        'jquery-nos'
    ], function ($) {
        $(function () {
            var models = <?= json_encode($options['models']) ?>;
            var $container = $('#<?= $id ?>');

            $container.find('.select-item').click(function() {
                var $dialog = $container.nosDialog({
                    contentUrl: 'admin/novius_renderers/appdeskpicker/main?models=<?= urlencode(\Crypt::encode(json_encode($options['models']))) ?>',
                    title: <?= json_encode(__('Select an item')) ?>,
                    ajax: true,
                });

                $dialog.on('appdeskpicker-item-picked', function (event, item) {
                    var type = models.find(function (type) {
                        return type.model == item._model;
                    });
                    $container.find('.field-id').val(item._id);
                    $container.find('.field-class').val(item._model);
                    $container.find('.field-title').text(type.label + ' > ' + item._title);
                    $container.find('.deselect-item').show();
                    $dialog.nosDialog('close');
                });
            });

            $container.find('.deselect-item').click(function() {
                $container.find('.field-id').val(null);
                $container.find('.field-class').val(null);
                $container.find('.field-title').text(<?= json_encode(__('Nothing selected yet')) ?>);
                $container.find('.deselect-item').hide();
            });
        });
    });
</script>

