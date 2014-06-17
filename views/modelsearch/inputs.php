<?php
$class = $value['model'];
if (!empty($class)) {
    $title = $class::title_property();
}
$class_id = $value['id'];
?>
<div id="<?= $id ?>" class="modelsearch">
    <div class="ms-select">
        <label>
            <?= $label ?>
        </label>
        <select name="<?= $options['names']['model'] ?>">
            <option value="" <?= empty($class) ? '' : '' ?>><?= __('None') ?></option>
            <?php
            foreach ($options['models'] as $model => $label) {
                ?>
            <option value="<?= $model ?>" <?= ($class == $model) ? 'selected="selected"' : '' ?>><?= $label ?></option>
                <?php
            }
            ?>
        </select>
    </div>
    <div class="ms-input">
        <label>
            <?= __('Content title') ?>
        </label>
        <input type="hidden" name="<?= $options['names']['id'] ?>" value="<?= !empty($class_id) ? $class_id : 0 ?>"/>
        <?php
        echo \Novius\Renderers\Renderer_Autocomplete::renderer(array(
            'name' => 'search[]',//do not assume this will be the only one
            'value' => (!empty($title) && !empty($class_id)) ? $class::find($class_id)->{$title} : '',
            'placeholder' => __('Choose "empty" value above to remove a possibly registered value'),
            'renderer_options' => array(
                'data' => array(
                    'data-autocomplete-url' => 'admin/novius_renderers/modelsearch/search',
                    'data-autocomplete-callback' => 'click_modelsearch',
                    'data-autocomplete-post' => \Format::forge(array('model' => $class))->to_json(),
                ),
                //do not use a wrapper to allow using multiple modelsearch and including only one script
            ),
        ));
        ?>
    </div>
</div>