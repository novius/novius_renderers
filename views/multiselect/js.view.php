<script type="text/javascript">
    require([
        'jquery-nos',
        'static/apps/lib_renderers/js/ui.multiselect',
        'link!static/apps/lib_renderers/css/ui.multiselect.css'
    ], function( $nos ) {
    $nos(function() {
            var  $m = $nos('#<?= $id ?>');
			$m.nosOnShow('one', function() {
                $nos(this)
                        .css(<?= $css ?>);
                $nos(this).multiselect(<?= $options ?>);
            });
            $m.nosOnShow();
        });
    });
</script>