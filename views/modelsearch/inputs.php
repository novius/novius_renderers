<?php
$class = \Arr::get($value, 'model');
$class_id = \Arr::get($value, 'id');

$title = '';
if (!empty($class) && !empty($class_id)) {
    $title = $class::find($class_id)->title_item();
}

?>
<div id="<?= $id ?>" class="modelsearch">
    <?php
    $models = \Arr::get($options, 'models');
    if (count($models) > 1) {
        ?>
        <div class="ms-select">
            <label><?= $label ?></label>
            <select name="<?= \Arr::get($options, 'names.model') ?>">
                <?php foreach (\Arr::merge(array('' => __('None')), $models) as $model => $label) { ?>
                    <option value="<?= $model ?>" <?= ($class == $model) ? 'selected="selected"' : '' ?>><?= $label ?></option>
                <?php } ?>
            </select>
        </div>
    <?php } else { ?>
        <input type="hidden" name="<?= \Arr::get($options, 'names.model') ?>" value="<?= reset($models) ?>" />
    <?php } ?>
    <div class="ms-value">
        <label>
            <?= __('Content title') ?>
        </label>
        <input type="hidden" name="<?= $options['names']['id'] ?>" value="<?= !empty($class_id) ? $class_id : 0 ?>"/>
        <?= \Novius\Renderers\Renderer_Autocomplete::renderer(array(
            'name' => 'search[]',//do not assume this will be the only one
            'value' => $title,
            'placeholder' => __('Choose "empty" value above to remove a possibly registered value'),
            'renderer_options' => array(
                'data' => array(
                    'data-autocomplete-cache' => 'false',
                    'data-autocomplete-minlength' => intval(\Arr::get($options, 'minlength')),
                    'data-autocomplete-url' => 'admin/novius_renderers/modelsearch/search',
                    'data-autocomplete-callback' => 'click_modelsearch',
                    'data-autocomplete-post' => \Format::forge(array('model' => $class))->to_json(),
                ),
                //do not use a wrapper to allow using multiple modelsearch and including only one script
            ),
        )); ?>
    </div>
</div>

<style type="text/css">
#<?= $id ?> .ms-input {
    position: relative;
}

#<?= $id ?> .autocomplete-liste {
    width: 100%;
}
</style>
