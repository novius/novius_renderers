# Introduction

This renderer allows the user to choose between multiple model types, and then a specific item.
The existing appdesks are reused (thus allowing to search, filter and paginate properly through the existing items).

# Configuration sample (crud field configuration)

```php
    'foo_link' => array( // This name doesn't really matter, only the configured keys below
        'label' => __('Choose an item'),
        'renderer' => \Novius\Renderers\Renderer_AppdeskPicker::class,
        'renderer_options' => [
            'field_id' => 'foo_link_id',
            'field_class' => 'foo_link_class',
            'models' => [
                [
                    'model' => \Nos\Media\Model_Media::class,
                    'appdesk' => 'admin/noviusos_media/appdesk/index/appdesk_pick',
                    'label' => __('Media'),
                ],
                [
                    'model' => \Nos\Slideshow\Model_Slideshow::class,
                    'appdesk' => 'admin/noviusos_slideshow/appdesk/index/appdesk_pick',
                    'label' => __('Slideshow'),
                ],
                [
                    'model' => \Nos\OnlineMediaFiles\Model_Media::class,
                    'appdesk' => 'admin/novius_onlinemediafiles/appdesk/index/appdesk_pick',
                    'label' => __('Online media'),
                ],
                [
                    'model' => \Nos\Page\Model_Page::class,
                    'appdesk' => 'admin/noviusos_page/appdesk/index/appdesk_pick',
                    'label' => __('Page'),
                ],
            ],
        ],
    ),
```
