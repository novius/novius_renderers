/**
 * Created by albert on 26/11/14.
 */

define(
    [
        'jquery-nos',
        'static/apps/novius_renderers/js/autocomplete.js'
    ],
    function ($nos, autocomplete) {
        return function($content, js_id) {

            var addMultiple = function($input, hiddenName, infos) {
                // Search
                var $values = $input.closest('form').find('.label-result-autocomplete[data-name="'+hiddenName+'"]');

                // Add item as a tag, paired with an hidden input
                if (!$values.is('[data-value="'+infos.value+'"]')) {

                    var $value = $(document.createElement('div'))
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
                        );

                    if ($values.length) {
                        $values.last().after($value);
                    } else {
                        $input.after($value);
                    }
                }
            };

            var addSingle = function($input, hiddenName, infos) {
                // Update the hidden value
                var $hidden_input = $input.closest('form').find('input[name="'+hiddenName+'"]');
                if (!$hidden_input.length) {
                    // Creates the hidden field whether it doesn't already exists
                    $hidden_input = $(document.createElement('input')).attr({
                        name: hiddenName,
                        type: 'hidden'
                    });
                    $input.after($hidden_input);
                }
                $hidden_input.val(infos.value);
            };

// Delete selection (multiple only)
            $content.on('click', 'span.delete-label', function() {
                $nos(this).closest('.label-result-autocomplete').remove();
            });

// Initialize the autocomplete
            autocomplete($content, {
                on_click : function(infos) {
                    var $input = infos.root;
                    var multiple = ($input.attr('data-multiple') == 1) || ($input.data('multiple') == 1);
                    var crud = $input.attr('data-autocomplete-crud') || $input.data('autocomplete-crud');
                    var $ul = $input.nextAll('ul.autocomplete-liste').first();
                    var insert = (typeof crud != 'undefined') && (crud.length > 0);
                    var hiddenName = $input.attr('data-name') || $input.data('name') || $input.attr('name') + '-id';

                    if (multiple) {
                        //add [] because of the multiple sent values
                        if (hiddenName.indexOf('[]', hiddenName.length - 2) === -1) {
                            hiddenName += '[]';
                        }
                    }

                    if (insert) {
                        //Add listener in case an item is added
                        $content.nosListenEvent({
                                name: 'crudJson',
                                is: js_id
                            },
                            function(event) {
                                if (multiple) {
                                    infos = {
                                        value: event.id,
                                        label: event.title
                                    };
                                    addMultiple($input, hiddenName, infos);
                                    $input.val('').trigger('focus');
                                } else {
                                    infos = {
                                        value: event.id,
                                        label: event.title
                                    };
                                    // Replace the text typed by the user to get suggestions by the selected label
                                    $input.val(infos.label).trigger('focus');
                                    addSingle($input, hiddenName, infos);
                                }
                            }
                        );
                    }

                    if (infos.value) {
                        // Multiple selection
                        if (multiple) {
                            // Clears the text typed by the user to get suggestions
                            $input.val('').trigger('focus');
                            addMultiple($input, hiddenName, infos);
                        }
                        // Single selection
                        else {
                            // Replace the text typed by the user to get suggestions by the selected label
                            $input.val(infos.label).trigger('focus');
                            addSingle($input, hiddenName, infos);
                        }
                    } else {

                        if (insert) {

                            $ul.nosDialog('open', {
                                contentUrl:  'admin/novius_renderers/autocomplete/call_crud',
                                ajax: true,
                                ajaxData: {
                                    _crud: crud,
                                    _js_id: js_id,
                                    _value: infos.root.val()
                                }
                            });
                        }
                    }

                    // Hide the autocomplete list
                    $ul.hide();
                }
            });
        }
});