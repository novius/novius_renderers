<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2011 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

namespace Lib\Renderers;

class Renderer_Multiselect extends \Fieldset_Field
{
    //Multiselect from : http://www.quasipartikel.at/multiselect/
    protected $widget_options = array();

    public function __construct($name, $label = '', $attributes = array(), $rules = array(), \Fuel\Core\Fieldset $fieldset = null)
    {
        $attributes['type']  = 'select';
        $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] : '').' multiselect notransform';
        $attributes['multiple'] = 'multiple';
        if (empty($attributes['id'])) {
            $attributes['id'] = uniqid('multi_');
        }
        if (!empty($attributes['widget_options'])) {
            if (is_array($attributes['widget_options'])) {
                //$this->set_attribute('data-multiselect-options', htmlspecialchars(\Format::forge()->to_json($attributes['widget_options'])));
                $this->widget_options = \Arr::merge($this->widget_options, $attributes['widget_options']);
                unset($attributes['widget_options']);
            }
        }
        $attributes['style'] = (isset($attributes['style']) ? $attributes['style'] : ''). 'display:none;';
        parent::__construct($name, $label, $attributes, $rules, $fieldset);
    }

    /**
     * How to display the field
     * @return string
     */
    public function build()
    {
        parent::build();

        $this->fieldset()->append($this->js_init());
        return (string) parent::build();
    }

    public function js_init()
    {
        $id = $this->get_attribute('id');
        return \View::forge('lib_renderers::multiselect/js', array(
            'id' => $id,
            'options' => \Format::forge()->to_json($this->widget_options),
        ), false);
    }

}
