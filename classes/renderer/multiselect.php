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
    protected $renderer_options = array();
    protected $field_style = array(
        'width' => '60%',
        'height' => '150px',
    );

    public function __construct($name, $label = '', $attributes = array(), $rules = array(), \Fuel\Core\Fieldset $fieldset = null)
    {
        $attributes['type']  = 'select';
        $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] : '').' multiselect notransform';
        $attributes['multiple'] = 'multiple';
        if (empty($attributes['id'])) {
            $attributes['id'] = uniqid('multi_');
        }
        if (!empty($attributes['renderer_options'])) {
            if (is_array($attributes['renderer_options'])) {
                //$this->set_attribute('data-multiselect-options', htmlspecialchars(\Format::forge()->to_json($attributes['renderer_options'])));
                $this->renderer_options = \Arr::merge($this->renderer_options, $attributes['renderer_options']);
                unset($attributes['renderer_options']);
            }
        }
        if (!empty($attributes['style'])) {
            if (is_array($attributes['style'])) {
                $this->field_style = \Arr::merge($this->field_style, $attributes['style']);
                unset($attributes['style']);
            }
        }
        //hide the real select
        $attributes['style'] = 'display:none;';
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
            'options' => \Format::forge()->to_json($this->renderer_options),
            'css' => \Format::forge()->to_json($this->field_style),
        ), false);
    }

}
