<?php

namespace Novius\Renderers;

class Renderer_AppdeskPicker extends \Fieldset_Field
{
    protected $renderer_options = array();

    public function build()
    {
        $item = $this->fieldset->getInstance();
        $options = \Arr::get($this->attributes, 'renderer_options', []);

        $selectedItem = null;
        $selectedTypeConfig = null;
        if (!empty($item->{$options['field_id']}) && !empty($item->{$options['field_class']})) {
            $id = $item->{$options['field_id']};
            $class = $item->{$options['field_class']};
            $selectedItem = $class::find($id);

            foreach ($options['models'] as $type) {
                if ($type['model'] == $item->{$options['field_class']}) {
                    $selectedTypeConfig = $type;
                    break;
                }
            }
        }

        $renderer = (string) \View::forge('novius_renderers::appdeskpicker/renderer', array(
            'options' => $options,
            'item' => $item,
            'name' => $this->name,
            'selectedItem' => $selectedItem,
            'selectedTypeConfig' => $selectedTypeConfig,
        ), false);

        return $this->template($renderer);
    }

    public function before_save($item, $data)
    {
        $options = \Arr::get($this->attributes, 'renderer_options', []);
        $item->{$options['field_id']} = \Arr::get($data, $this->name.'.id');
        $item->{$options['field_class']} = \Arr::get($data, $this->name.'.class');
    }
}
