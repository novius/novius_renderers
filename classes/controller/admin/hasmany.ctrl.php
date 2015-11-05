<?php

namespace Novius\Renderers;

class Controller_Admin_HasMany extends \Nos\Controller_Admin_Application
{
    public function action_add_item($index)
    {
        $class = \Input::get('model');
        $relation = \Input::get('relation');
        $order = \Input::get('order');
        $forge = \Input::get('forge', array());
        $context = \Input::get('context');
        $view = \Input::get('view');
        $duplicate = \Input::get('duplicate', true);

        // Duplicates an item
        if (!empty($forge)) {
            $base_item = $class::forge($forge);//if the forge contains an id, then it will not be considered as a new item
            $item = clone $base_item;
        }
        // Creates a new item
        else {
            $item = $class::forge();
            // Sets the context if provided
            if (!empty($context)) {
                $this->setItemContext($item, $context);
            }
        }

        // Renders the fieldset
        $return = Renderer_HasMany::render_fieldset($item, $relation, $index, array(
            'order'        => (int) $order,
            'content_view' => $view,
            'duplicate'    => $duplicate,
        ));
        \Response::forge($return)->send(true);
        exit();
    }

    /**
     * Sets the $context on $item
     *
     * @param $item
     * @param $context
     * @return bool
     */
    public function setItemContext($item, $context)
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
