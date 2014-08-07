<script type="text/javascript">
    require([
        'jquery-nos',
        'static/apps/novius_renderers/js/showdown',
        'static/apps/novius_renderers/js/wmd',
        'link!static/apps/novius_renderers/css/wmd.css'
    ], function( $ ) {
        $(function() {
            var $input = $('#<?= $id ?>');
            var $toolbar = $('<div id="toolbar_<?= $id ?>" class="wmd-toolbar"></div>');
            $toolbar.insertBefore($input);
            new WMD("<?= $id ?>", "toolbar_<?= $id ?>", <?= \Fuel\Core\Format::forge($options)->to_json() ?>);
        });
    });
</script>