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

        $item = $this->fieldset()->getInstance();
        if (!empty($item)) {
            $this->set_value($this->getValueFromInstance($item, $key));
        }

        return (string) \View::forge('novius_renderers::multimedias/inputs', array(
            'id' => $id,
            'key' => $key,
            'options' => $this->renderer_options,
            'item' => $item,
            'value' => $this->value,
        ), false);
    }

    protected function getValueFromInstance($item, $key)
    {
        $value = array();
        $index=1;
        while(!empty($item->medias->{$key.$index})) {
            $media = $item->medias->{$key.$index};
            if (!empty($media)) {
                $value[$index] = $media->id;
                $index++;
            }
        }
        return $value;
    }

    public static function js_init($id)
    {
        return \View::forge('novius_renderers::multimedias/js', array(
            'id' => $id,
        ), false);
    }
}