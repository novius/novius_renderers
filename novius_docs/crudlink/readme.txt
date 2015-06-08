=== Introduction ===

This renderer gives the ability to display a link to a model's crud.

=== Configuration ===

You need to populate the field with an instance of Nos\Orm\Model. The link to the correct crud will be displayed if the crud exists.

==== renderer_options ====

text : default ('See the {{MODEL}}\'s page')

The text displayed inside the link.

=== Example ===

```php
'link'                       => array(
    'label'            => __('Crud Link'),
    'renderer'         => '\Novius\Renderers\Renderer_CrudLink',
    'renderer_options' => array(),
    'populate'         => function ($item) {
                return $item->related_item;
        },
    'show_when'        => function ($item) {
            return (!empty($item->related_item);
        }
),
```
