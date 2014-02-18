<script type="text/javascript">
    require([
        'jquery-nos'
    ], function($)
    {
        $(function()
        {
            var $container      = $('#<?= $id; ?>');
            var media_options   = $container.find('input.media').data('media-options');
            var $span = $container.find('span[data-next]');
            var key = $span.data('key');
            media_options.inputFileThumb.file = "";//flush image
            $container.on('change', 'input.media', function(e, data)
            {
                var $input = $container.find('input.media:last');
                // add a new renderer only if the last one has been used
                if ($input.val() > 0)
                {
                    var index = $span.data('next');
                    var $newimg = $('<input name="'+ key +'[' + index + ']" class="media" type="hidden" value="">');
                    $newimg.nosMedia(media_options);
                    $container.append($newimg);
                    $span.data('next', index + 1);
                }
            });
        });
    });
</script>