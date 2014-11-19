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
            // Get the search keywords
            $keywords = trim(strval(\Input::post('search', '')));

            // Get the target model
            $class = \Input::post('model', '');
            if (empty($class) or !class_exists($class)) {
                throw new \Exception('Could not find this model.');
            }

            // Check if the current user has the permission to access the model
            list($application) = \Config::configFile($class);
            if (!\Nos\User\Permission::isApplicationAuthorised($application)) {
                throw new \Nos\Access_Exception('You don\'t have access to application '.$application.'!');
            }

            $query_args = array();

            $pk_property = \Arr::get($class::primary_key(), 0);
            $title_property = $class::title_property();
            if (empty($title_property)) {
                throw new \Exception('Cannot search on this model.');
            }

            // Search on keywords if jayps_search is configured on the model
            $searchable = $class::behaviours('JayPS\Search\Orm_Behaviour_Searchable');
            if (!empty($keywords) && !empty($searchable)) {
                $query_args['where'][] = array('keywords', $keywords.'*');
            }

            // Create the base query from the model
            $query = $class::query($query_args)->get_query();

            // Select only the primary key and the title
            $query = $query->select_array(array(
                array($pk_property, 'value'),
                array($title_property, 'label')
            ), true);

            // Search on title if jayps_search is not configured on the model
            if (!empty($keywords) && empty($searchable)) {
                $query->where_open()
                    ->or_where($title_property, 'LIKE', '%'.$keywords.'%')
                    ->where_close();
            }

            // Limit (optionnal)
            \Config::load('novius_renderers::renderer/modelsearch', true);
            $limit = \Config::get('novius_renderers::renderer/modelsearch.suggestion_limit', array());
            if (!empty($limit)) {
                $query->limit($limit);
            }

            // Order by title
            $query->order_by($title_property);

            // Get query results
            $results = $query
                ->order_by($title_property)
                ->distinct(true)
                ->execute()
                ->as_array()
            ;
            $results = array_filter((array) $results);

            if (!empty($results)) {
                $results = array_map(function($result) {
                    return \Arr::subset($result, array('label', 'value'));
                }, $results);
            } else {
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
}