<script type="text/javascript" id="<?= $id ?>">
    require([
        'jquery-nos',
        'https://maps.googleapis.com/maps/api/js?key=<?= e($google_api_key) ?>&libraries=places'
    ], function ($) {
        $(function () {
            // Getting the closest container : has-many item or form
            var $container = $('#<?= $id ?>').closest('.hasmany_content');
            if (!$container || $container.length == 0) {
                $container = $('#<?= $form_id ?>');
            }

            function getComponentFromPlace(place, type) {
                for (i = 0; i < place.address_components.length; i++) {
                    var component = place.address_components[i];
                    for (var j = 0; j < component.types.length; j++) {
                        if (component.types[j] == type) {
                            return component.long_name;
                        }
                    }
                }

                return '';
            }

            function fillFieldsWithPlace(place) {
                var destinationFields = <?= json_encode($destination_fields) ?>;
                for (var field in destinationFields) {
                    var value = '';
                    switch (destinationFields[field]) {
                        case 'address':
                            value = getComponentFromPlace(place, 'street_number')
                                + ' '
                                + getComponentFromPlace(place, 'route');
                            break;
                        case 'latitude':
                            value = place.geometry.location.lat();
                            break;
                        case 'longitude':
                            value = place.geometry.location.lng();
                            break;
                        default:
                            value = getComponentFromPlace(place, destinationFields[field]);
                    }

                    var $field = $container.find('[name="' + field + '"]');
                    if ($field.is(':visible') && value) {
                        $field.val(value);
                    }
                }
            }

            function initGooglePlacesAutocomplete($field, fieldTypes) {
                var autocomplete = new google.maps.places.Autocomplete($field.get(0));
                autocomplete.setTypes(fieldTypes);
                autocomplete.addListener('place_changed', function() {
                    fillFieldsWithPlace(this.getPlace());
                });

                <?php if (!empty($bounds)): ?>
                    autocomplete.setBounds(new google.maps.LatLngBounds(
                        new google.maps.LatLng(
                            <?= var_export($bounds[0][0]) ?>,
                            <?= var_export($bounds[0][1]) ?>
                        ),
                        new google.maps.LatLng(
                            <?= var_export($bounds[1][0]) ?>,
                            <?= var_export($bounds[1][1]) ?>
                        )
                    ));
                <?php endif ?>
            }

            var sourceFields = <?= json_encode($source_fields) ?>;
            for (var field in sourceFields) {
                initGooglePlacesAutocomplete(
                    $container.find('[name="' + field + '"]'),
                    sourceFields[field]
                );
            }
        });
    });
</script>

