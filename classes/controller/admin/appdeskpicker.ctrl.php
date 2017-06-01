<?php

namespace Novius\Renderers;

class Controller_Admin_AppdeskPicker extends \Nos\Controller_Admin_Application
{
    public function action_main($edit = false)
    {
        $models = json_decode(\Crypt::decode((string) \Input::get('models')), true);
        if (empty($models)) {
            return null;
        }

        return \View::forge('novius_renderers::appdeskpicker/main', array(
            'models' => $models,
        ), false);
    }
}
