<?php
$id = uniqid();
?>

<div id="<?= $id ?>">
    <?php
    if ($url) {
    ?>
    <a href="<?= $url ?>">
        <?php
        }

        echo $text;
        if ($url) {
        ?>
    </a>
<?php
}
?>
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
                    tab   : {
                        url: url
                    }
                });
                console.log('bite');
                return false;
            });
        });
    });
</script>