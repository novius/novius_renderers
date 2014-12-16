<?php

namespace Novius\Renderers;

use Fuel\Core\Input;

class Controller_Admin_Autocomplete extends \Nos\Controller_Admin_Application
{
    public function prepare_i18n()
    {
        parent::prepare_i18n();
        \Nos\I18n::current_dictionary('novius_renderers::default');
    }

    public function action_search_model() {
        try {
            $results = array();
            $filter = trim(\Input::post('search', ''));
            $class = \Input::post('model', '');
            $from_id = intval(\Input::post('from_id', ''));
            $insert_option = \Input::post('insert_option', false);

            if (!empty($class)) {

                // Check if the current user has the permission to access the model
                list($application) = \Config::configFile($class);
                if (!\Nos\User\Permission::isApplicationAuthorised($application)) {
                    throw new \Nos\Access_Exception('You don\'t have access to application '.$application.'!');
                }

                $table = $class::table();
                $pk_property = \Arr::get($class::primary_key(), 0);
                $title_property = $class::title_property();

                // Create the query
                $query = \Fuel\Core\DB::select(
                    array($pk_property, 'value'),
                    array($title_property, 'label')
                )->from($table);

                // Do not search on the item where the autocomplete appears
                $query->where($pk_property, '!=', $from_id);

                // Apply filter on query
                if (!empty($filter)) {
                    $query->where_open()
                        ->or_where($title_property, 'LIKE', '%' . $filter . '%')
                        ->where_close();
                }

                // Limit (optionnal)
                \Config::load('novius_renderers::renderer/autocomplete', true);
                $max_suggestions = \Config::get('novius_renderers::renderer/autocomplete.max_suggestions', array());
                if (!empty($max_suggestions)) {
                    $query->limit($max_suggestions);
                }

                // Get query results
                $results = array_filter((array) $query->order_by($title_property)
                    ->distinct(true)
                    ->execute()
                    ->as_array()
                );
                if ($insert_option) {
                    array_unshift($results, array(
                        'value' => 0,
                        'label' => __('Add one item')
                    ));
                } else {
                    if (empty($results)) {
                        $results = array(
                            array(
                                'value' => 0,
                                'label' => __('No content of that type has been found')
                            )
                        );
                    }
                }
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

    public function action_call_crud() {
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

        if (Input::method() == 'GET') {
            if (!empty($crud) && !empty($js_id)) {

                //Add a specific event on insert thanks to config
                \Event::register_function('config|'.$config_file, function(&$config) use ($crud, $js_id) {
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
            \Event::register_function('afterSaveCrud|'.$config_file, function(&$json) use ($crud, $js_id) {
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
