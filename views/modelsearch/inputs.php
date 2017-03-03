<?php
$current_model = \Arr::get($value, 'model');
$current_model_id = \Arr::get($value, 'id');
$external = \Arr::get($value, 'external');
$classAutocomplete = !empty($current_model) ? '' : 'ms-hidden';
$classExternal = !empty($current_model) ? 'ms-hidden' : '';

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
    return;
}

?>
<div id="<?= $id ?>" class="modelsearch">
    <?php if (count($available_models) > 1): ?>
        <div class="ms-select">
            <label><?= $label ?></label>
            <select name="<?= \Arr::get($options, 'names.model') ?>">
                <?php if ($options['external'] !== true && \Arr::get($options, 'allow_none', true)): ?>
                    <option value=""><?= __('None') ?></option>
                <?php endif ?>
                <?php foreach ($available_models as $model => $label): ?>
                    <?php
                    $selected = ($model == $current_model) ? 'selected="selected"' : '';
                    if (empty($current_model) && $model == '') {
                        $selected = 'selected="selected"';
                    }
                    ?>
                    <option value="<?= $model ?>" <?= $selected ?>><?= $label ?></option>
                <?php endforeach ?>
            </select>
        </div>
    <?php else: ?>
        <input type="hidden" name="<?= \Arr::get($options, 'names.model') ?>" value="<?= key($available_models) ?>"/>
    <?php endif ?>
    <div class="ms-value ms-autocomplete <?= $classAutocomplete ?>">
        <label>
            <?= (count($available_models) > 1) ? __('Content title') : $label ?>
        </label>
        <div class="autocomplete-container">
            <input type="hidden" name="<?= $options['names']['id'] ?>" value="<?= !empty($current_model_id) ? $current_model_id : 0 ?>"/>
            <?= \Novius\Renderers\Renderer_Autocomplete::renderer(array(
                'name' => 'search[]',//do not assume this will be the only one
                'value' => $current_model_title,
                'placeholder' => (count($available_models) > 1) ? __('Choose "empty" value above to remove a possibly registered value') : '',
                'renderer_options' => $options['autocomplete'],
            )) ?>
        </div>
    </div>
    <?php if ($options['external'] === true): ?>
        <div class="ms-value <?= $classExternal ?> ms-external">
            <label>
                <?= __('External link') ?>
            </label>
            <div class="autocomplete-container">
                <input class="external" type="text" value="<?= $external ?>" name="<?= $options['names']['external'] ?>">
            </div>
        </div>
    <?php endif ?>
</div>

<?php
$css = <<<CSS
    #$id .ms-input {
        position: relative;
    }
    
    #$id .autocomplete-liste {
        width: 100%;
    }
CSS;
?>
<style type="text/css">
    <?= $css ?>
</style>
