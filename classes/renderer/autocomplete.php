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

use Fuel\Core\Fieldset;

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

    /**
     * Initialize options and attributes
     *
     * @param $name
     * @param string $label
     * @param array $attributes
     * @param array $rules
     * @param Fieldset $fieldset
     */
    public function __construct($name, $label = '', array $attributes = array(), array $rules = array(), \Fuel\Core\Fieldset $fieldset = null)
    {
        list($attributes, $this->renderer_options) = static::create_options($attributes);
        $this->options['name'] = $name;
        parent::__construct($name, $label, $attributes, $rules, $fieldset);
    }

    /**
     * Extract options from renderer attributes
     *
     * @param $attributes
     * @return array
     */
    public static function create_options(&$attributes)
    {
        $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] : '').' autocomplete';

        // Generate a unique ID for the field if none were defined
        if (empty($attributes['id'])) {
            $attributes['id'] = uniqid('autocomplete_');
        }

        // Autocomplete on a model
        $model = \Arr::get($attributes, 'renderer_options.data.data-autocomplete-model');
        if (!empty($model)) {
            // Use the native controller url if none were defined
            if (!\Arr::get($attributes, 'renderer_options.data.data-autocomplete-url')) {
                \Arr::set($attributes, 'renderer_options.data.data-autocomplete-url', 'admin/novius_renderers/autocomplete/search_model');
            }
            // Add the model into the posted vars
            \Arr::set($attributes, 'renderer_options.data.data-autocomplete-post', static::json_merge(
                \Arr::get($attributes, 'renderer_options.data.data-autocomplete-post'),
                array('model' => $model)
            ));
        }

        // Extract data from renderer options
        $attributes = \Arr::merge(static::$DEFAULT_ATTRIBUTES, $attributes, \Arr::get($attributes, 'renderer_options.data', array()));
        \Arr::delete($attributes, 'renderer_options.data');

        // Extract renderer options from attributes
        $options = \Arr::merge(static::$DEFAULT_OPTIONS, \Arr::get($attributes, 'renderer_options', array()));
        \Arr::delete($attributes, 'renderer_options');

        // Prevent from displaying native autocomplete
        $attributes['autocomplete'] = 'off';

        return array($attributes, $options);
    }

    /**
     * How to display the field
     *
     * @return type
     */
    public function build()
    {
        $this->autocomplete_template = $this->template;
        $this->template = '{field}';
        //parent::build();

        // Use fieldset to populate the field
        $item = $this->fieldset()->getInstance();

        $is_multiple = !empty($this->attributes['data-multiple']);

        // Keeps the renderer working if populate was made thanks to a key in renderer_options (backward compatibility)
        if (!empty($this->renderer_options['populate']) && is_callable($this->renderer_options['populate'])) {
            $this->value = $this->renderer_options['populate']($item);
        }

        // Populate the default values
        $populate = '';
        $hiddenName = \Arr::get($this->attributes, 'data-name', $this->options['name']);
        if ($is_multiple) {
            if (!\Str::ends_with($hiddenName, '[]')) {
                $hiddenName .= '[]';
            }
            if (!empty($this->value)) {
                foreach((array) $this->value as $id => $value) {
                    // Get the item title and ID whether $value is a Model
                    if ($value instanceof \Nos\Orm\Model) {
                        $id = $value->implode_pk($value);
                        $label = $value->title_item();
                    } else {
                        $label = $value;
                    }
                    $populate .= '
                        <div class="label-result-autocomplete" data-value="'.$id.'" data-name="'.$hiddenName.'">
                            '.$label.'<span class="delete-label">X</span>
                            <input name="'.$hiddenName.'" type="hidden" value="'.$id.'" />
                        </div>
                    ';
                }
            }
        } else {
            $value = (is_array($this->value) ? reset($this->value) : $this->value);
            if ($value instanceof \Nos\Orm\Model) {
                // Get the primary key whether $value is a Model
                $value = $value->implode_pk($value);
            }
            $populate .= '<input name="'.$hiddenName.'" type="hidden" value="'.$value.'" />';
        }

        // Add the current item ID to the posted vars (used to prevent current item to appear in suggestions)
        $this->set_attribute('data-autocomplete-post', static::json_merge(
            $this->get_attribute('data-autocomplete-post'),
            array('from_id' => $item->implode_pk($item))
        ));

        // Populate the autocomplete search input
        $populate_input = \Arr::get($this->renderer_options, 'populate_input');
        if (is_callable($populate_input)) {
            $this->set_value($populate_input($item));
        } else {
            $this->set_value('');//autocomplete input always empty
        }

        // Add the javascript
        $this->fieldset()->append(static::js_init($this->get_attribute('id'), $this->renderer_options));

        //build without user's template
        $build = $this->template((string) parent::build().$populate);
        $this->template = $this->autocomplete_template;
        return $this->template($build);
    }

    /**
     * Merge array $data into $json string
     *
     * @param $json The json string
     * @param array $data An array to merge
     * @return mixed
     */
    public static function json_merge($json, array $data) {
        $array = \Format::forge($json, 'json')->to_array();
        $array = \Arr::merge($array, $data);
        return \Format::forge($array)->to_json();
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
     * Automatically save values when using the data-autocomplete-model feature
     *
     * @param $item
     * @param $data
     * @return bool
     */
    public function before_save($item, $data)
    {
        $return = parent::before_save($item, $data);

        // Get the field name
        $field_name = \Arr::get($this->attributes, 'data-name', $this->name);

        // Automatically save the value on $item if the model feature is used
        $model = \Arr::get($this->attributes, 'data-autocomplete-model');
        if (!empty($model)) {
            // Check if the property/relation exists
            if (isset($item->{$field_name})) {
                $value = \Arr::get($data, $field_name);
                // If multiple then consider it's a relation
                if (\Arr::get($this->attributes, 'data-multiple')) {
                    $item->{$field_name} = $model::query()
                        ->where(\Arr::get($model::primary_key(), 0), 'IN', (array) $value)
                        ->get();
                }
                // Otherwise consider it's a property
                else {
                    $item->{$field_name} = $value;
                }

                // Return false to avoid the default save on this field
                return false;
            }
        }

        return $return;
    }

    /**
     * Generates the JavaScript to initialise the renderer
     *
     * @param string $id HTML ID attribute of the <input> tag
     * @param array $renderer_options
     * @return \Fuel\Core\View JavaScript to execute to initialise the renderer
     */
    protected static function js_init($id, $renderer_options = array())
    {
        return \View::forge('novius_renderers::autocomplete/js', array(
            'id' => $id,
            'wrapper' => \Arr::get($renderer_options, 'wrapper', ''),
        ), false);
    }
}
