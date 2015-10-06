<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2013 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link       http://www.novius-os.org
 */

namespace Novius\Renderers;

class Renderer_ModelSearch extends \Nos\Renderer
{
    protected static $DEFAULT_RENDERER_OPTIONS = array(
        'names'         => array(
            'id'       => '{{prefix}}foreign_id',
            'model'    => '{{prefix}}foreign_model',
            'external' => '{{prefix}}url',
        ),
        'minlength'     => 3,
        'external'      => false,
        'allow_none'    => true,
        'link_property' => null
    );

    /**
     * Saves the selected items (only if "link_property" is specified in the renderer options)
     *
     * @param $item
     * @param $data
     *
     * @return bool
     */
    public function before_save($item, $data)
    {
        // Gets the renderer options
        $options = $this->getOptions($item);

        // Gets the link property
        $link_property = \Arr::get($options, 'link_property');
        if (empty($link_property)) {
            return true;
        }

        $params = array(
            'link_foreign_model' => array('name' => 'model'),
            'link_foreign_id'    => array('name' => 'id'),
            'link_url'           => array('name' => 'external')
        );

        // Check if link are both identical
        if (!empty($item->$link_property)) {
            $currentLink = \Novius\Link\Model_Link::find($item->$link_property);
            if (!empty($currentLink)) {
                $identical = true;
                foreach ($params as $property => $name) {
                    $key = \Arr::get($options['names'], $name['name']);
                    if (!empty($key) && $currentLink->$property != $data[$key]) {
                        $identical = false;
                        break;
                    }
                }
                if ($identical) {
                    return;
                }
                // Delete the current link to replace it since it's different now
                $currentLink->delete();
                $item->$link_property = null;
            }
        }

        // If we have enough information to create a link, do it
        if ((!empty($data[$options['names']['model']]) && !empty($data[$options['names']['id']]))
            || (empty($data[$options['names']['model']]) && !empty($data[$options['names']['external']]))
        ) {
            // Create a new link
            $newLink = \Novius\Link\Model_Link::forge();
            foreach ($params as $property => $name) {
                $key = \Arr::get($options['names'], $name['name']);
                if (!empty($key) && !empty($data[$key])) {
                    $newLink->$property = $data[$key];
                }
            }
            $newLink->save();
            $item->$link_property = $newLink->link_id;
        }

        return true;
    }

    /**
     * Builds the field
     *
     * @return string
     */
    public function build()
    {
        $attr_id = $this->get_attribute('id');
        $id      = !empty($attr_id) ? $attr_id : uniqid('modelsearch_');

        $item = $this->fieldset()->getInstance();

        // Gets the renderer options
        $options = $this->getOptions($item);

        // Populate values with the linked object
        $link_property = \Arr::get($options, 'link_property');
        if (empty($this->value) && $link_property) {
            $currentLink = \Novius\Link\Model_Link::find($item->$link_property);
            if ($currentLink) {
                $this->value = array(
                    'model'    => $currentLink->link_foreign_model,
                    'id'       => $currentLink->link_foreign_id,
                    'external' => $currentLink->link_url
                );
            }
        }

        // Prepare values
        if (empty($this->value) || !is_array($this->value)) {
            $this->value = array(
                'model'    => '',
                'id'       => 0,
                'external' => ''
            );
            // @todo make the "allow none" option compatible with the "external" option
            if (!(\Arr::get($options, 'allow_none', true) && $options['external'] !== true)) {
                // First available model as default value if external is enabled or none
                reset($options['models']);
                $this->value['model'] = key($options['models']);
            }
        } else {
            // Valid option : (choose not to relate any model)
            //  array(
            //      'model' => '',
            //      'id' => 0,
            //  )
            // => not considered as empty()
            if (empty($this->value['model'])) {
                $this->value['model'] = '';
                \Arr::delete($this->value, 'id');
            }
            if (!array_key_exists('id', $this->value)) {
                $this->value['id'] = 0;
            }
        }

        // Add JS (init sub renderer)
        $this->fieldset()->append(static::js_init());

        return (string)\View::forge('novius_renderers::modelsearch/inputs', array(
            'label'   => $this->label,
            'id'      => $id,
            'value'   => $this->value,
            'item'    => $item,
            'options' => $options
        ), false);
    }

