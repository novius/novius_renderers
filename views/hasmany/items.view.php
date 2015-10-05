<?php
\Nos\I18n::current_dictionary('novius_renderers::default');
?>
<div class="hasmany_items count-items-js" data-nb-items="<?= empty($item->{$relation}) ? 1 : count($item->{$relation}) ?>">
    <div class="item_list">
        <?php
        $i = 0;
        $listItems = null;
        if (!empty($relation) && !empty($item)) {
            $listItems = $item->{$relation};
        }
        elseif ($value) {
            $listItems = array();
            foreach ($value as $elem) {
                $newModel = $model::forge();
                foreach ($elem as $property => $elemValue) {
                    $newModel->$property = $elemValue;
                }
                $listItems[] = $newModel;
            }
        }

        if (!empty($listItems)) {
            foreach ($listItems as $o) {
                // Build fieldset and return view
                echo \Novius\Renderers\Renderer_HasMany::render_fieldset($o, $relation, $i, $options, $data);
                $i++;
            }
        } elseif (\Arr::get($options, 'default_item', true)) {
            // Display an empty item in case none have already been added
            echo \Novius\Renderers\Renderer_HasMany::render_fieldset($model::forge(), $relation, $i, $options, $data);
        }
        ?>
    </div>

    <?php
    $attr = array(
        'data-icon' => 'plus',
        'data-model' => $model,
        'data-context' => $context,
        'data-relation' => $relation,
        'data-order' => !empty($options['order']) ? 1 : 0,
    );

    if (\Arr::get($options, 'inherit_context', true)) {
        if (method_exists($item, 'behaviours') && ($item::behaviours('Nos\Orm_Behaviour_Contextable') || $item::behaviours('Nos\Orm_Behaviour_Twinnable'))) {
            $attr['data-context'] = $item->get_context();
        }
    }

    $attr = \Arr::merge($attr, $data);
    if (!isset($options['add']) || $options['add']) {
        ?>
    <button class="add-item-js button-add-item" <?= array_to_attr($attr) ?>><?= __('Add one item') ?></button>
    <?php
    }
    ?>
</div>
