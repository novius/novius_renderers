<?php
$id = uniqid();
?>

<div id="<?= $id ?>">
    <?php if ($url): ?><a href="<?= $url ?>"><?php endif ?>
        <?= $text ?>
    <?php if ($url): ?></a><?php endif ?>
</div>

<script>
    require([
        'jquery-nos'
    ], function ($) {
        $(function () {
            var $input = $('#<?= $id ?>');
            $input.find('a').click(function () {
                $this = $(this);
                url = $this.attr('href');
                $(this).nosAction({
                    action: 'nosTabs',
                    method: 'add',
                    tab: {
                        url: url
                    }
                });
                return false;
            });
        });
    });
</script>