    /**
     * Returns the renderer options for the specified $item
     *
     * @param \Nos\Orm\Model $item
     *
     * @return array
     */
    protected function getOptions(\Nos\Orm\Model $item)
    {
        $options = \Arr::merge(static::$DEFAULT_RENDERER_OPTIONS, $this->renderer_options);

        // Replaces placeholders
        $prefix           = $item::prefix();
        $options['names'] = array_map(function ($value) use ($prefix) {
            return str_replace('{{prefix}}', $prefix, $value);
        }, $options['names']);

        // Sets the available models
        \Arr::set($options, 'models', $this->getAvailableModels($options));

        // Gets the autocomplete configuration
        \Arr::set($options, 'autocomplete', $this->getAutocompleteConfig($options));

        return $options;
    }

    /**
     * Returns the autocomplete configuration
     *
     * @param $options
     *
     * @return array
     */
    public function getAutocompleteConfig($options)
    {
        $item = $this->fieldset()->getInstance();

        // Prepares the autocomplete configuration
        $autocomplete_config = array(
            'available_models' => array_keys($options['models']),
            'use_jayps_search' => (bool)\Arr::get($options, 'use_jayps_search', false),
            'display_method'   => '',
            'display_fields'   => array(),
        );

        // Sets the query args
        $query_args = \Arr::get($options, 'query_args', array());
        if (is_callable($query_args)) {
            $query_args = $query_args($item, $options);
        }
        \Arr::set($autocomplete_config, 'query_args', (array)$query_args);

        // Prepare the autocomplete posted vars
        $autocomplete_post = array();

        // Sets the model in the posted vars if there is only one available
        // @todo we should not have to do this !
        if (count($autocomplete_config['available_models']) == 1) {
            $autocomplete_post['model'] = \Arr::get($autocomplete_config['available_models'], 0);
        }

        // Sets the desired context in the posted vars if the twinnable option is enabled
        if (!empty($options['twinnable'])) {
            // Sets the context from $item
            if ($options['twinnable'] === true) {
                $behaviour_twinnable            = $item::behaviours('Nos\Orm_Behaviour_Twinnable', false);
                $autocomplete_post['twinnable'] = $item->{$behaviour_twinnable['context_property']};
            } // Sets the specified context (eg. if the current model isn't twinnable but the relation is)
            else {
                $autocomplete_post['twinnable'] = $options['twinnable'];
            }
        }

        $autocomplete_options = \Arr::get($options, 'autocomplete', array());
        if (isset($autocomplete_options['data']['data-autocomplete-post'])) {
            $datas = $autocomplete_options['data']['data-autocomplete-post'];
            if (!is_array($datas)) {
                $datas = \Format::forge($datas, 'json')->to_array();
            }
            if (isset($datas['display_method'])) {
                $autocomplete_config['display_method'] = $datas['display_method'];
                unset($datas['display_method']);
            }
            if (isset($datas['display'])) {
                $autocomplete_config['display_fields'] = $datas['display'];
                unset($datas['display_fields']);
            }
            $options['autocomplete']['data']['data-autocomplete-post'] = $datas;
        }


        //$autocomplete_config

        // Sets the autocomplete attributes
        $config = \Arr::merge(array(
            'data' => array(
                'data-autocomplete-cache'     => 'false',
                'data-autocomplete-minlength' => intval(\Arr::get($options, 'minlength')),
                'data-autocomplete-url'       => 'admin/novius_renderers/modelsearch/search',
                'data-autocomplete-callback'  => 'click_modelsearch',
                'data-autocomplete-post'      => $autocomplete_post,
                'data-autocomplete-config'    => $autocomplete_config,
            ),
            // Do not use a wrapper to allow using multiple modelsearch and including only one script
        ), \Arr::get($options, 'autocomplete', array()));

        $config['data']['data-autocomplete-post'] = \Format::forge($config['data']['data-autocomplete-post'])->to_json();

        return $config;
    }

    /**
     * Initializes the javascript
     *
     * @return \Fuel\Core\View
     */
    public static function js_init()
    {
        return \View::forge('novius_renderers::modelsearch/js', array(), false);
    }

    /**
     * Return the available models
     *
     * @param array $options
     *
     * @return array
     */
    public static function getAvailableModels($options = array())
    {
        // Gets the predefined models (eg. Model_Page)
        \Config::load('novius_renderers::renderer/modelsearch', true);
        $models = \Config::get('novius_renderers::renderer/modelsearch.models', array());

        // Gets the custom models
        $models = \Arr::merge($models, \Arr::get($options, 'models', array()));

        // Adds a fake model for handling external link
        if (\Arr::get($options, 'external') === true) {
            $models = \Arr::merge(array('' => __('External')), $models);
        }

        return array_filter($models);
    }

    /**
     * Return the available models
     *
     * @deprecated Use getAvailableModels() instead
     *
     * @param array $options
     *
     * @return array
     */
    public static function get_available_models($options = array())
    {
        return static::getAvailableModels($options);
    }
}
