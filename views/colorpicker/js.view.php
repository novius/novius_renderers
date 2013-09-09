<script type="text/javascript">
    require([
        'jquery-nos',
        'static/apps/lib_renderers/js/colorpicker',
        'link!static/apps/lib_renderers/css/colorpicker.css'
    ], function( $nos ) {
    $nos(function() {

            $nos('#<?= $id ?>').after('<div id="renderer_<?= $id ?>" class="customRenderer"/>');
			$nos('#renderer_<?= $id ?>').append('<div class="colorSelector"/>');
			$nos('#renderer_<?= $id ?> div.colorSelector').after('<div class="colorpickerHolder"/>');
			$nos('#renderer_<?= $id ?> div.colorSelector').append('<div/>');
            var color = '#' + $nos('#<?= $id ?>').attr('value');
			$nos('#renderer_<?= $id ?> div.colorSelector div').css('backgroundColor', color);

			$nos('#renderer_<?= $id ?> div.colorpickerHolder').ColorPicker({
                flat: true,
                color: color,
                onSubmit: function(hsb, hex, rgb) {
                    $nos('#renderer_<?= $id ?> div.colorSelector div').css('backgroundColor', '#' + hex);
                    $nos('#<?= $id ?>').attr('value', hex);
                    $nos('#renderer_<?= $id ?> div.colorpickerHolder').stop().animate({height: 0}, 500);
                }
            });
            $nos('#renderer_<?= $id ?> div.colorpickerHolder>div').css('position', 'absolute');
            var widt = false;
            $nos('#renderer_<?= $id ?> div.colorSelector').on('click', function() {
                $nos('#renderer_<?= $id ?> div.colorpickerHolder').stop().animate({height: widt ? 0 : 173}, 500);
                widt = !widt;
            });
        });
    });
</script>