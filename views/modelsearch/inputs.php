<?php
$current_model = \Arr::get($value, 'model');
$current_model_id = \Arr::get($value, 'id');
$external = \Arr::get($value, 'external');
$classAutocomplete = !empty($$current_model) ? '' : 'ms-hidden';
$classExternal = !empty($$current_model) ? 'ms-hidden' : '';

$current_model_title = '';
if (!empty($current_model) && !empty($current_model_id)) {
    $item = $current_model::find($current_model_id);
    if (!empty($item)) {
        $current_model_title = $item->title_item();
    }
}

// Get available models
$available_models = \Arr::get($options, 'models');
if (!count($available_models)) {
    return ;
}

?>
<div id="<?= $id ?>" class="modelsearch">
    <?php if (count($available_models) > 1) { ?>
        <div class="ms-select">
            <label><?= $label ?></label>
            <select name="<?= \Arr::get($options, 'names.model') ?>">
                <option value=""><?= __('None') ?></option>
                <?php foreach ($available_models as $model => $label) { ?>
                    <option value="<?= $model ?>" <?= ($model == $current_model) ? 'selected="selected"' : '' ?>><?= $label ?></option>
                <?php } ?>
            </select>
        </div>
    <?php } else { ?>
        <input type="hidden" name="<?= \Arr::get($options, 'names.model') ?>" value="<?= key($models) ?>" />
    <?php } ?>
    <div class="ms-value ms-autocomplete <?=$classAutocomplete?>">
        <label>
            <?= __('Content title') ?>
        </label>
        <div class="autocomplete-container">
        <input type="hidden" name="<?= $options['names']['id'] ?>" value="<?= !empty($current_model_id) ? $current_model_id : 0 ?>"/>
        <?= \Novius\Renderers\Renderer_Autocomplete::renderer(array(
            'name' => 'search[]',//do not assume this will be the only one
            'value' => $current_model_title,
            'placeholder' => __('Choose "empty" value above to remove a possibly registered value'),
            'renderer_options' => array(
                'data' => array(
                    'data-autocomplete-cache' => 'false',
                    'data-autocomplete-minlength' => intval(\Arr::get($options, 'minlength')),
                    'data-autocomplete-url' => 'admin/novius_renderers/modelsearch/search',
                    'data-autocomplete-callback' => 'click_modelsearch',
                    'data-autocomplete-post' => \Format::forge(array(
                            'model' => $current_model,
                            'use_jayps_search' => (bool) \Arr::get($options, 'use_jayps_search', false),
                        ))->to_json(),
                ),
                //do not use a wrapper to allow using multiple modelsearch and including only one script
            ),
        )); ?>
    </div>
    <?php
    if ($options['external'] === true) {
        ?>
        <div class="ms-value <?=$classExternal?> ms-external">
            <label>
                <?= __('External link') ?>
            </label>
            <input class="ms-external" type="text" value="<?=$external?>" name="<?= $options['names']['external'] ?>">
        </div>
    <?php
    }
    ?>
</div>

<style type="text/css">
#<?= $id ?> .ms-input {
    position: relative;
}

#<?= $id ?> .autocomplete-liste {
    width: 100%;
}
</style>
