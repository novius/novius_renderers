<?php
/**
* NOVIUS OS - Web OS for digital communication
*
* @copyright  2013 Novius
* @license    GNU Affero General Public License v3 or (at your option) any later version
*             http://www.gnu.org/licenses/agpl-3.0.html
* @link http://www.novius-os.org
*/
?>
<script type="text/javascript">
    require([
        'jquery-nos',
        'static/apps/novius_renderers/js/autocomplete',
        'link!static/apps/novius_renderers/css/autocomplete.css'
    ], function ($nos, autocomplete) {

        var $content = $nos('<?= empty($wrapper) ? 'body' : $wrapper ?>');
        $content.on('click', 'span.delete-label', function() {
            var $div = $nos(this).parent();
            var value = $div.attr('data-value');
            var name = $div.attr('data-name');
            //remove hidden input, then remove label
            $div.closest('form').find('input[name="'+name+'"][value="'+value+'"]').remove();
            $div.remove();
        });

        autocomplete('<?= empty($wrapper) ? 'body' : $wrapper ?>', {
            on_click : function(infos) {
                var $ul = $nos('ul.autocomplete-liste');
                var $input = infos.root;
                var name = $input.attr('name');
                var hiddenName = $input.attr('data-name') || $input.data('name') || name + '-id';
                var multiple = ($input.attr('data-multiple') == 1) || ($input.data('multiple') == 1) || 0;
                var label = '';
                if (multiple) {
                    //add [] because of the multiple sent values
                    if (hiddenName.indexOf('[]', hiddenName.length - 2) === -1) {
                        hiddenName += '[]';
                    }
                    //flush value
                    $input.val('').trigger('focus');
                    //then add item as a tag, paired with an hidden input
                    $input.after('<input name="'+hiddenName+'" type="hidden" value="'+infos.value+'">');
                    label = '<div class="label-result-autocomplete" data-value="'+infos.value+'" data-name="'+hiddenName+'">'+infos.label+'<span class="delete-label">X</span></div>';
                    $input.after(label);
                } else {
                    $input.val(infos.label).trigger('focus');
                    //update hidden input value if possible, create it otherwise
                    if($input.parent().find('input[name="'+hiddenName+'"]').length) {
                        $input.parent().find('input[name="'+hiddenName+'"]').val(infos.value);
                    } else {
                        $input.after('<input name="'+hiddenName+'" type="hidden" value="'+infos.value+'">');
                    }
                    $ul.hide();
                }
            }
        });
    });
</script>