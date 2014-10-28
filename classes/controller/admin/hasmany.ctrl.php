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
        if (!empty($forge)) {
            $base_item = $class::forge($forge);//if the forge contains an id, then it will not be considered as a new item
            $item = clone $base_item;
        } else {
            $item = $class::forge();
        }

        $params = array(
            'index' => $index,
        );
        $params['item'] = $item;
        $return = Renderer_HasMany::render_fieldset($item, $relation, $index, array('order' => (int)$order));
        \Response::forge($return)->send(true);
        exit();
    }
}