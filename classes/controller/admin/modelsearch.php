<?php

namespace Novius\Renderers;

class Controller_Admin_ModelSearch extends Controller_Admin_Autocomplete
{
    public function prepare_i18n()
    {
        parent::prepare_i18n();
        \Nos\I18n::current_dictionary('novius_renderers::default');
    }

    public function action_search()
    {
        try {
            $config = (array)\Input::post('config', array());

            // Get the search keywords
            $keywords = trim(strval(\Input::post('search', '')));

            // Use jayps_search ?
            $use_jayps_search = \Arr::get($config, 'use_jayps_search', false);
            $use_jayps_search = (!empty($use_jayps_search) and $use_jayps_search != 'false');

            // Get the target model
            $model = (string)\Input::post('model', '');
            if (empty($model) or !class_exists($model)) {
                throw new \Exception('Could not find this model.');
            }


            // The fields to display as the label in a result
            $display_field = \Input::post('display', array());

            // The method to call on the item to display as the label in a result
            $display_method = \Input::post('display_method', '');

            // Check if the $model is available
            $available_models = (array)\Arr::get($config, 'available_models', array());
            if (!in_array($model, $available_models)) {
                throw new \Exception('This model is not compatible with this feature (not available).');
            }

            // Check if the current user has the permission to access the model
            list($application) = \Config::configFile($model);
            if (!\Nos\User\Permission::isApplicationAuthorised($application)) {
                throw new \Nos\Access_Exception('You don\'t have access to application '.$application.'!');
            }

            $query_args = (array)\Arr::get($config, 'query_args', array());

            $pk_property    = \Arr::get($model::primary_key(), 0);
            $title_property = method_exists($model, 'search_property') ? $model::search_property() : $model::title_property();
            if (empty($title_property)) {
                throw new \Exception('This model is not compatible with this feature (title property is required).');
            }

            // Search on keywords if jayps_search is enabled
            $searchable = $model::behaviours('JayPS\Search\Orm_Behaviour_Searchable');
            if (!empty($use_jayps_search) && !empty($keywords) && !empty($searchable)) {
                $query_args['where'][] = array('keywords', $keywords.'*');
            }

            // Create the base query from the model
            $query = $model::query($query_args);

            // Will select only the primary key and the title
            $select = array(
                array($pk_property, 'value'),
            );
            if (is_array($title_property)) {
                $select[] = array(\DB::expr('CONCAT_WS(" ", '.implode(', ', $title_property).')'), 'label');
            } else {
                $select[]       = array($title_property, 'label');
                $title_property = array($title_property);
            }

            // Check if a twinnable condition is needed
            $context = \Input::post('twinnable', false);
            if (!empty($context)) {
                $behaviour_twinnable = $model::behaviours('Nos\Orm_Behaviour_Twinnable', false);
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

            if (!empty($display_field)) {
                $keys = array_keys($display_field);
                foreach ($keys as $key) {
                    $select[] = array($key, $key);
                }
            }

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
                ->as_array();
            $results = array_filter((array)$results);

            if (!empty($context)) {

                // Group items by common id
                $common_items = array();
                foreach ($results as $r) {
                    $common_items[$r['common']][$r['value']] = $r;
                }

                // Build results
                $results = array();
                foreach ($common_items as $items) {
                    // Sort items by specified context order
                    uasort($items, function ($a, $b) use ($context) {
                        $a_context = $a['context'] == $context;
                        $b_context = $b['context'] == $context;
                        if ($a_context === false xor $b_context === false) {
                            return $a_context === false ? 1 : -1;
                        }
                        return intval($a_context) - intval($b_context);
                    });
                    reset($items);
                    $results[key($items)] = current($items);
                }
            }

            if (!empty($results)) {
                if (!empty($display_method)) {
                    $arr_ids = array();
                    foreach ($results as $result) {
                        $arr_ids[] = $result['value'];
                    }
                    $items = $model::query()
                        ->where(
                            \Arr::get($model::primary_key(), 0), 'IN', $arr_ids
                        )
                        ->get();
                    foreach ($results as $resultKey => $result) {
                        $label = '';
                        if (array_key_exists($result['value'], $items)) {
                            $item  = $items[$result['value']];
                            $label = $item->$display_method();
                        }
                        if (!empty($label)) {
                            $results[$resultKey]['label'] = $label;
                        }
                    }
                } else {
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

            } else {
                $results = array(
                    array(
                        'value' => 0,
                        'label' => __('No content of that type has been found')
                    )
                );
            }

            \Response::json($results);
        } // Errors
        catch (\Exception $e) {
            \Response::json(array(
                'error' => $e->getMessage(),
            ));
        }
    }
}