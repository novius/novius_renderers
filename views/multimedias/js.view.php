<script type="text/javascript">
    require([
        'jquery-nos',
        'link!static/apps/novius_renderers/css/multimedias.css'
    ], function ($) {
        $(function () {
            var $container = $('#<?= $id; ?>');
            var media_options = $container.find('input.media').data('media-options');
            var $span = $container.find('span[data-next]');
            var key = $span.data('key');
            var sortable = $container.data('sortable');
            if (sortable) {
                $container.find('ul').sortable({
                    helper: 'clone'
                });
                $container.find('ul').disableSelection();
            }
            media_options.inputFileThumb.file = "";//flush image
            $container.on('change', 'input.media', function (e, data) {
                var $input = $container.find('input.media:last');
                // add a new renderer only if the last one has been used
                if ($input.val() > 0) {
                    // If input not in list, put in it.
                    var $parentLi = $input.parents('.wrapper_elements');
                    if (!$parentLi.length) {
                        var $item = $input.closest('.ui-widget');
                        var $wrapper = $container.find('.wrapper_elements');
                        var classesToAdd = $wrapper.data('addclass');
                        var $li = $("<li/>").addClass(classesToAdd);
                        $item.appendTo($li);
                        $wrapper.append($li);
                    }

                    var index = $span.data('next');
                    var limit = $span.data('limit');
                    // add a new media picker only if limit is not defined or it has not been reach
                    if ((typeof limit != 'number') || (index <= limit)) {
                        var $newimg = $('<input name="' + key + '[' + index + ']" class="media" type="hidden" value="">');
                        $newimg.nosMedia(media_options);
                        $container.find('.picker-container').append($newimg);
                        $span.data('next', index + 1);
                    }
                }
            });
        });
    });
</script>
