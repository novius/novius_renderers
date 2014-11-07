<?php

namespace Novius\Renderers;

class Renderer_HasMany extends \Nos\Renderer
{
    protected static $DEFAULT_RENDERER_OPTIONS = array(
        'limit' => false,
    );

    public function build()
    {
        $attr_id = $this->get_attribute('id');
        $id = !empty($attr_id) ? $attr_id : uniqid('hasmany_');
        $key = !empty($this->renderer_options['name']) ? $this->renderer_options['name'] : $this->name;
        $relation = !empty($this->renderer_options['related']) ? $this->renderer_options['related'] : $this->name;
        $this->fieldset()->append(static::js_init());
        $return = \View::forge('novius_renderers::hasmany/items', array(
            'id' => $id,
            'key' => $key,
            'relation' => $relation,
            'model' => $this->renderer_options['model'],
            'item' => $this->fieldset()->getInstance(),
            'options' => $this->renderer_options
        ), false)->render();
        return $this->template($return);
    }

    public static function render_fieldset($item, $relation, $index = null, $renderer_options = array())
    {
        static $auto_id_increment = 1;
        $class = get_class($item);
        $config_file = \Config::configFile($class);
        $config = \Config::load(implode('::',$config_file), true);
        $index = \Input::get('index', $index);
        $fieldset = \Fieldset::build_from_config($config['fieldset_fields'], $item, array('save' => false, 'auto_id' => false));
        // Override auto_id generation so it don't use the name (because we replace it below)
        $auto_id = uniqid('auto_id_');
        // Will build hidden fields seperately
        $fields = array();
        foreach ($fieldset->field() as $field) {
            $field->set_attribute('id', $auto_id.$auto_id_increment++);
            if ($field->type != 'hidden' || (mb_strpos(get_class($field), 'Renderer_') != false)) {
                $fields[] = $field;
            }
        }

        $fieldset->populate_with_instance($item);
        $fieldset->form()->set_config('field_template', '<tr><th>{label}</th><td>{field}</td></tr>');
        $view_params = array(
            'fieldset' => $fieldset,
            'fields' => $fields,
            'is_new' => $item->is_new(),
            'index' => $index,
            'options' => $renderer_options,
        );
        $view_params['view_params'] = &$view_params;

        $replaces = array();
        foreach ($config['fieldset_fields'] as $name => $item_config) {
            $replaces['"'.$name.'"'] = '"'.$relation.'['.$index.']['.$name.']"';
        }
        $return = (string) \View::forge('novius_renderers::hasmany/item', $view_params, false)->render();

        \Event::trigger('novius_renderers.hasmany_fieldset');

        \Event::trigger_function('novius_renderers.hasmany_fieldset', array(
            array(
                'item' => &$item,
                'index' => &$index,
                'relation' => &$relation,
                'replaces' => &$replaces
            )
        ));

        return strtr($return, $replaces);
    }

    public static function js_init()
    {
        return \View::forge('novius_renderers::hasmany/js', array(), false);
    }
}