<?php
\Nos\I18n::current_dictionary('novius_renderers::default');
?>
<div class="hasmany_items count-items-js" data-nb-items="<?= count($default_items) ?>">
    <div class="item_list">
        <?php
        $i = 0;
        if (!empty($default_items)) {
            foreach ($default_items as $default_item) {
                // Build fieldset and return view
                echo \Novius\Renderers\Renderer_HasMany::render_fieldset($default_item, $relation, $i, $options, $data);
                $i++;
            }
        }
        ?>
    </div>

    <?php
    $attr = array(
        'data-icon' => 'plus',
        'data-model' => $model,
        'data-context' => \Arr::get($options, 'context'),
        'data-relation' => $relation,
        'data-view'    => \Arr::get($options, 'content_view'),
        'data-order' => !empty($options['order']) ? 1 : 0,
    );

    if (!isset($options['add']) || $options['add']) {
        ?>
        <button class="add-item-js button-add-item" <?= array_to_attr(\Arr::merge($attr, $data)) ?>><?= __('Add one item') ?></button>
        <?php
    }
    ?>
</div>
