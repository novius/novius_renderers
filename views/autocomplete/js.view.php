<script type="text/javascript">
require([
    'jquery-nos',
    'static/apps/lib_renderers/js/autocomplete',
    'link!static/apps/lib_renderers/css/autocomplete.css'
], function ($, autocomplete) {
    autocomplete('<?= empty($wrapper) ? 'body' : $wrapper ?>', {
        on_click : function(infos) {
            infos.root.val(infos.label).trigger('focus');
            $('<ul class="autocomplete-liste"></ul>').hide();
        }
    });
});
</script>