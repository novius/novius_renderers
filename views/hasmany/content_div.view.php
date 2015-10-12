<div class="hasmany_content_div">
    <?php
    foreach ($fields as $field) {
        $tpl = $field->template;
        if (empty($tpl)) {
            $field->set_template('<div class="hasmany_content_div_field">{label} {field}</div>');
        }
        echo $field->build();
    }
    ?>
</div>
