<?php

namespace Novius\Renderers;

use Nos\Renderer;

class Renderer_MultiMedias extends Renderer
{
    protected static $DEFAULT_RENDERER_OPTIONS = array(
        'limit' => false,
        'mode' => 'all',
    );

    public function build()
    {
        $attr_id = $this->get_attribute('id');
        $id = !empty($attr_id) ? $attr_id : uniqid('multimedias_');
        $this->fieldset()->append(static::js_init($id));
        $key = !empty($this->renderer_options['key']) ? $this->renderer_options['key'] : $this->name;
        return (string) \View::forge('novius_renderers::multimedias/inputs', array(
            'id' => $id,
            'key' => $key,
            'item' => $this->fieldset()->getInstance(),
            'options' => $this->renderer_options
        ), false);
    }

    public static function js_init($id)
    {
        return \View::forge('novius_renderers::multimedias/js', array(
            'id' => $id,
        ), false);
    }
}