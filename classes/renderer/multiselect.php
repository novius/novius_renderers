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
    protected $renderer_options = array();
    protected $renderer_style = array();

    //Multiselect from : http://www.quasipartikel.at/multiselect/
    protected static $DEFAULT_OPTIONS = array();
    protected static $DEFAULT_STYLE = array(
        'width' => '60%',
        'height' => '150px',
    );

    public static function create_options(&$attributes)
    {
        $attributes['type']  = 'select';
        $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] : '').' multiselect notransform';
        $attributes['multiple'] = 'multiple';
        if (empty($attributes['id'])) {
            $attributes['id'] = uniqid('multi_');
        }
        $options = static::$DEFAULT_OPTIONS;
        if (!empty($attributes['renderer_options'])) {
            if (is_array($attributes['renderer_options'])) {
                //$this->set_attribute('data-multiselect-options', htmlspecialchars(\Format::forge()->to_json($attributes['renderer_options'])));
                $options = \Arr::merge($options, $attributes['renderer_options']);
                unset($attributes['renderer_options']);
            }
        }
        $style = static::$DEFAULT_STYLE;
        if (!empty($attributes['style'])) {
            if (is_array($attributes['style'])) {
                $style = \Arr::merge($style, $attributes['style']);
                unset($attributes['style']);
            }
        }
        //hide the real select
        $attributes['style'] = 'display:none;';

        return array($attributes, $options, $style);
    }
    public function __construct($name, $label = '', $attributes = array(), $rules = array(), \Fuel\Core\Fieldset $fieldset = null)
    {
        list($attributes, $this->renderer_options, $this->renderer_style) = static::create_options($attributes);
        parent::__construct($name, $label, $attributes, $rules, $fieldset);
    }

    /**
     * How to display the field
     * @return string
     */
    public function build()
    {
        parent::build();

        $this->fieldset()->append($this->js_init($this->get_attribute('id'), $this->renderer_options, $this->renderer_style));
        return (string) parent::build();
    }

    /**
     * Standalone build of the multiselect renderer.
     *
     * @param  array  $renderer Renderer definition (attributes (under 'data' key) + renderer_options)
     * @return string The <input> tag + JavaScript to initialise it
     */
    public static function renderer($renderer = array())
    {
        list($attributes, $renderer_options, $style) = static::create_options($renderer);
        $options = (array) $attributes['options'];
        $values = (array) $attributes['values'];
        $st_options = '';
        foreach ($options as $key => $opt) {
            $st_options .= in_array($key, $values) ? '<option value="'.$key.'" selected="selected">' : '<option value="'.$key.'">';
            $st_options .= $opt;
            $st_options .= '</option>';
        }
        unset($attributes['options']);
        unset($attributes['values']);
        return '<select '.array_to_attr($attributes).'>'.$st_options.'</select>'.static::js_init($attributes['id'], $renderer_options, $style);
    }

    public function js_init($id, $options, $style)
    {
        return \View::forge('lib_renderers::multiselect/js', array(
            'id' => $id,
            'options' => \Format::forge()->to_json($options),
            'css' => \Format::forge()->to_json($style),
        ), false);
    }

}
