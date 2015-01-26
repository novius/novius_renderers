<div id="<?= $id ?>">
<?php
$index = 1;
$value = (array) $value;
foreach ($value as $media_id) {
    if (!empty($media_id)) {
        echo \Nos\Media\Renderer_Media::renderer(
            array(
                'name' => $key.'['.$index.']',
                'value' => $media_id,
                'required' => false,
                'renderer_options' => $options,
            )
        );
        $index++;
    }
}
//if limit = 0 OR false, or if number of medias < limit, an additional media can be add
if ((!$options['limit']) || $index <= $options['limit']) {
    echo \Nos\Media\Renderer_Media::renderer(
        array(
            'name' => $key.'['.$index.']',
            'value' => null,
            'required' => false,
            'renderer_options' => $options,
        )
    );
    $index++;
}
?>
    <span data-next="<?= $index ?>" data-key="<?= $key ?>"></span>
</div>