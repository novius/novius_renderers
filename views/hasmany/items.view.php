<?php
\Nos\I18n::current_dictionary('novius_renderers::default');
?>
<div class="count-items-js" data-nb-items="<?= empty($item->{$relation}) ? 1 : count($item->{$relation}) ?>">
    <div class="item_list">
        <?php
        $i = 1;
        if (!empty($item->{$relation})) {
            foreach ($item->{$relation} as $o) {
                //Build fieldset and return view
                echo \Novius\Renderers\Renderer_HasMany::render_fieldset($o, $relation, $i);
                $i++;
            }
        } else {
            //Display an empty item in case none have already been added
            echo \Novius\Renderers\Renderer_HasMany::render_fieldset($model::forge(), $relation, $i);
        }
        ?>
    </div>
    <button class="add-item-js button-add-item" data-model="<?= $model ?>" data-relation="<?= $relation ?>"><?= __('Add one item') ?></button>
</div>

