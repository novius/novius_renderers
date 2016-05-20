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
     * @return mixed|string
     */
    public function build()
    {
        $this->renderer_options = \Arr::merge(static::$DEFAULT_RENDERER_OPTIONS, $this->renderer_options);
        $key = !empty($this->renderer_options['name']) ? $this->renderer_options['name'] : $this->name;
        $relation = !empty($this->renderer_options['related']) ? $this->renderer_options['related'] : $this->name;

        $data = array();
        $attributes = $this->get_attribute();
        foreach ($attributes as $key => $value) {
            if (mb_strpos($key, 'form-data') === 0) {
                $data[mb_substr($key, strlen('form-'))] = $value;
            }
        }

        // Gets the fieldset item
        $item = $this->fieldset()->getInstance();
        $context = $this->getItemContext($item);
        if (!empty($this->renderer_options['context'])) {
            $context = $this->renderer_options['context'];
        }

        // Adds the javascript
        $this->fieldset()->append(static::js_init());

        $return = \View::forge('novius_renderers::hasmany/items', array(
            'id' => $this->getId(),
            'key' => $key,
            'relation' => $relation,
            'value' => $this->value,
            'model' => $this->renderer_options['model'],
            'item' => $item,
            'context' => $context,
            'options' => $this->renderer_options,
            'data' => $data
        ), false)->render();
        return $this->template($return);
    }

    /**
     * Renders the fieldset
     *
     * @param $item
     * @param $relation
     * @param null $index
     * @param array $renderer_options
     * @param array $data
     * @return string
     * @throws \FuelException
     */
    public static function render_fieldset($item, $relation, $index = null, $renderer_options = array(), $data = array())
    {
        $renderer_options = \Arr::merge(static::$DEFAULT_RENDERER_OPTIONS, $renderer_options);
        static $auto_id_increment = 1;
        $index = \Input::get('index', $index);
        $config = static::getConfig($item, $data, $renderer_options);
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

    /**
     * Automatically saves the related item
     *
     * @param $item
     * @param $data
     * @return bool
     */
    public function before_save($item, $data)
    {
        parent::before_save($item, $data);

        // Checks if auto save is enabled
        if (\Arr::get($this->renderer_options, 'dont_save', !\Arr::get($this->renderer_options, 'before_save'))) {
            return true;
        }

        // Gets the relation properties
        $model = $this->renderer_options['model'];
        $modelPks = $model::primary_key();
        $modelPk = current($model::primary_key());
        $modelContextField = $this->getItemContextField($model);
        $modelContextCommonField= $this->getItemContextCommonField($model);
        $modelContextMainField = $this->getItemContextMainField($model);
        $relationName = $this->name;
        $relation = $item->relations($relationName);

        // Gets the new relation data
        $values = \Arr::get($data, $relationName);
        $postData = \Input::post($relationName);

        $isPopulatedWithItem = !empty($values) && is_object(current($values));
        $isRelationTwinnable = is_a($relation, 'Nos\\Orm_Twinnable_HasMany');

        // Sort/order properties
        $orderProperty = \Arr::get($this->renderer_options, 'order_property');
        $orderField = \Arr::get($this->renderer_options, 'order_field', $orderProperty);

        // Gets the item context
        $itemContext = $isRelationTwinnable ? \Input::post($this->getItemContextField($item), $this->getItemContext($item)) : null;

        // Initializes the related items
        if ($isRelationTwinnable && !empty($item->{$relationName})) {
            // Removes the related items in the current context
            $item->{$relationName} = array_filter($item->{$relationName}, function($relatedItem) use ($modelContextField, $itemContext) {
                return $relatedItem->{$modelContextField} != $itemContext;
            });
        } else {
            // Resets the related items
            $item->{$relationName} = array();
        }

        if (empty($modelPks) || empty($values) || ($isPopulatedWithItem && empty($postData))) {
            // When the input array is empty (which happens when the user tries to remove all childs),
            // the relation array (array(id => Model)) is given instead, which prevents us to remove the childs from database.
            return true;
        }

        // The fields that will not be set on the related items
        $ignored_fields = \Arr::merge($modelPks, array(
            $modelPk,
            $modelContextCommonField,
            $modelContextMainField,
            $orderField
        ));

        // Creates/updates the related items
        foreach ($values as $v) {

            // Searches the related item
            $relatedItem = null;
            $relatedItemId = \Arr::get($v, $modelPk);
            if (!empty($relatedItemId)) {

                // Queries the database
                $relatedItem = $model::query()
                    ->where($modelPk, '=', $relatedItemId)
                    ->get_one();

                // If the context of the related item differs from the context of the item then creates a new one
                if (!empty($relatedItem) && $isRelationTwinnable && $relatedItem->{$modelContextField} != $itemContext) {
                    $relatedItemCommonId = $relatedItem->{$modelContextCommonField};
                    // Forges a new related item
                    $relatedItem = $model::forge();
                    // Sets the common id from the original related item
                    $relatedItem->{$modelContextCommonField} = $relatedItemCommonId;
                }
            }

            // Creates a new one if no related item found
            if (empty($relatedItem)) {
                $relatedItem = $model::forge();
            }

            // If twinnable then set the context from the item
            if ($isRelationTwinnable) {
                $relatedItem->{$modelContextField} = $itemContext;
            }

            // Sets the correct value for the order property
            if (!empty($orderField)) {
                $relatedItem->{$orderProperty} = \Arr::get($v, $orderField);
                unset($v[$orderField]);
            }

            // Sets the properties values
            $filled_values = 0;
            foreach ($v as $field => $value) {
                // Checks if the field has to be ignored
                if (in_array($field, $ignored_fields)) {
                    continue;
                }
                $relatedItem->{$field} = $value;
                if (!empty($value)) {
                    $filled_values++;
                }
            }

            // Skip this related item if no values were filled, to avoid saving an empty item
            if (empty($filled_values)) {
                continue;
            }

            // Trigger the before save of all the fields of the has_many
            $config = static::getConfig($relatedItem, array());
            $fieldset = static::getFieldSet($config, $relatedItem, $this->renderer_options);
            foreach ($fieldset->field() as $field) {
                $field->before_save($relatedItem, $v);
                $callback = \Arr::get($config, 'fieldset_fields.'.$field->name.'.before_save');
                if (!empty($callback) && is_callable($callback)) {
                    $callback($relatedItem, $v);
                }
            }

            // Adds the related item
            $item->{$relationName}[] = $relatedItem;
        }

        return false;
    }

    public static function js_init()
    {
        return \View::forge('novius_renderers::hasmany/js', array(), false);
    }

    /**
     * Gets the relation name
     *
     * @return mixed
     */
    public function getRelationName()
    {
        return \Arr::get($this->renderer_options, 'related', $this->name);
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
    protected static function getConfig($item, $data, &$renderer_options)
    {
        $class       = get_class($item);
        $config_file = \Config::configFile($class);
        $config      = \Config::load(implode('::', $config_file), true);

        // fallback old
        if (\Arr::get($renderer_options, 'order', false) && empty(\Arr::get($renderer_options, 'order_property', false))) {
            foreach (\Arr::get($config, 'fieldset_fields') as $k => $field) {
                if (preg_match('/(.*)_order(.*)/', $k)) {
                    \Arr::set($renderer_options, 'order_property', true);
                    break;
                 }
             }
        }

        \Event::trigger_function('novius_renderers.fieldset_config', array('config' => &$config, 'item' => $item, 'data' => $data));
        return $config;
    }

    /**
     * Gets or generates the renderer unique id
     *
     * @return array|mixed|string
     */
    public function getId()
    {
        $id = $this->get_attribute('id');
        return !empty($id) ? $id : uniqid('hasmany_');
    }

    /**
     * Returns the context of the specified $item
     *
     * @param $item
     * @return bool
     */
    public function getItemContext($item)
    {
        if (empty($item)) {
            return false;
        }
        if (!$item::behaviours('Nos\Orm_Behaviour_Contextable') && !$item::behaviours('Nos\Orm_Behaviour_Twinnable')) {
            return false;
        }
        return $item->get_context();
    }

    /**
     * Returns the field name of the context for the specified $model
     *
     * @param $model
     * @return bool
     */
    public function getItemContextField($model)
    {
        $field = \Arr::get($this->getItemContextProperties($model), 'context_property');
        return is_array($field) ? current($field) : $field;
    }

    /**
     * Returns the field name of the context common id for the specified $model
     *
     * @param $model
     * @return bool
     */
    public function getItemContextCommonField($model)
    {
        $field = \Arr::get($this->getItemContextProperties($model), 'common_id_property');
        return is_array($field) ? current($field) : $field;
    }

    /**
     * Returns the field name of the context common id for the specified $model
     *
     * @param $model
     * @return bool
     */
    public function getItemContextMainField($model)
    {
        $field = \Arr::get($this->getItemContextProperties($model), 'is_main_property');
        return is_array($field) ? current($field) : $field;
    }

    /**
     * Returns the context properties of the specified $model
     *
     * @param $model
     * @return array
     */
    public function getItemContextProperties($model)
    {
        return (array) $model::behaviours('Nos\Orm_Behaviour_Twinnable') ?: array();
    }
}
