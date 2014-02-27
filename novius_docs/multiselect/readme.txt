//This multiselect is easy to use as a crud field, see config below :

'objects' => array(
    'renderer' => 'Novius\Renderers\Renderer_Multiselect',
    'label' => __('Label'),
    'form' => array(
        'options' => $options, //a key => value array, the value is displayed, the key is sent
    ),
    'renderer_options' => array(
        'order' => true //This allows to display selected elements in the selected order.
    ),
    'populate' => function($item) {
        if (!empty($item->objects)) {
            return array_keys($item->objects);
        } else {
            return array();
        }
    },
    'before_save' => function($item, $data) {
        $item->objects = array();//fetch the relation
        if (!empty($data['objects'])) {
            $item->objects = \Namespace\Model_Object::query()->where('obj_id', 'in', $data['objects'])->get();
        }
    },
),


//It can also be used in a standalone way :
echo \Novius\Renderers\Renderer_Multiselect::renderer(array(
            'name' => 'objects[]',
            'options' => \Arr::assoc_to_keyval($item->objects, 'obj_id', 'obj_name'),
            'values' => (array) $values,
            'order' => true //This allows to display selected elements in the selected order. Don't work with CRUD configuration
            'style' => array(
                'width' => '70%'
            )
        ));