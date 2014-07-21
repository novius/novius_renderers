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

        // Delete selection (multiple only)
        $content.on('click', 'span.delete-label', function() {
            $nos(this).closest('.label-result-autocomplete').remove();
        });

        // Initialize the autocomplete
        autocomplete('<?= empty($wrapper) ? 'body' : $wrapper ?>', {
            on_click : function(infos) {
                var $input = infos.root;
                var hiddenName = $input.attr('data-name') || $input.data('name') || $input.attr('name') + '-id';
                var multiple = ($input.attr('data-multiple') == 1) || ($input.data('multiple') == 1);

                // Multiple selection
                if (multiple) {
                    //add [] because of the multiple sent values
                    if (hiddenName.indexOf('[]', hiddenName.length - 2) === -1) {
                        hiddenName += '[]';
                    }
                    // Clears the text typed by the user to get suggestions
                    $input.val('').trigger('focus');
                    // Add item as a tag, paired with an hidden input
                    if (!$input.closest('form').find('.label-result-autocomplete[data-name="'+hiddenName+'"][data-value="'+infos.value+'"]').length) {
                        $input.after(
                            $(document.createElement('div'))
                                .addClass('label-result-autocomplete')
                                .attr({ 'data-value': infos.value, 'data-name': hiddenName })
                                .html(infos.label)
                                .append(
                                    $(document.createElement('span')).addClass('delete-label').html('X')
                                )
                                .append(
                                    $(document.createElement('input')).attr({
                                        name: hiddenName,
                                        type: 'hidden',
                                        value: infos.value
                                    })
                                )
                        );
                    }
                }

                // Single selection
                else {
                    // Replace the text typed by the user to get suggestions by the selected label
                    console.log(infos.label);
                    $input.val(infos.label).trigger('focus');
                    // Update the hidden value
                    var $hidden_input = $input.cloest('form').find('input[name="'+hiddenName+'"]');
                    if (!$hidden_input.length) {
                        // Creates the hidden field whether it doesn't already exists
                        $hidden_input = $(document.createElement('input')).attr({
                            name: hiddenName,
                            type: 'hidden'
                        });
                        $input.after($hidden_input);
                    }
                    $hidden_input.val(infos.value);
                }

                // Hide the autocomplete list
                $input.nextAll('ul.autocomplete-liste').first().hide();
            }
        });
    });
</script>