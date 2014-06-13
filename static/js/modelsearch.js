
function click_modelsearch(infos) {
    require(['jquery-nos'], function($nos) {
        var $ul = $nos('ul.autocomplete-liste');
        var $input = infos.root.closest('div.modelsearch').find('input[type="hidden"]');
        $ul.hide();
        infos.root.val(infos.label).trigger('focus');
        $input.val(infos.value);
    });
}
function update_post($select) {

    var model = $select.find('option:selected').val();
    var post = {};
    var event = $nos.Event('update_autocomplete.renderer');
    if (model.length > 0) {
        post.model = model;
        $select.parent().find('input.autocomplete')
            .data('autocomplete-post', post)
            .trigger(event);
    }
}
require(['jquery-nos'], function($nos) {
    $nos('body').on('change', 'div.modelsearch select', function() {
        var $select = $nos(this);
        update_post($select);
    });
});