=== Introduction ===

This renderer gives the ability to manage the creation and the update of models inside the CRUD of another model.

For example a Model_Movie could handle shows with a specific schedule, because the show is specific to the movie it
could be interresting to create it directly in the movie's CRUD

because a show
and it would not be interessant to create a specific CRUD for Model_MovieShow.



A more convenient way to handle them is to directly create them in the Model_Cinema CRUD.

It can easily be done thanks to this renderer.

=== Requirements ===

* A model with a `has_many` relation :

    protected static $_has_many  = array(
        'shows' => array(
            'key_from'         => 'movie_id',
            'key_to'           => 'show_movie_id',
            'cascade_save'     => true,
            'cascade_delete'   => true,
            'model_to'         => 'Model_MovieShow',
        ),
    );

    You must set `cascade_save` and `cascade_delete` to true.

* The corresponding related model with a belongs_to relation :


    protected static $_belongs_to  = array(
        'movie' => array( // key must be defined, relation will be loaded via $mentine->key
            'key_from'      => 'show_id', // Column on this model
            'key_to'        => 'movie_id', // Column on the other model
            'cascade_save'  => false,
            'cascade_delete'=> false,
            'model_to'      => 'Model_Movie', // Model to be defined
        ),
    );

* A configuration on this related model, with a "fieldset_fields" key.
  This will contain fields configuration for the related model.

=== Example ===

see config_model.sample & config_crud.sample

=== Note ===

As a field will be added multiple times in the crud, an operation is made to transform names.
For some specific fields (eg hidden field for autocomplete) an event can be used to transform their names.
This event is "novius_renderers.hasmany_fieldset"