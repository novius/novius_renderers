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
        'static/apps/lib_renderers/js/colorpicker',
        'link!static/apps/lib_renderers/css/colorpicker.css'
    ], function( $ ) {
    $(function() {

        var $input = $('#<?= $id ?>');
        $input.wrap('<div id="renderer_<?= $id ?>" class="customRenderer"/>');
        // On wrap un div display none pour forcer le non-affichage
        // (cas du common field qui rajoute un display:block sur le champ ça pète l'affichage)
        $input.wrap('<div style="display:none;"></div>');
        var $renderer = $('#renderer_<?= $id ?>');
        $renderer.append('<div class="colorSelector"/>');
        $('#renderer_<?= $id ?> div.colorSelector').after('<div class="colorpickerHolder"/>');
        $('#renderer_<?= $id ?> div.colorSelector').append('<div/>');
        var color = '#' + $input.val();
        $('#renderer_<?= $id ?> div.colorSelector div').css('backgroundColor', color);

        $('#renderer_<?= $id ?> div.colorpickerHolder').ColorPicker({
            flat: true,
            color: color,
            onSubmit: function(hsb, hex, rgb) {
                $('#renderer_<?= $id ?> div.colorSelector div').css('backgroundColor', '#' + hex);
                $input.attr('value', hex);
                $('#renderer_<?= $id ?> div.colorpickerHolder').stop().animate({height: 0}, 500);
            }
        });
        $('#renderer_<?= $id ?> div.colorpickerHolder>div').css('position', 'absolute');
        var width = false;
        $('#renderer_<?= $id ?> div.colorSelector').on('click', function() {
            $('#renderer_<?= $id ?> div.colorpickerHolder').stop().animate({height: width ? 0 : 173}, 500);
            width = !width;
        });

        // Si c'est un common field, alors l'overlay qui affiche la popup aura été calculé avant (pas assez haut)
        if ($input.is('[context_common_field]')) {
            $renderer.next().height('36px');
        }
    });
});
</script>