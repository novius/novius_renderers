<?php
$sortable = \Arr::get($options, 'sortable', false);
$class = "";

$divClass = "multimedias";
if ($sortable) {
    $class = "ui-state-default sortable";
    $divClass .= " multimedias-sortable";
}
?>
<div id="<?= $id ?>" class="<?= $divClass ?>" <?= !empty($sortable) ? 'data-sortable="1"' : '' ?>>
    <ul class="wrapper_elements" data-addclass="<?= $class ?>">

        <?php
        $index = 1;
        $value = (array)$value;
        foreach ($value as $media_id) {
            ?>
            <?php
            if (!empty($media_id)) {
                ?>

                <li class="<?= $class ?>">

                    <?php
                    echo \Nos\Media\Renderer_Media::renderer(
                        array(
                            'name'             => $key . '[' . $index . ']',
                            'value'            => $media_id,
                            'required'         => false,
                            'renderer_options' => $options,
                        )
                    );
                    ?>

                </li>
                <?php
                $index++;
            }
            ?>
        <?php
        }
        ?>
    </ul>
    <span class="picker-container <?= $class ?>">
    <?php
    //if limit = 0 OR false, or if number of medias < limit, an additional media can be add
    if ((!$options['limit']) || $index <= $options['limit']) {
        echo \Nos\Media\Renderer_Media::renderer(
            array(
                'name'             => $key . '[' . $index . ']',
                'value'            => null,
                'required'         => false,
                'renderer_options' => $options,
            )
        );
        $index++;
    }
    ?>
</span>
    <span data-next="<?= $index ?>" data-key="<?= $key ?>"></span>
</div>