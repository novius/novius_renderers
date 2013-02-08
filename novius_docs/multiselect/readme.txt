This multiselect is easy to use as a crud field, see config below :

'objects' => array(
    'renderer' => 'Lib\Renderers\Renderer_Multiselect',
    'label' => __('Label'),
    'form' => array(
        'options' => $options, //a key => value array, the value is displayed, the key is sent
    ),
    'populate' => function($item) {
        if (!empty($item->objects)) {
            return array_keys($item->objects);
        } else {
            return array();
        }
    },
    'before_save' => function($item, $data) {
        $item->objects;//fetch the relation
        unset($item->objects);//remove all the objects and only set the objects which have been sent
        if (!empty($data['objects'])) {
            foreach ($data['objects'] as $object_id) {
                if (ctype_digit($object_id) ) {
                    $item->objects[$depa_id] = \Namespace\Model_Object::find($object_id);
                }
            }
        }
    },
),