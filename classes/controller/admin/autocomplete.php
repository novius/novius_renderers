<?php

namespace Novius\Renderers;

class Controller_Admin_Autocomplete extends \Nos\Controller_Admin_Application
{
    public function action_search_model() {
        try {
            $results = array();
            $filter = trim(\Input::post('search', ''));
            $class = \Input::post('model', '');
            $from_id = intval(\Input::post('from_id', ''));

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
                if (empty($results)) {
                    $results = array(
                        array(
                            'value' => 0,
                            'label' => __('No content of that type has been found')
                        )
                    );
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
}