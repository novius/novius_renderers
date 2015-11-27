<?php

namespace Novius\Renderers;

class Renderer_HasMany extends \Nos\Renderer
{
    protected static $DEFAULT_RENDERER_OPTIONS = array(
        'limit' => false,
        'content_view' => 'novius_renderers::hasmany/content',
    );

    /**
     * Builds the fieldset
     *
     * @return mixed
     * @throws \Exception
     * @throws \FuelException
     */
    public function build()
    {
        $this->renderer_options = \Arr::merge(static::$DEFAULT_RENDERER_OPTIONS, $this->renderer_options);
        $key = \Arr::get($this->renderer_options, 'name', $this->name);
        $relation = \Arr::get($this->renderer_options, 'related', $this->name);
        $model = \Arr::get($this->renderer_options, 'model');
        $item = $this->fieldset()->getInstance();

        // Checks the model
        if (empty($model) || !is_subclass_of($model, 'Nos\Orm\Model')) {
            throw new \Exception('A valid model must be specified.');
        }

        $data = array();
        $attributes = $this->get_attribute();
        foreach ($attributes as $key => $value) {
            if (mb_strpos($key, 'form-data') === 0) {
                $data[mb_substr($key, strlen('form-'))] = $value;
            }
        }

        // Gets the context
        $context = \Arr::get($this->renderer_options, 'context');
        if (\Arr::get($this->renderer_options, 'inherit_context', true)) {
            // Inherits the context
            $item_context = static::getItemContext($item);
            if (!empty($item_context)) {
                $context = $item_context;
            }
        }

        // Builds the default items
        $default_items = array();
        if (!empty($relation) && !empty($item)) {
            $default_items = $item->{$relation};
        } elseif (!empty($this->value)) {
            // Builds default items from existing values
            if (!empty($this->value)) {
                foreach ($this->value as $elem) {
                    $newModel = $model::forge();
                    foreach ($elem as $property => $elemValue) {
                        $newModel->$property = $elemValue;
                    }
                    $default_items[] = $newModel;
                }
            }
        }
        // Builds a default empty item if there are no default items and if the feature is enabled
        if (empty($default_items) && \Arr::get($this->renderer_options, 'default_item', true)) {
            $default_item = $model::forge();
            \Novius\Renderers\Renderer_HasMany::setItemContext($default_item, $context);
            $default_items = array($default_item);
        }

        // Adds the javascript
        $this->fieldset()->append(static::js_init());

        return $this->template(\View::forge('novius_renderers::hasmany/items', array(
            'id' => $this->getId(),
            'key' => $key,
            'relation' => $relation,
            'model' => $model,
            'item' => $item,
            'default_items' => $default_items,
            'options' => \Arr::merge($this->renderer_options, array(
                'context' => $context,
            )),
            'data' => $data,
        ), false)->render());
    }

    public function before_save($item, $data)
    {
        parent::before_save($item, $data);
        // This part of the code is disabled if the before_save renderer_option is not defined.
        if (!\Arr::get($this->renderer_options, 'before_save')) {
            return true;
        }
        $name = $this->name;

        $values = \Arr::get($data, $name);
        $postData = \Input::post($name);
        $item->$name = array();
        if(empty($values) && empty($postData)) {
            // When the input array is empty (which happens when the user tries to remove all childs),
            // the relation array (array(id => Model)) is given instead, which prevents us to remove the childs from database.
            return true;
        }

        $orderField = \Arr::get($this->renderer_options, 'order_field');
        $orderProperty = \Arr::get($this->renderer_options, 'order_property');
        $model = $this->renderer_options['model'];
        $pk = current($model::primary_key());
        if (empty($pk)) {
            return true;
        }

        foreach ($values as $v) {
            $empty = true;
            // If the item already exists, the primary key is given so we can find it, otherwise we create a new one
            if (!empty($v[$pk])) {
                $subItem = $model::find($v[$pk]);
            } else {
                $subItem = $model::forge();
            }
            unset($v[$pk]);

            // Set the correct value for the order property
            if ($orderField) {
                $subItem->$orderProperty = $v[$orderField];
                unset($v[$orderField]);
            }

            // Fill the model with every value given in POST
            foreach ($v as $property => $value) {
                if (!empty($value)) {
                    $empty = false;
                }
                $subItem->$property = $value;
            }
            // Only add a filled item to the model, this avoid saving an empty item if the default_item option is true
            if (!$empty) {
                // Trigger the before save of all the fields of the has_many
                $config = static::getConfig($subItem, array());
                $fieldset = static::getFieldSet($config, $subItem);
                foreach ($fieldset->field() as $field) {
                    $field->before_save($subItem, $v);
                    $callback = \Arr::get($config, 'fieldset_fields.' . $field->name . '.before_save');
                    if (!empty($callback) && is_callable($callback)) {
                        $callback($subItem, $v);
                    }
                }
                if (!empty($subItem->$pk)) {
                    $item->{$name}[$subItem->$pk] = $subItem;
                } else {
                    $item->{$name}[] = $subItem;
                }
            }
        }
        return true;
    }

    /**
     * Return the fieldset from the config, populated by the item
     * @param $config
     * @param $item
     *
     * @return \Fieldset
     */
    protected static function getFieldSet($config, $item)
    {
        $fieldset = \Fieldset::build_from_config($config['fieldset_fields'], $item, array('save' => false, 'auto_id' => false));
        $fieldset->populate_with_instance($item);
        return $fieldset;
    }

    /**
     * Get the configuration of the form, triggering the novius_renderers.fieldset_config event
     * @param $item
     * @param $data
     *
     * @return array
     */
    protected static function getConfig($item, $data) {
        $class       = get_class($item);
        $config_file = \Config::configFile($class);
        $config      = \Config::load(implode('::', $config_file), true);
        \Event::trigger_function('novius_renderers.fieldset_config', array('config' => &$config, 'item' => $item, 'data' => $data));
        return $config;
    }

    public static function render_fieldset($item, $relation, $index = null, $renderer_options = array(), $data = array())
    {
        $renderer_options = \Arr::merge(static::$DEFAULT_RENDERER_OPTIONS, $renderer_options);
        static $auto_id_increment = 1;
        $index = \Input::get('index', $index);
        $config = static::getConfig($item, $data);
        $fieldset = static::getFieldSet($config, $item);
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

    public function getId() {
        $id = $this->get_attribute('id');
        return !empty($id) ? $id : uniqid('hasmany_');
    }

    /**
     * Gets the context of $item
     *
     * @param $item
     * @return bool
     */
    public static function getItemContext($item) {
        if (!empty($item)) {
            if ($item::behaviours('Nos\Orm_Behaviour_Contextable') || $item::behaviours('Nos\Orm_Behaviour_Twinnable')) {
                return $item->get_context();
            }
        }
        return false;
    }

    /**
     * Sets the $context on $item
     *
     * @param $item
     * @param $context
     * @return bool
     */
    public static function setItemContext($item, $context)
    {
        // Gets the context properties from the behaviour
        $context_properties = \Arr::get(array_values(array_filter($item::behaviours(array(
            'Nos\Orm_Behaviour_Contextable',
            'Nos\Orm_Behaviour_Twinnable'
        )))), 0);
        if (empty($context_properties)) {
            return false;
        }

        // Gets the context property name
        $context_property = \Arr::get($context_properties, 'context_property');
        if (empty($context_property) || !isset($item->{$context_property})) {
            return false;
        }

        // Sets the context
        $item->{$context_property} = $context;

        return true;
    }
}