<?php

namespace Novius\Renderers;

class Controller_Admin_ModelSearch extends \Nos\Controller_Admin_Application
{
    public function prepare_i18n()
    {
        parent::prepare_i18n();
        \Nos\I18n::current_dictionary('novius_renderers::default');
    }

    public function action_search() {
        try {
            $results = array();
            $filter = trim(\Input::post('search', ''));
            $class = \Input::post('model', '');

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

                // Apply filter on query
                if (!empty($filter)) {
                    $query->where_open()
                        ->or_where($title_property, 'LIKE', '%' . $filter . '%')
                        ->where_close();
                }

                // Limit (optionnal)
                \Config::load('novius_renderers::renderer/modelsearch', true);
                $limit = \Config::get('novius_renderers::renderer/modelsearch.suggestion_limit', array());
                if (!empty($limit)) {
                    $query->limit($limit);
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