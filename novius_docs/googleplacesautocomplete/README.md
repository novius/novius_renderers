# Google Places Autocomplete renderer

This renderer allows you to get an autocompletion with Google Places on any field.

This is not intended to render an input field, but instead to act like a plugin that will modify other existing fields.

Configuration sample:

```php

$googleApiKey = /* Get it from whereever you want... */;

return [
    'fields => [
        'google_places_autocomplete' => [
            'renderer' => \Novius\Renderers\Renderer_GooglePlacesAutocomplete::class,
            'renderer_options' => [
                'google_api_key' => $googleApiKey,
                'source_fields' => [
                    /**
                     * Those fields are the one that will trigger the autocompletion when the user types something in it.
                     * The key is the field `name`, and the value matches the `types` argument of Google Places:
                     * https://developers.google.com/places/supported_types#table3
                     */
                    'foo_address'   => ['address'],
                    'foo_post_code' => ['(regions)'],
                    'foo_city'      => ['(cities)'],
                    'foo_country'   => ['(regions)'],
                ],
                'destination_fields' => [
                    /**
                     * When the user selects something in the autocompletion, those fields will be filled with the information extracted from the `Place` object from the API. The attribute that will be used to fill the field can be:
                     *     - `address`: Concatenation of the `street_number` and `route`
                     *     - `latitude`
                     *     - `longitude`
                     *     - Any documented type: https://developers.google.com/places/supported_types#table2
                     */
                    'foo_address'   => 'address',
                    'foo_post_code' => 'postal_code',
                    'foo_city'      => 'locality',
                    'foo_country'   => 'country',
                    'foo_latitude'  => 'latitude',
                    'foo_longitude' => 'longitude',
                ],
                'bounds' => [
                    // (optional) Matches the `bounds` attribute from the API. As it says:
                    // "Sets the preferred area within which to return Place results."
                    // "Results are biased towards, but not restricted to, this area."
                    [41.606863, -4.632769], // South-west point
                    [59.068768, 16.464692], // North-east point
                ],
            ],
        ],
        'foo_address' => [
            'label' => __('Adress'),
        ],
        'foo_post_code' => [
            'label' => __('Postal code'),
        ],
        'foo_city' => [
            'label' => __('City'),
        ],
        'foo_country' => [
            'label' => __('Country'),
        ],
        'foo_latitude' => [
            'label' => __('Latitude'),
            'form' => [
                'disabled',
            ],
        ],
        'foo_longitude' => [
            'label' => __('Longitude'),
            'form' => [
                'disabled',
            ],
        ],
    ],
];
```
