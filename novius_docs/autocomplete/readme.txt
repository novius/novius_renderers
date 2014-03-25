===== Introduction ================
It was designed to be used as a special Fieldset_Field, and for now, it was not meant to be used outside a form.
The use of the Renderer is therefore related to how it is included in the crud config or included in the View.

===== Configuration ===============

3 kinds of options :
 - 'form' options : used in the construction of the input field, does not concerns the renderer (eg : class)
 - 'renderer_options' -> 'data' key : these are the options used by the js renderer :
        - 'data-autocomplete-url' : mandatory
            url of the controller which performs the search. Called in ajax & must returns json.
        - 'data-autocomplete-callback' : optional
            JS function called when clicking in the autocomplete list. WARNING : it must be a global function!
            The default function uses the label in the list to populate the field.
        - 'data-autocomplete-minlength' : optional
            Numbers of chars needed to perform the search.
            Default value : 3
        - 'data-name' : optional
            Choose a name for the hidden input (and not the one used for the input itself)
            "name" of the field + "-id". eg "field_name-id"
        - 'wrapper' : optional but strongly advised.
            A unique html id used to identify the current autocomplete. Prevent from adding multiple list (each for autocomplete in DOM)
            Becomes mandatory when using "multiple" option (see below).
        - 'multiple' : optional. Default :0.
            If setting to '1', it will display chosen values below the autocomplete field. When using this option, 'wrapper' becomes mandatory.
            WARNING : will be overwritten if a specific callback is used ('data-autocomplete-callback');
- 'renderer_options' -> 'populate_input' : optional
        Allow to populate the input "text"


===== Update options afterward ====

A custom jQuery event can be used to update the url and post content.
New url is retrieved on dom attribute "data-autocomplete-url" in order to be easily read during dev,
whereas the new post content must be retrieved on data as it can be a js object.
See below how to use it :

$input.attr('data-autocomplete-url', new_url);
$input.data('autocomplete-post', new_post);
var event = $nos.Event('update_autocomplete.renderer');
$input.trigger(event);

===== Example =====================

/* In a view */
<?=
    Novius\Renderers\Renderer_Autocomplete::renderer(array(
        'name' => 'field_name',
        'class' => 'class_for_input_field',
        'renderer_options' => array(
            'data' => array(
                'data-autocomplete-url' => 'admin/application/folder/crud/autocomplete',
                //'data-autocomplete-callback' => 'on_click'
            )
        ),
    ));
?>

 /* In a crud config */
    ...
    'fields' => array(
        ...
        'field_name' => array(
            'renderer' => 'Novius\Renderers\Renderer_Autocomplete',
            'form' => array(
                'class' => 'class_for_input_field',
            ),
            'renderer_options' => array(
                'data' => array(
                    'data-autocomplete-url' => 'admin/application/folder/crud/autocomplete',
                    //'data-autocomplete-callback' => 'on_click'
                )
            ),
        ),
    )
    ...