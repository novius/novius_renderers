<?php
$class = $value['model'];
if (!empty($class)) {
    $title = $class::title_property();
}
$class_id = $value['id'];
?>
<div id="<?= $id ?>" class="modelsearch">
    <select name="<?= $options['names']['model'] ?>" style="margin-bottom:10px">
        <option value="" <?= empty($class) ? '' : '' ?>><?= __('None') ?></option>
        <?php
        foreach ($options['models'] as $model => $label) {
            ?>
        <option value="<?= $model ?>" <?= ($class == $model) ? 'selected="selected"' : '' ?>><?= $label ?></option>
            <?php
        }
        ?>
    </select>

    <input type="hidden" name="<?= $options['names']['id'] ?>" value="<?= !empty($class_id) ? $class_id : 0 ?>"/>
    <?php
    echo \Novius\Renderers\Renderer_Autocomplete::renderer(array(
        'name' => 'search',
        'value' => (!empty($title) && !empty($class_id)) ? $class::find($class_id)->{$title} : '',
        'renderer_options' => array(
            'data' => array(
                'data-autocomplete-url' => 'admin/novius_renderers/modelsearch/search',
                'data-autocomplete-callback' => 'click_modelsearch',
                'data-autocomplete-post' => \Format::forge(array('model' => $class))->to_json(),
            ),
            'wrapper' => '#'.$id,
        ),
    ));
    ?>
</div>