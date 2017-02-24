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

<link rel="stylesheet" type="text/css" href="static/apps/novius_renderers/css/ui.multiselect.css"/>
<script type="text/javascript">
    require([
        'jquery-nos',
        'jquery-ui.droppable',
        'static/apps/novius_renderers/js/multiselect/ui.multiselect'
        <?= (!empty($locale) && file_exists('static/apps/novius_renderers/js/multiselect/locales/jquery.uix.multiselect_'.$locale.'.js')) ? ", 'static/apps/novius_renderers/js/multiselect/locales/jquery.uix.multiselect_".$locale.".js'" : '' ?>
    ], function( $nos ) {
    $nos(function() {
            var  $m = $nos('#<?= $id ?>');
			$m.nosOnShow('one', function() {
                $nos(this)
                        .css(<?= $css ?>);
                $nos(this).multiselect(<?= $options ?>);
                $nos(this).multiselect('option', 'locale', '<?= $locale ?>');
            });
            $m.nosOnShow();
        });
    });
</script>
