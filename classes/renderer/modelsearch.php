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

    public function before_save($item, $data)
    {
        $options = $this->getOptions();
        $link_property = \Arr::get($options, 'link_property');

        if ($link_property) {
            $newLink = null;
            $params = array(
                'link_foreign_model' => array('name' => 'model'),
                'link_foreign_id'    => array('name' => 'id'),
                'link_url'           => array('name' => 'external')
            );

            // Check if link are both identical
            if (!empty($item->$link_property)) {
                $currentLink = \Novius\Link\Model_Link::find($item->$link_property);
                $identical = true;
                foreach ($params as $property => $name) {
                    if ($currentLink->$property != $data[$options['names'][$name['name']]]) {
                        $identical = false;
                        break;
                    }
                }
                if ($identical) {
                    return;
                }
                // Delete the current link to replace it since it's different now
                $currentLink->delete();
                $item->$link_property = NULL;
            }

            // If we have enough information to create a link, do it
            if ((!empty($data[$options['names']['model']]) && !empty($data[$options['names']['id']]))
                || (empty($data[$options['names']['model']]) && !empty($data[$options['names']['external']]))
            ) {
                // Create a new link
                $newLink = \Novius\Link\Model_Link::forge();
                foreach ($params as $property => $name) {
                    if (!empty($data[$options['names'][$name['name']]])) {
                        $newLink->$property = $data[$options['names'][$name['name']]];
                    }
                }
                $newLink->save();
                $item->$link_property = $newLink->link_id;
            }
        }

        return true;
    }
    
    private function getOptions()
    {
        $options = \Arr::merge(static::$DEFAULT_RENDERER_OPTIONS, $this->renderer_options);
        $item = $this->fieldset()->getInstance();
        //Format options
        $class = get_class($item);
        $prefix = $class::prefix();
        array_walk($options['names'], function(&$value, $key) use ($prefix) {
            $value = str_replace('{{prefix}}', $prefix, $value);
        });
        return $options;
    }
    

    public function build()
    {
        $attr_id = $this->get_attribute('id');
        $id = !empty($attr_id) ? $attr_id : uniqid('modelsearch_');

        $item = $this->fieldset()->getInstance();

        // Prepare options
        $options = $this->getOptions();
        $available_models = $this->get_available_models($options);
        \Arr::set($options, 'models', $available_models);
        $link_property = \Arr::get($options, 'link_property');

        // Populate values with the linked object
        if (empty($this->value) && $link_property) {
            $currentLink = \Novius\Link\Model_Link::find($item->$link_property);
            $this->value = array('model' => $currentLink->link_foreign_model, 'id' => $currentLink->link_foreign_id, 'external' => $currentLink->link_url);
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


        //Add JS (init sub renderer)
        $this->fieldset()->append(static::js_init());

        return (string) \View::forge('novius_renderers::modelsearch/inputs', array(
            'label' => $this->label,
            'id' => $id,
            'value' => $this->value,
            'item' => $item,
            'options' => $options
        ), false);
    }

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