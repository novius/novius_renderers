require(['jquery-nos-wysiwyg'], function ($) {
    /**
     * Restore the order hidden inputs on a while list, going from 0 to XX
     * This isn't the most optimal way of doing it but it is the most resilient and cost-effective
     *
     * @param $list node like $('.item_list')
     */
    function restore_order($list) {
        var order = 0;

        $list.find('> .hasmany_item > input[data-hasmany-order]').each(function(){
            this.value = order++;
        });
    }

    //Add one item
    $(document).on('click', 'button.add-item-js', function(e) {
        var $button = $(this);
        var $container = $button.closest('.count-items-js');
        var next = $container.find('.hasmany_item').length;
        var btnData = $button.data();
        var data = {};
        for (i in btnData) {
            if (typeof btnData[i] !== 'function' && typeof btnData[i] !== 'object') {
                data[i] = btnData[i];
            }
        }
        $.ajax({
            type : "GET",
            url: 'admin/novius_renderers/hasmany/add_item/' + next,
            data : data,
            success : function(vue) {
                var $vue = $(vue);
                $vue.nosFormUI();
                var $itemList = $container.children('.item_list');
                $itemList.append($vue);
                $container.data('nb-items', next);
                restore_order($itemList);
            }
        });
        e.preventDefault();
    });

    //Duplicate an item
    $(document).on('click', 'button.dupli-item-js', function(event){
        var $div = $(this).closest('.hasmany_item');
        var index = $div.data('item-index');
        var next = $div.closest('.count-items-js').find('.hasmany_item').length;
        var $button = $div.closest('.count-items-js').find('button.add-item-js');
        var model = $button.data('model') || $button.attr('data-model');
        var data = {};
        data.forge = {};
        var btnData = $button.data();
        for (i in btnData) {
            if (typeof btnData[i] !== 'function' && typeof btnData[i] !== 'object') {
                data[i] = btnData[i];
            }
        }

        //select all inputs (cannot search on name, assuming it begins with "relation", because it's possible that it doesn't
        $div.find('input, select').each(function() {
            var $input = $(this);
            var input_name = $input.attr('name');
            if (typeof input_name != "undefined" && input_name.length > 0) {
                var value = $input.val();
                var begin = input_name.lastIndexOf('[') + 1;
                if (begin > 0) {
                    var end = input_name.lastIndexOf(']');
                    var name = input_name.substring(begin, end);
                    data.forge[name] = value;
                }
            }
        });

        $nos.ajax({
            type : "GET",
            url: 'admin/novius_renderers/hasmany/add_item/' + next,
            data : data,
            success : function(vue) {
                var $vue = $(vue);
                $vue.nosFormUI();
                $div.closest('.item_list').append($vue);
                $div.closest('.count-items-js').data('nb-items', next);

                restore_order($div.closest('.item_list'));
            }
        });
        event.preventDefault();
    });

    //Delete an item
    $(document).on('click', '.item-delete-js', function(event) {
        event.preventDefault();
        var question = $(this).data('question');
        var removed = $(this).data('removed');
        var $list = $(this).closest('.item_list');
        if (confirm(question)) {
            var $item = $(this).closest('.hasmany_item');
            if (removed.length > 0) {
                $item.find('.head').remove();
                $item.html(
                    $('<div/>').addClass('hasmany_message ui-state-error').html(removed)
                );
            } else {
                $item.remove();
            }

        }

        restore_order($list);
    });

    /**
     * Move around an item
     * The function keeps the tinyMCE editors alive and keep a clean order
     */
    $(document).on('click', '.item-down-js, .item-up-js', function(event) {
        event.preventDefault();
        var down = $(this).hasClass('item-down-js'),
            $item = $(this).closest('.hasmany_item'),
            $swapper = down ? $item.nextAll('div:eq(0)') : $item.prevAll('div:eq(0)'),
            $textarea = $item.find('textarea[name*=wysiwyg]'),
            order = 0;

        // already top or bottom
        if ($swapper.length == 0) {
            return;
        }

        //Deal with possible wysiwyg's
        $textarea.each(function(){
            var id_tiny = $(this).attr('id');
            if (tinyMCE) {
                var wysi = tinyMCE.get(id_tiny);
                if (wysi) {
                    wysi.save();
                    wysi.remove();
                }
            }
        });
        // move it
        if (down) {
            $swapper.after($item);
        } else {
            $swapper.before($item);
        }

        // set order on all hidden input! 0..X
        restore_order($item.closest('.item_list'));

        // restore wysiwyg
        $textarea.each(function(){
            $(this).wysiwyg($(this).data('wysiwyg-options'));
        });
    });
});

