# Renderer MultiMedias

This renderer allows you to link several medias to an item and sort them (drag'n drop).

## Renderer Options

`mode` The mode of the media picker (_all, image..._)

`sortable` Enables the possibility to sort the linked medias (_true/false_)

## Examples

Here are some examples of CRUD configurations.

__Simple CRUD config :__
```php
'medias->image' => array(
    'label' => 'Simple medias',
    'renderer' => 'Novius\Renderers\Renderer_MultiMedias',
    'renderer_options' => array(),
),
```

__Twinnable CRUD config :__
```php
'shared_medias_context->image' => array(
    'label' => 'Shared medias',
    'renderer' => 'Novius\Renderers\Renderer_MultiMedias',
    'renderer_options' => array(),
),
```

__Twinnable CRUD config with sort and images only :__
```php
'shared_medias_context->image' => array(
    'label' => 'Shared medias (sort + image only)',
    'renderer' => 'Novius\Renderers\Renderer_MultiMedias',
    'renderer_options' => array(
        'sortable' => true,
        'mode' => 'image',
    ),
),
```

__How to use the linked medias (simple) :__

```php
<?php
for ($index = 1; !empty($item->medias->{'image'.$index}); $index++) {
    $media = $item->medias->{'image'.$index};
    // ...
}
```

__How to use the shared linked medias (twinnable) :__

```php
<?php
for ($index = 1; !empty($item->shared_medias_context->{'image'.$index}); $index++) {
    $media = $item->shared_medias_context->{'image'.$index};
    // ...
}
```
