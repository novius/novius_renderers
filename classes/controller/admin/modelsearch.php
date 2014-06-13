<?php
namespace Novius\Renderers;

class Controller_Admin_ModelSearch extends \Nos\Controller_Admin_Application
{
    public function action_search() {
        $filter = \Input::post('search', '');
        $class = \Input::post('model', '\Nos\Page\Model_Page');
        $table = $class::table();
        $title = $class::title_property();
        $pk = $class::primary_key();
        $id = reset($pk);
        $show = \Fuel\Core\DB::select(
            array($id, 'value'),
            array($title, 'label')
        )->from($table);

        if (strlen($filter) > 0) {
            $show->where_open()
                ->or_where($title, 'LIKE', '%' . $filter . '%')
                ->where_close();
        }
        $show = (array) $show->order_by($title)->distinct(true)->execute()->as_array();
        if (empty($show)) {
            $show = array(
                array(
                    'value' => 0,
                    'label' => __('No content of that type has been found')
                )
            );
        }
        \Response::json($show);
    }
}