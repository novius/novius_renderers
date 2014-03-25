<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2013 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

namespace Novius\Renderers;

class Renderer_Autocomplete extends \Fieldset_Field
{
    protected $renderer_options = array();
    protected $autocomplete_template; //used to store user's template and then applying it on populated values (as tags)
    protected static $DEFAULT_OPTIONS = array(
        'wrapper' => '', //'<div class="datepicker-wrapper"></div>',
    );

    protected static $DEFAULT_ATTRIBUTES = array(
        'data-autocomplete-url' => 'admin/local/autocomplete/search',//function called by ajax
        'data-autocomplete-minlength' => 3,//number of chars used before calling the function above
        //'data-autocomplete-callback' => 'on_click',//function name used once the user has clicked in the list
    );

    public static function create_options(&$attributes)
    {
        $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] : '').' autocomplete';

        if (empty($attributes['id'])) {
            $attributes['id'] = uniqid('autocomplete_');
        }
        //first set data attributes of the input
        if (!empty($attributes['renderer_options']['data'])) {
            $data = (\Arr::merge(static::$DEFAULT_ATTRIBUTES, $attributes['renderer_options']['data']));
            unset($attributes['renderer_options']['data']);
            $attributes = \Arr::merge($attributes, $data);
        } else {
            $attributes = \Arr::merge($attributes, static::$DEFAULT_ATTRIBUTES);
        }

        $options = array();
        //then set options used by the renderer
        if (!empty($attributes['renderer_options'])) {
            $options = \Arr::merge(static::$DEFAULT_OPTIONS, $attributes['renderer_options']);
            unset($attributes['renderer_options']);
        }

        return array($attributes, $options);
    }

    public function __construct($name, $label = '', array $attributes = array(), array $rules = array(), \Fuel\Core\Fieldset $fieldset = null)
    {
        list($attributes, $this->renderer_options) = static::create_options($attributes);
        $this->options['name'] = $name;
        parent::__construct($name, $label, $attributes, $rules, $fieldset);
    }

    /**
     * How to display the field
     * @return type
     */
    public function build()
    {
        $this->autocomplete_template = $this->template;
        $this->template = '{field}';
        parent::build();
        //use fieldset to populate the field
        $item = $this->fieldset()->getInstance();
        $populate = '';
        //keeps the renderer working if populate was made thanks to a key in renderer_options
        if (!empty($this->renderer_options['populate']) && is_callable($this->renderer_options['populate'])) {
            $this->value = $this->renderer_options['populate']($item);
        }
        $hiddenName = !empty( $this->attributes['data-name']) ? $this->attributes['data-name'] : $this->options['name'];
        if (is_array($this->value)) {
            if ($this->attributes['data-multiple'] && !\Str::ends_with($hiddenName, '[]')) {
                $hiddenName .= '[]';
            }
            if (!empty($this->value)) {
                foreach($this->value as $id => $label) {
                    $populate.='<input name="'.$hiddenName.'" type="hidden" value="'.$id.'">';
                    $populate.= '<div class="label-result-autocomplete" data-value="'.$id.'" data-name="'.$hiddenName.'">'.$label.'<span class="delete-label">X</span></div>';
                }
            }
        } else {
            $populate.='<input name="'.$hiddenName.'" type="hidden" value="'.$this->value.'">';
        }
        if (!empty($this->renderer_options['populate_input']) && is_callable($this->renderer_options['populate_input'])) {
            $this->set_value($this->renderer_options['populate_input']($item));
        } else {
            $this->set_value('');//autocomplete input always empty
        }

        $this->fieldset()->append(static::js_init($this->get_attribute('id'), $this->renderer_options));
        //build without user's template
        $build = $this->template((string) parent::build().$populate);
        $this->template = $this->autocomplete_template;
        return $this->template($build);
    }

    /**
     * Standalone build of the autocomplete renderer.
     *
     * @param  array  $renderer Renderer definition (attributes (under 'data' key) + renderer_options)
     * @return string The <input> tag + JavaScript to initialise it
     */
    public static function renderer($renderer = array())
    {
        list($attributes, $renderer_options) = static::create_options($renderer);
        return '<input '.array_to_attr($attributes).' />'.static::js_init($attributes['id'], $renderer_options);
    }

    /**
     * Generates the JavaScript to initialise the renderer
     *
     * @param   string  HTML ID attribute of the <input> tag
     * @return string JavaScript to execute to initialise the renderer
     */
    protected static function js_init($id, $renderer_options = array())
    {
        return \View::forge('novius_renderers::autocomplete/js', array(
            'id' => $id,
            'wrapper' => \Arr::get($renderer_options, 'wrapper', ''),
        ), false);
    }
}
