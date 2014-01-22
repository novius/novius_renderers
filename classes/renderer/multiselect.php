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

use Fuel\Core\Input;

class Renderer_Multiselect extends \Fieldset_Field
{
    protected $renderer_options = array();
    protected $renderer_style = array();

    //Multiselect from : https://github.com/yanickrochon/jquery.uix.multiselect
    protected static $DEFAULT_OPTIONS = array(
        'availableListPosition' => 'left',
        'searchField' => true,
    );
    protected static $DEFAULT_STYLE = array(
        'width' => '70%',
        'height' => '150px',
        'margin' => '10px 0'
    );

    public function repopulate(array $input)
    {
        $value = \Arr::get($input, $this->name, null);
        $this->set_value($value, true);
    }

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
                if (isset($options['order'])) {
                    $options['sortable'] = $options['order'];//preserve retro compatibility
                    unset($options['order']);
                }
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
        if (isset($this->renderer_options['sortable']) && $this->renderer_options['sortable']) {
            //if sortable, then assue values are sort, and then construct options based on that sort
            $options = $this->options;
            $values = $this->value;
            while (!empty($values)) {
                $val = array_pop($values);//get values from the last, in order to apply a FILO
                $label = $options[$val];//get label of the current value
                unset($options[$val]);//remove the value where it was
                \Arr::insert_assoc($options, array($val => $label), 0);//put value on top and preserve keys
            }
            $this->options = $options;
        }
        $this->fieldset()->append(static::js_init($this->get_attribute('id'), $this->renderer_options, $this->renderer_style));
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
        if ($renderer['sortable']) {
            foreach ($values as $val) {
                $st_options .= array_key_exists($val, $options) ? '<option value="'.$val.'" selected="selected">' : '<option value="'.$val.'">';
                $st_options .= $options[$val];
                $st_options .= '</option>';
                unset($options[$val]);
            }
        }
        foreach ($options as $key => $opt) {
            $st_options .= in_array($key, $values) ? '<option value="'.$key.'" selected="selected">' : '<option value="'.$key.'">';
            $st_options .= $opt;
            $st_options .= '</option>';
        }
        unset($attributes['options']);
        unset($attributes['values']);
        return '<select '.array_to_attr($attributes).'>'.$st_options.'</select>'.static::js_init($attributes['id'], $renderer_options, $style);
    }

    public static function js_init($id, $options, $style)
    {
        $user = \Session::user();
        return \View::forge('lib_renderers::multiselect/js', array(
            'id' => $id,
            'options' => \Format::forge()->to_json($options),
            'css' => \Format::forge()->to_json($style),
            'locale' => \Str::sub($user->user_lang, 0, 2),
        ), false);
    }

}
