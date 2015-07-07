<?php

namespace Novius\Renderers;

use Fuel\Core\Crypt;

class Controller_Admin_ModelSearch extends \Nos\Controller_Admin_Application
{
    public function prepare_i18n()
    {
        parent::prepare_i18n();
        \Nos\I18n::current_dictionary('novius_renderers::default');
    }

    public function action_search() {
        try {
            // Uncrypt sensitive data and merge them to the post values
            $cryptedPosts = \Input::post('crypted_post');
            if (!empty($cryptedPosts)) {
                $crypt = new Crypt();
                $cryptedPosts = json_decode($crypt->decode($cryptedPosts), true);
                $_POST = \Arr::merge_assoc($_POST, $cryptedPosts);
            }
            // Get the search keywords
            $keywords = trim(strval(\Input::post('search', '')));

            // Use jayps_search ?
            $use_jayps_search = \Input::post('use_jayps_search', false);
            $use_jayps_search = (!empty($use_jayps_search) and $use_jayps_search != 'false');

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
            $title_property = method_exists($class, 'search_property') ? $class::search_property() : $class::title_property();
            if (empty($title_property)) {
                throw new \Exception('Cannot search on this model.');
            }

            // Search on keywords if jayps_search is enabled
            $searchable = $class::behaviours('JayPS\Search\Orm_Behaviour_Searchable');
            if (!empty($use_jayps_search) && !empty($keywords) && !empty($searchable)) {
                $query_args['where'][] = array('keywords', $keywords.'*');
            }

            // Create the base query from the model
            $query = $class::query($query_args);

            // Will select only the primary key and the title
            $select = array(
                array($pk_property, 'value'),
            );
            if (is_array($title_property)) {
                $select[] = array(\DB::expr('CONCAT_WS(" ", '.implode(', ', $title_property).')'), 'label');
            } else {
                $select[] = array($title_property, 'label');
                $title_property = array($title_property);
            }

            // Check if a twinnable condition is needed
            $context = \Input::post('twinnable', false);
            if (!empty($context)) {
                $behaviour_twinnable = $class::behaviours('Nos\Orm_Behaviour_Twinnable', false);
                if ($behaviour_twinnable) {
                    $query->and_where_open()
                        ->where($behaviour_twinnable['context_property'], is_array($context) ? 'IN' : '=', $context)
                        ->or_where($behaviour_twinnable['is_main_property'], '=', 1)
                        ->and_where_close();

                    // Add context_common_id and context in select
                    $select[] = array($behaviour_twinnable['common_id_property'], 'common');
                    $select[] = array($behaviour_twinnable['context_property'], 'context');
                }
            }
            $query = $query->get_query();

            $query = $query->select_array($select, true);

            // Search on title if jayps_search is disabled
            if (empty($use_jayps_search) && !empty($keywords)) {
                $query->where_open();
                foreach ($title_property as $p) {
                    $query->or_where($p, 'LIKE', '%'.$keywords.'%');
                }
                $query->where_close();
            }

            // Limit (optionnal)
            \Config::load('novius_renderers::renderer/modelsearch', true);
            $limit = \Config::get('novius_renderers::renderer/modelsearch.suggestion_limit', array());
            if (!empty($limit)) {
                $query->limit($limit);
            }

            // Order by title
            foreach ($title_property as $p) {
                $query->order_by($p);
            }

            // Get query results
            $results = $query
                ->distinct(true)
                ->execute()
                ->as_array()
            ;
            $results = array_filter((array) $results);

            if (!empty($context)) {
                // Remove pairs in results (only keep the same context if exist)
                $result_context = array();
                $result = array();
                foreach ($results as $r) {
                    if (isset($result_context[$r['common']])) {
                        if ((is_array($context) && !in_array($r['context'], $context)) ||
                            $r['context'] !== $context) {
                            continue;
                        } else {
                            unset($result[$result_context[$r['common']]]);
                        }
                    }
                    $result_context[$r['common']] = $r['value'];
                    $result[$r['value']] = array(
                        'value' => $r['value'],
                        'label' => $r['label'],
                    );
                }

                $results = $result;
            }

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