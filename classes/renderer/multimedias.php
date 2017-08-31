<?php

namespace Novius\Renderers;

use Nos\Renderer;

class Renderer_MultiMedias extends Renderer
{
    protected static $DEFAULT_RENDERER_OPTIONS = array(
        'limit' => false,
        'mode'  => 'all',
        'sortable' => false,
    );

    public function build()
    {
        // Gets the unique field ID
        $id = $this->get_attribute('id') ?: uniqid('multimedias_');

        // Appends the javascript part of the renderer
        $this->fieldset()->append(static::js_init($id));

        // Populates the default values
        $item = $this->fieldset()->getInstance();
        if (!empty($item) && empty($this->value)) {
            $this->set_value($this->getValueFromInstance($item));
        }

        $template = $this->template ?: $this->fieldset()->form()->get_config('field_template', "{field} {required}");
        
        $field = (string) \View::forge('novius_renderers::multimedias/inputs', array(
            'id'      => $id,
            'key'     => $this->getInputName(),
            'options' => $this->renderer_options,
            'item'    => $item,
            'value'   => $this->value,
        ), false);
        
        return str_replace(array('{label}', '{field}'), array($this->label, $field), $template);
    }

    /**
     * Before save (populates input values)
     *
     * @param $item
     * @param $data
     * @return bool
     */
    public function before_save($item, $data)
    {
        // If the "key" option is set then skip auto save (for retro-compatibility)
        if (isset($this->renderer_options['key'])) {
            return true;
        }

        $providerKey = $this->getProviderKey();
        $providerName = $this->getProviderName();
        $relationName = $this->getProviderRelationName();

        // Gets the values
        $values = \Input::post($this->getInputName(), \Arr::get($data, $this->getInputName(), array()));
        $values = array_filter($values);

        // Resets the medias
        foreach ($item->{$relationName} as $id => $media) {
            if (preg_match('`^'.preg_quote($providerName).'([0-9]+)$`', $media->medil_key)) {
                $item->{$providerKey}->{$media->medil_key} = null;
                unset($item->{$relationName}[$id]);
            }
        }

        // Sets the medias
        $index = 1;
        foreach ($values as $id) {
            $item->{$providerKey}->{$providerName.$index} = $id;
            $index++;
        }

        return false;
    }

    /**
     * Gets the value from the instance
     *
     * @param $item
     * @param null $key
     * @return array
     */
    protected function getValueFromInstance($item)
    {
        $providerKey = $this->getProviderKey();
        $providerName = $this->getProviderName();
        $value = array();
        for ($index = 1; !empty($item->{$providerKey}->{$providerName.$index}); $index++) {
            $value[$index] = $item->{$providerKey}->{$providerName.$index}->id;
        }
        return $value;
    }

    /**
     * Gets the input name of the field
     *
     * @return mixed
     */
    public function getInputName()
    {
        return \Arr::get($this->renderer_options, 'key', $this->name);
    }

    /**
     * Gets the full provider path (ex. "medias->image")
     *
     * @return string
     */
    protected function getProviderPath()
    {
        // Gets the key if specified
        $key = \Arr::get($this->renderer_options, 'key');
        return !empty($key) ? 'medias->'.$key : $this->name;
    }

    /**
     * Gets the provider key (ex. "medias" for "medias->image")
     *
     * @return mixed
     */
    protected function getProviderKey()
    {
        $path = $this->getProviderPath();
        return \Arr::get(explode('->', $path), 0);
    }

    /**
     * Gets the provider name (ex. "image" for "medias->image")
     *
     * @return mixed
     */
    protected function getProviderName()
    {
        $path = $this->getProviderPath();
        return \Arr::get(explode('->', $path), 1);
    }

    /**
     * Gets the relation name of the provider
     *
     * @return string
     */
    protected function getProviderRelationName()
    {
        return $this->getProviderKey() === 'shared_medias_context' ? 'linked_shared_medias_context' : 'linked_medias';
    }

    /**
     * Returns the javascript part of the renderer
     *
     * @param $id
     * @return \Fuel\Core\View
     */
    public static function js_init($id)
    {
        return \View::forge('novius_renderers::multimedias/js', array(
            'id' => $id,
        ), false);
    }
}
