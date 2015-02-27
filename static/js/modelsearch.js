// Update the post attribute on model selection
require(['jquery-nos'], function($nos) {

    // Update autocomplete on model change
    $nos('body').on('change', 'div.modelsearch select', function() {
        var $select = $nos(this);

        $container = $select.parents('.modelsearch');
        $autocomplete = $container.find('.ms-autocomplete');
        $external = $container.find('.ms-external');

        autoCompleteHidden = false;
        if ($select.val() === '') {
            autoCompleteHidden = true;
        }

        show = autoCompleteHidden ? $external : $autocomplete;
        hide = autoCompleteHidden ? $autocomplete : $external;
        hide.addClass('ms-hidden');
        show.removeClass('ms-hidden');


        update_autocomplete_post($select);
        // Clear the search and value fields
        $select.closest('.modelsearch').find('[name="search[]"], .ms-value input[type="hidden"]').val('');
    });

    /**
     * Update the autocomplete post attribute
     *
     * @param $select
     */
    function update_autocomplete_post($select) {
        // Get the autocomplete input
        var $input = $select.closest('.modelsearch').find('input.autocomplete');
        if (!$input.length) {
            return ;
        }
        // Build and save the new post attributes
        var post = $input.data('autocomplete-post') || {};
        var model = $select.find('option:selected').val();
        post.model = model.length > 0 ? model : '';
        $input.attr('data-autocomplete-post', post).trigger('update_autocomplete.renderer');
    }
});

/**
 * Callback on autocomplete selection
 *
 * @param infos
 */
function click_modelsearch(infos) {
    var $root = infos.root.closest('.modelsearch');
    require(['jquery-nos'], function($nos) {
        // Hide the autocomplete suggestions
        $root.find('ul.autocomplete-liste').hide();
        // Update the selected value (single)
        infos.root.val(infos.label).trigger('focus');
        $root.find('.ms-value input[type="hidden"]').val(infos.value);
    });
}
