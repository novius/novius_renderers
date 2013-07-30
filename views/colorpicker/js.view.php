<script type="text/javascript">
    require([
        'jquery-nos',
        'static/apps/lib_renderers/js/colorpicker',
        'link!static/apps/lib_renderers/css/colorpicker.css'
    ], function( $nos ) {
    $nos(function() {

            $nos('#<?= $id ?>').after('<div class="customRenderer"/>');
			$nos('div.customRenderer').append('<div class="colorSelector"/>');
			$nos('div.colorSelector').after('<div class="colorpickerHolder"/>');
			$nos('div.colorSelector').append('<div/>');
            var color = '#' + $nos('#<?= $id ?>').attr('value');
			$nos('div.colorSelector div').css('backgroundColor', color);

			$nos('div.colorpickerHolder').ColorPicker({
                flat: true,
                color: color,
                onSubmit: function(hsb, hex, rgb) {
                    $nos('div.colorSelector div').css('backgroundColor', '#' + hex);
                    $nos('#<?= $id ?>').attr('value', hex);
                    $nos('div.colorpickerHolder').stop().animate({height: 0}, 500);
                }
            });
            $nos('div.colorpickerHolder>div').css('position', 'absolute');
            var widt = false;
            $nos('div.colorSelector').bind('click', function() {
                $nos('div.colorpickerHolder').stop().animate({height: widt ? 0 : 173}, 500);
                widt = !widt;
            });
        });
    });
</script>