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
use Nos\Config_Common;
use Orm\Model;

class Renderer_Autocomplete extends \Fieldset_Field
{
    protected $renderer_options = array();
    protected $autocomplete_template; //used to store user's template and then applying it on populated values (as tags)

    protected static $DEFAULT_OPTIONS = array(
        'wrapper' => '', //'<div class="datepicker-wrapper"></div>',
    );

    protected static $DEFAULT_ATTRIBUTES = array(
        'data-autocomplete-url' => 'admin/novius_renderers/autocomplete/search_model',//function called by ajax
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
     *n
     * @param $attributes
     * @return array
     */
    public static function create_options(&$attributes)
    {
        $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] : '').' autocomplete';

        // Generate a unique ID for the field if none were defined
        if (empty($attributes['id'])) {
            $attributes['id'] = uniqid('autocomplete_');
            $attributes['data-id'] = $attributes['id'];
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
        } else {
            $post = \Arr::get($attributes, 'renderer_options.data.data-autocomplete-post');
            $post = \Format::forge($post, 'json')->to_array();
            $model = \Arr::get($post, 'model', false);
        }

        if (!empty($model)) {
            $data_insert = \Arr::get($attributes, 'renderer_options.data.data-autocomplete-crud', false);
            if (empty($data_insert)) {
                //Try to determine path for the crud controller (if not set)
                $insert = \Arr::get($attributes, 'renderer_options.insert_option', false);
                if (!empty($insert)) {
                    if (!is_string($insert)) {
                        $application = $model::getApplication();
                        $common_config = Config_Common::load($model);
                        $crud_path = \Arr::get($common_config, 'controller');
                        $insert = $application.DS.$crud_path;
                    }
                    if (!\Str::ends_with($insert, 'insert_update')) {
                        $insert .= DS.'insert_update';
                    }
                    if (!\Str::starts_with($insert, 'admin')) {
                        $insert = 'admin'.DS.$insert;
                    }
                    \Arr::set($attributes, 'renderer_options.data.data-autocomplete-crud', $insert);
                }
            }
            //If insert option is active, add this information in POST
            if (!empty($data_insert) || !empty($insert)) {
                $post = \Arr::get($attributes, 'renderer_options.data.data-autocomplete-post');
                $post = \Format::forge($post, 'json')->to_array();
                $insert_option = \Arr::get($post, 'insert_option', false);
                if (empty($insert_option)) {
                    \Arr::set($attributes, 'renderer_options.data.data-autocomplete-post', \Format::forge(
                        \Arr::merge($post, array('insert_option' => true))
                    )->to_json());
                }
            }
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

        $populate = '';
        $hiddenName = \Arr::get($this->attributes, 'data-name', $this->options['name']);

        $is_multiple = !empty($this->attributes['data-multiple']);

        // Get the current fieldset item
        $item = $this->fieldset()->getInstance();

        if (!empty($item)) {
            // Add the current item ID to the posted vars (used to prevent current item to appear in suggestions)
            $this->set_attribute('data-autocomplete-post', static::json_merge(
                $this->get_attribute('data-autocomplete-post'),
                array('from_id' => $item->implode_pk($item))
            ));
        }

        // Keeps the renderer working if populate was made thanks to a key in renderer_options (backward compatibility)
        if (!empty($this->renderer_options['populate']) && is_callable($this->renderer_options['populate'])) {
            $this->value = $this->renderer_options['populate']($item);
        }

        // Populate multiple values
        if ($is_multiple) {
            if (!\Str::ends_with($hiddenName, '[]')) {
                $hiddenName .= '[]';
            }
            if (!empty($this->value)) {
                foreach((array) $this->value as $id => $value) {
                    // Get the item title and ID whether $value is a Model
                    $label = $value;
                    if ($value instanceof \Nos\Orm\Model) {
                        $id = $value->implode_pk($value);
                        $label = $value->title_item();
                    }
                    // Generate the hidden field
                    $populate .= '
                        <div class="label-result-autocomplete" data-value="'.$id.'" data-name="'.$hiddenName.'">
                            '.$label.'<span class="delete-label">X</span>
                            <input name="'.$hiddenName.'" type="hidden" value="'.$id.'" />
                        </div>
                    ';
                }
            }
        }
        // Populate single value
        else {
            $value = (is_array($this->value) ? reset($this->value) : $this->value);
            if ($value instanceof \Nos\Orm\Model) {
                // Set the title as input value
                $this->set_value($value->title_item());
                // Set the primary key as value
                $value = $value->implode_pk($value);
            } else {
                $this->set_value($value);
            }
            // Generate the hidden field
            $populate .= '<input name="'.$hiddenName.'" type="hidden" value="'.$value.'" />';
        }

        // Remove the field's name attribute to avoid conflicts with the hidden field
        $this->name = null;

        // Populate the autocomplete search input
        $populate_input = \Arr::get($this->renderer_options, 'populate_input');
        if (is_callable($populate_input)) {
            $this->set_value($populate_input($item));
        } elseif ($is_multiple) {
            // The autocomplete input must be empty for multiple values
            $this->set_value('');
        }

        // Add the javascript
        $this->fieldset()->append(static::js_init($this->get_attribute('data-id'), $this->renderer_options));

        // Build the field followed by the populated values
        $field = $this->build_without_template().$populate;

        // Apply the original template on the field
        $field = $this->template($field);

        return $field;
    }

    /**
     * Build the field without template
     *
     * @return mixed
     */
    public function build_without_template() {
        $original_template = $this->template;
        $this->template = '{field}';
        $field = $this->template(parent::build());
        $this->template = $original_template;
        return $field;
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
        return '<input '.array_to_attr($attributes).' />'.static::js_init($attributes['data-id'], $renderer_options);
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

        $is_multiple = \Arr::get($this->attributes, 'data-multiple');

        // Automatically save the value on $item if the model feature is used
        $model = \Arr::get($this->attributes, 'data-autocomplete-model');
        if (!empty($model)) {
            // Check if the property/relation exists
            if (isset($item->{$field_name})) {
                // Get the posted value(s)
                $value = \Input::post($field_name);
                if ($is_multiple) {
                    $value = (!is_array($value) ? array($value) : $value);
                } else {
                    $value = (is_array($value) ? reset($value) : $value);
                }
                // Save the value(s) in a relation
                if ($item->relations($field_name)) {
					$item->{$field_name} = array();
                    if (!empty($value)) {
                        $items = $item->{$field_name} = $model::query()
                            ->where(\Arr::get($model::primary_key(), 0), 'IN', (array) $value)
                            ->get();
						foreach ($value as $pk) {
							$item->{$field_name}[] = $items[$pk];
						}
                    }
                }
                // Save the value(s) in a property
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
