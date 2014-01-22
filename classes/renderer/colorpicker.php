<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2013 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

namespace Lib\Renderers;

class Renderer_Colorpicker extends \Fieldset_Field
{
    protected $renderer_options = array();

    public function __construct($name, $label = '', array $attributes = array(), array $rules = array(), \Fuel\Core\Fieldset $fieldset) {

        $attributes['type']  = 'text';
        $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] : '').' colorpicker notransform';
        if (empty($attributes['id'])) {
            $attributes['id'] = uniqid('color_');
        }
        parent::__construct($name, $label, $attributes, $rules, $fieldset);
    }

    /**
     * How to display the field
     * @return string
     */
    public function build() {
        parent::build();

        $this->fieldset()->append($this->js_init());
        return (string) parent::build();
    }

    public function js_init() {
        $id = $this->get_attribute('id');
        return \View::forge('lib_renderers::colorpicker/js', array(
            'id' => $id,
        ), false);
    }

}
