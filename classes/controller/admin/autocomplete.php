<?php

namespace Novius\Renderers;

use Fuel\Core\Crypt;

class Controller_Admin_Autocomplete extends \Nos\Controller_Admin_Application
{
    public function prepare_i18n()
    {
        parent::prepare_i18n();
        \Nos\I18n::current_dictionary('novius_renderers::default');
    }

    public function before()
    {
        parent::before();

        // Uncrypt the autocomplete configuration
        $config = \Input::post('config');
        if (!empty($config)) {
            $crypt = new Crypt();
            \Arr::set($_POST, 'config', unserialize($crypt->decode($config)));
        }
    }

    public function action_search_model()
    {
        try {
            $config = (array) \Input::post('config', array());

            // The search string
            $search_query = trim(\Input::post('search', ''));

            // The model on which to search
            $model = \Input::post('model', '');

            // The ID of the item we are searching from
            $from_id = intval(\Input::post('from_id', ''));

            // The option to insert a new item
            $insert_option = \Input::post('insert_option', false);

            // The fields on which to search
            $search_field = \Input::post('fields');

            // The fields to display as the label in a result
            $display_field = \Input::post('display');

            $results = array();
            if (!empty($model)) {

                // Check if the $model is available
                $available_models = (array) \Arr::get($config, 'available_models', array());
                if (!in_array($model, $available_models)) {
                    throw new \Exception('This model is not compatible with this feature (not available).');
                }

                // Check if the current user has the permission to access the model
                list($application) = \Config::configFile($model);
                if (!\Nos\User\Permission::isApplicationAuthorised($application)) {
                    throw new \Nos\Access_Exception('You don\'t have access to application '.$application.'!');
                }

                $pk_property = \Arr::get($model::primary_key(), 0);
                $title_property = $model::title_property();

                // Create the base query from the model
                $query = $model::query()->get_query();

                // Adds the fields to display in the query select
                $field_array = array(
                    array($pk_property, 'value'),
                    array($title_property, 'label')
                );
                if (!empty($display_field)) {
                    $keys = array_keys($display_field);
                    foreach ($keys as $key) {
                        $field_array[] = array($key, $key);
                    }
                }

                // Select only the primary key and the title
                $query = $query->select_array($field_array, true);

                // Do not search on the item where the autocomplete appears
                $query->where($pk_property, '!=', $from_id);

                // Apply the search query
                if (!empty($search_query)) {
                    $query->where_open();
                    $search_field = !empty($search_field) ? (array) $search_field : array($title_property);
                    foreach ($search_field as $field) {
                        $query->or_where($field, 'LIKE', '%'.$search_query.'%');
                    }
                    $query->where_close();
                }

                // Limit (optionnal)
                \Config::load('novius_renderers::renderer/autocomplete', true);
                $max_suggestions = \Config::get('novius_renderers::renderer/autocomplete.max_suggestions', array());
                if (!empty($max_suggestions)) {
                    $query->limit($max_suggestions);
                }

                // Gets the query results
                $results = array_filter((array) $query->order_by($title_property)
                    ->distinct(true)
                    ->execute()
                    ->as_array()
                );

                // Formats the results (eg. displayed fields)
                if (!empty($results) && !empty($display_field)) {
                    foreach ($results as $resultKey => $result) {
                        $label = '';
                        foreach ($display_field as $key => $template) {
                            if (!empty($result[$key])) {
                                $label .= ' '.strtr($template, array('{{field}}' => $result[$key]));
                            }
                        }
                        if (!empty($label)) {
                            $results[$resultKey]['label'] = $label;
                        }
                    }
                }

                // Adds an option in the results to insert a new item based on the search query
                if ($insert_option) {
                    array_unshift($results, array(
                        'value' => 0,
                        'label' => __('Add one item')
                    ));
                }
            }

            // Sets a message if there are no results
            if (empty($results)) {
                $results = array(
                    array(
                        'value' => 0,
                        'label' => __('No content of that type has been found')
                    )
                );
            }

            \Response::json($results);
        }

        // Errors
        catch (\Exception $e) {
            \Response::json(array(
                'error' => $e->getMessage(),
            ));
        }
    }

    public function action_call_crud()
    {
        $crud = \Input::param('_crud', false);
        $js_id = \Input::param('_js_id', false);
        $segments = explode('/', $crud);
        //remove "admin"
        array_shift($segments);
        //remove "insert_update"
        array_pop($segments);
        //get app
        $app = array_shift($segments);
        //make config path
        $config_file = $app.'::controller/admin/'.implode('/', $segments);

        if (\Input::method() == 'GET') {
            if (!empty($crud) && !empty($js_id)) {

                //Add a specific event on insert thanks to config
                \Event::register_function('config|'.$config_file, function (&$config) use ($crud, $js_id) {
                    $config['controller_url'] = 'admin/novius_renderers/autocomplete/call_crud';
                    $config['fields']['_crud'] = array(
                        'form' => array(
                            'type' => 'hidden',
                            'value' => $crud,
                        ),
                    );
                    $config['fields']['_js_id'] = array(
                        'form' => array(
                            'type' => 'hidden',
                            'value' => $js_id,
                        ),
                    );
                    //Add a section in layout, which will be invisible as the fields will be hidden
                    array_push($config['layout']['content'], array(
                        'view' => 'nos::form/fields',
                        'params' => array(
                            'fields' => array(
                                '_crud',
                                '_js_id',
                            )
                        ),
                    ));
                });
                //HMVC call
                return \Nos\Nos::hmvc($crud, array());
            }
        } else {
            //HMVC call
            \Event::register_function('afterSaveCrud|'.$config_file, function (&$json) use ($crud, $js_id) {
                unset($json['replaceTab']);
                $json['closeDialog'] = true;
                //Get id in the classic Event
                $id = \Arr::get($json, 'dispatchEvent.0.id', 0);
                //Get class
                $class = \Arr::get($json, 'dispatchEvent.0.name', 0);
                if (!empty($id)) {
                    $item = $class::find($id);
                    //Add custom event just so the autocomplete knows
                    $json['dispatchEvent'][] = array(
                        'name' => 'afterSaveCrud',
                        'is' => $js_id,
                        'id' => $id,
                        'title' => $item->title_item(),
                    );
                }
            });
            \Nos\Nos::hmvc($crud, array());
        }
        exit();
    }
}
