<?php

namespace Novius\Renderers;

class Controller_Admin_HasMany extends \Nos\Controller_Admin_Application
{
    public function action_add_item($index)
    {
        $class = \Input::get('model');
        $relation = \Input::get('relation');
        $item = $class::forge();
        $params = array(
            'index' => $index,
        );
        $params['item'] = $item;
        $return = Renderer_HasMany::render_fieldset($item, $relation, $index);
        \Response::forge($return)->send(true);
        exit();
    }
}