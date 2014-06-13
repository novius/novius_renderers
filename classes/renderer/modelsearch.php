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
        )
    );

    public function build()
    {
        $attr_id = $this->get_attribute('id');
        $id = !empty($attr_id) ? $attr_id : uniqid('modelsearch_');

        //Add JS (init sub renderer)
        $this->fieldset()->append(static::js_init());

        //Prepare values
        if (empty($this->value) || !is_array($this->value)) {
            //value must contain model name and ID
            $this->value = array(
                'model' => 'Nos\Page\Model_Page',//Page is the default related model
                'id' => 0
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
            if (!array_key_exists('model', $this->value)) {
                $this->value['model'] = 'Nos\Page\Model_Page';
            }
            if (!array_key_exists('id', $this->value)) {
                $this->value['id'] = 0;
            }
        }

        $item = $this->fieldset()->getInstance();

        //Prepare options
        $options = \Arr::merge(static::$DEFAULT_RENDERER_OPTIONS, $this->renderer_options);
        \Config::load('novius_renderers::renderer/modelsearch', true);
        //Do not assume that Model_Page must always be available, default value is array()
        $default_models = \Config::get('novius_renderers::renderer/modelsearch.models', array());
        $options['models'] = !empty($options['models']) ? \Arr::merge($default_models, (array) $options['models']) : $default_models;

        //Format options
        $class = get_class($item);
        $prefix = $class::prefix();
        array_walk($options['names'], function(&$value, $key) use ($prefix) {
            $value = str_replace('{{prefix}}', $prefix, $value);
        });

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
}