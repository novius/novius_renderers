=== Introduction ===

This renderer allow to manage creation and update of models that does not have an appdesk and a crud.
A Model_Cinema could handle Film Show (with a specific schedule)
and it would not be interessant to create a specific CRUD for Model_FilmShow.

A more convenient way to handle them is to directly create them in the Model_Cinema CRUD.

It can easily be done thanks to this renderer.

=== Requirements ===

* A model with a has_many relation
* The corresponding related model
* A configuration on this related model, with a "fieldset_fields" key.
  This will contain fields configuration for the related model.

=== Example ===

see config_model.sample & config_crud.sample

=== Note ===

As a field will be added multiple times in the crud, an operation is made to transform names.
For some specific fields (eg hidden field for autocomplete) an event can be used to transform their names.
This event is "novius_renderers.hasmany_fieldset"