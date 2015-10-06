# Renderer_ModelSearch

This renderer allow user to search among defined models in order to retrieve its ID.

Default model is Model_Page (defined by novius_renderers/config/renderer/modelsearch.config.php).
Models can be add thks to this very same file, by extending it, or thanks to renderer configuration (see config.sample).

The keys sent by the renderer can be chosen or automatically set to :
    "current model prefix" + "_foreign_id" AND "_foreign_model"

eg : for Nos\BlogNews\News\Model_Post, data sent would be "post_foreign_model" and "post_foreign_id".

Then you have to deal with these field thanks to "populate" and "before_save" methods (see config.sample).

## Sample configuration

```php
'link' => array(
    'label' => __('Link'),
    'renderer' => 'Novius\Renderers\Renderer_ModelSearch',
    'renderer_options' => array(
        'models' => array(
            'Nos\BlogNews\News\Model_Post' => __('News story'),//This will be merged with novius_renderers/config/renderer/modelsearch.config.php
            'Nos\BlogNews\Blog\Model_Post' => __('Blog Post')
        ),
        'names' => array(
            'id' => 'item_foreign_id',//Optional. Default is automatically made with the prefix of the current model
            'model' => 'item_foreign_model',//Optional. Default is automatically made with the prefix of the current model
        )
    ),
    'populate' => function ($item) {
        return array(
            'model' => $item->item_foreign_model,
            'id' => $item->item_foreign_id,
        );
    },
    'before_save' => function ($item, $data) {
        $item->item_foreign_model = '';
        $item->item_foreign_id = 0;
        $model = \Input::post('item_foreign_model', '');
        $search = \Input::post('search', '');
        if (!empty($model) && !empty($search)) {
            $id = \Input::post('item_foreign_id', 0);
            //Only save information if the correspond id/model exists
            if (!empty($id) && !empty($model::find($id))) {
                $item->item_foreign_model = $model;
                $item->item_foreign_id = $id;
            }
        }
    },
)
```

## Configuration with has many renderer

Bellow the config of your CRUD field

```php
'field_key' => array(
    'label' => __('Field name'),
    'renderer' => 'Novius\Renderers\Renderer_HasMany',
    'renderer_options' => array(
        'model' => 'Namespace\Model_YourModel',
        'order' => true,
        'inherit_context' => true
    ),
    'template' => '<div class="custom_class_replace_what_you_want">{field}</div>',
    'before_save' => function($item, $data) {
       // YOUR CODE
    }
),
```

Bellow the config of your model (in config/model/yourmodel.config.php)

```php
return array(
    'fieldset_fields' => array(
        'yourmodelkey_id'          => array(
            'label' => '',
            'form'  => array(
                'type' => 'hidden',
            )
        ),
        'yourmodelkey_order'       => array(
            'label' => '',
            'form'  => array(
                'type' => 'hidden',
            )
        ),
        'yourmodel_relation_key'           => array(
            'label'            => __('Your label'),
            'renderer'         => 'Novius\Renderers\Renderer_ModelSearch',
            'renderer_options' => array(
                'models'       => array(
                    'Namespace\Model_OfItemSearch' => __('Your label'),
                ),
                'autocomplete' => array(
                    'data' => array(
                        'data-autocomplete-post' => array(
                                    'display_method' => 'methodNameCallableWithItem', // Display method has priority over display bellow
                                    'display' => array(
                                      'an_item_field'          => '{{field}}', // Display field
                                      'an_other_item_field'       => '({{field}})', // Display field between parenthesis
                                    ),
                                )
                    )
                ),
                'names'        => array(
                    'id' => 'yourmodelforeignkey_id',
                ),
                'twinnable'    => true,
            ),
            'populate'         => function ($item) {
                    return array(
                        'model' => 'Namespace\Model_OfItemSearch',
                        'id'    => !empty($item->itemsearch) ? $item->itemsearch->id : 0,
                    );
                },
            'before_save'      => function ($item, $data) {
                    $item->id = 0;
                    $id                 = \Input::post('yourmodelforeignkey_id', 0);
                    if (!empty($id)) {
                        $item_search              = Namespace\Model_OfItemSearch::find($id);
                        $item->yourmodelforeignkey_id = $item_search->itemsearch_context_common_id;
                    }
             }
        ),
    ),
);
```
