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
        'static/apps/novius_renderers/js/autocomplete/init.js',
        'link!static/apps/novius_renderers/css/autocomplete.css'
    ], function ($nos, init) {
        var js_id = '<?= $id ?>';
        var $content = <?= empty($wrapper) ? '$nos(\'body\').find(\'[data-id="\' + js_id + \'"]\').closest(\'form\')' : '$nos(\''.$wrapper.'\')' ?>;
        init($content, js_id);
    });
</script>
