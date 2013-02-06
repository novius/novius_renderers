<link rel="stylesheet" href="static/apps/lib_renderers/css/autocomplete.css">
<script type="text/javascript">
require(['jquery-nos', 'static/apps/lib_renderers/js/autocomplete.js'], function ($, autocomplete) {
    autocomplete('body', {
        on_click : function(infos) {
            infos.root.val(infos.label).trigger('focus');
            $('<ul class="autocomplete-liste"></ul>').hide();
        }
    });
});
</script>