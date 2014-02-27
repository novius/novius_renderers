<div id="<?= $id ?>">
<?php
$index = 1;
while(!empty($item->medias->{$key.$index})) {
    $media = $item->medias->{$key.$index};
    echo \Nos\Media\Renderer_Media::renderer(
        array(
            'name' => $key.'['.$index.']',
            'value' => isset($media->media_id) && !empty($media->media_id) ? $media->media_id : null,
            'required' => false,
            'renderer_options' => $options,
        )
    );
    $index++;
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