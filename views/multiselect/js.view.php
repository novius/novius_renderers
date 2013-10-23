<link rel="stylesheet" type="text/css" href="static/apps/lib_renderers/css/ui.multiselect.css"/>
<script type="text/javascript">
    require([
        'jquery-nos',
        'jquery-ui.droppable',
        'static/apps/lib_renderers/js/multiselect/ui.multiselect'
        <?= (!empty($locale) && file_exists('static/apps/lib_renderers/js/multiselect/locales/jquery.uix.multiselect_'.$locale.'.js')) ? ", 'static/apps/lib_renderers/js/multiselect/locales/jquery.uix.multiselect_".$locale.".js'" : '' ?>
    ], function( $nos ) {
    $nos(function() {
            var  $m = $nos('#<?= $id ?>');
			$m.nosOnShow('one', function() {
                $nos(this)
                        .css(<?= $css ?>);
                $nos(this).multiselect(<?= $options ?>);
                $nos(this).multiselect('option', 'locale', '<?= $locale ?>');
            });
            $m.nosOnShow();
        });
    });
</script>