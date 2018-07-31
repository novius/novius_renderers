<?php

namespace Novius\Renderers;

class Renderer_GooglePlacesAutocomplete extends \Fieldset_Field
{
    public function build()
    {
        parent::build();

        $this->template = '{field}';
        $options = \Arr::get($this->attributes, 'renderer_options', []);

        $view = 'novius_renderers::google_places_autocomplete/js';
        $options = \Arr::merge($options, [
            'id' => $this->get_attribute('id'),
            'form_id' => $this->fieldset->get_config('form_attributes.id'),
        ]);

        return $this->template(\View::forge($view, $options, false)->render());
    }
}
