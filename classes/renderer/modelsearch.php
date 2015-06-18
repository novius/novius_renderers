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

class Renderer_ModelSearch extends \Nos\Renderer
{
    protected static $DEFAULT_RENDERER_OPTIONS = array(
        'names' => array(
            'id' => '{{prefix}}foreign_id',
            'model' => '{{prefix}}foreign_model',
        ),
        'minlength' => 3,
        'external' => false,
        'link_property' => null
    );

    /**
     * Saves the selected items (only if "link_property" is specified in the renderer options)
     *
     * @param $item
     * @param $data
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
     * Returns the renderer options for the specified $item
     *
     * @param \Nos\Orm\Model $item
     * @return array
     */
    protected function getOptions(\Nos\Orm\Model $item)
    {
        $options = \Arr::merge(static::$DEFAULT_RENDERER_OPTIONS, $this->renderer_options);

        // Replaces placeholders
        $prefix = $item::prefix();
        array_walk($options['names'], function(&$value, $key) use ($prefix) {
            $value = str_replace('{{prefix}}', $prefix, $value);
        });

        return $options;
    }

    /**
     * Builds the field
     *
     * @return string
     */
    public function build()
    {
        $attr_id = $this->get_attribute('id');
        $id = !empty($attr_id) ? $attr_id : uniqid('modelsearch_');

        $item = $this->fieldset()->getInstance();

        // Gets the renderer options
        $options = $this->getOptions($item);
        $available_models = $this->get_available_models($options);
        \Arr::set($options, 'models', $available_models);
        $link_property = \Arr::get($options, 'link_property');

        // Populate values with the linked object
        if (empty($this->value) && $link_property) {
            $currentLink = \Novius\Link\Model_Link::find($item->$link_property);
            if ($currentLink) {
                $this->value = array('model' => $currentLink->link_foreign_model, 'id' => $currentLink->link_foreign_id, 'external' => $currentLink->link_url);
            }
        }

        // Prepare values
        if (empty($this->value) || !is_array($this->value)) {
            // First available model as default value
            reset($available_models);
            $this->value = array(
                'model' => key($available_models),
                'id' => 0,
                'external' => ''
            );
        } else {
            /*
             * Valid option : (choose not to relate any model)
             *  array(
             *      'model' => '',
             *      'id' => 0,
             *  )
             * => not considered as empty()
             */
            if (!array_key_exists('model', $this->value) || empty($this->value['model'])) {
                if ($options['external'] !== true) {
                    $this->value['model'] = 'Nos\Page\Model_Page';
                }
           }
            if (!array_key_exists('id', $this->value)) {
                $this->value['id'] = 0;
            }
        }

        if ($options['external'] === true) {
            $options['models'] = \Arr::merge(array('' => __('External')), $options['models']);
        }


        // Deal with autocomplete configuration
        $post = array(
            'model' => $this->value['model'],
            'use_jayps_search' => (bool) \Arr::get($options, 'use_jayps_search', false),
        );

        // Twinnable ?
        if (!empty($options['twinnable'])) {
            if ($options['twinnable'] === true) {
                $behaviour_twinnable = $item::behaviours('Nos\Orm_Behaviour_Twinnable', false);
                // Will use behaviour configuration to match the right results
                $post['twinnable'] = $item->{$behaviour_twinnable['context_property']};
            } else {
                // Allow custom configuration (eg specific context if the current model isn't twinnable but the relation is)
                $post['twinnable'] = $options['twinnable'];
            }
        }
        $options['autocomplete'] = \Arr::merge(array(
            'data' => array(
                'data-autocomplete-cache' => 'false',
                'data-autocomplete-minlength' => intval(\Arr::get($options, 'minlength')),
                'data-autocomplete-url' => 'admin/novius_renderers/modelsearch/search',
                'data-autocomplete-callback' => 'click_modelsearch',
                'data-autocomplete-post' => \Format::forge($post)->to_json(),
            ),
            // Do not use a wrapper to allow using multiple modelsearch and including only one script
        ), \Arr::get($options, 'autocomplete', array()));

        // Add JS (init sub renderer)
        $this->fieldset()->append(static::js_init());

        return (string) \View::forge('novius_renderers::modelsearch/inputs', array(
            'label' => $this->label,
            'id' => $id,
            'value' => $this->value,
            'item' => $item,
            'options' => $options
        ), false);
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
     * @return array
     */
    public static function get_available_models($options = array()) {
        // Do not assume that Model_Page must always be available, default value is array()
        \Config::load('novius_renderers::renderer/modelsearch', true);
        $models = \Config::get('novius_renderers::renderer/modelsearch.models', array());

        // Custom models
        $models = \Arr::merge($models, \Arr::get($options, 'models', array()));

        return array_filter($models);
    }
}
