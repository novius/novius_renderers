<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2013 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link       http://www.novius-os.org
 */

namespace Novius\Renderers;

use Fuel\Core\Inflector;
use Nos\Renderer_Text;

class Renderer_CrudLink extends Renderer_Text
{
    protected $renderer_options = array();
    protected static $_cacheCrud = array();

    protected function defaultOptions()
    {
        \Nos\I18n::current_dictionary('novius_renderers::default');
        return array('text' => __('See the {{MODEL}}\'s page'), 'url' => '');
    }


    public function __construct($name, $label = '', array $attributes = array(), array $rules = array(), \Fuel\Core\Fieldset $fieldset)
    {
        $this->renderer_options = \Arr::merge($this->defaultOptions(), \Arr::get($attributes, 'renderer_options', array()));
        parent::__construct($name, $label, $attributes, $rules, $fieldset);
    }

    /**
     * How to display the field
     *
     * @return string
     */
    public function build()
    {

        $item = $this->value;
        $text = \Arr::get($this->renderer_options, 'text', '');
        $url  = \Arr::get($this->renderer_options, 'url', '');

        if (is_subclass_of($item, '\Nos\Orm\Model')) {
            $app   = $item->getApplication();
            $class = get_class($item);
            $crud  = $this->findCrud($class, $app);
            if ($crud) {
                $url = $crud . '/insert_update/' . $this->value->id;
            }
            $text = strtr($text, array('{{MODEL}}' => Inflector::humanize(Inflector::singularize((Inflector::tableize($class))))));

        }
        $this->value = \View::forge('novius_renderers::crudlink/link', compact('text', 'url'))->render();
        return (string)parent::build();
    }

    protected function findCrud($class, $app)
    {
        if (!\Arr::get(static::$_cacheCrud, $class)) {
            $configDir      = APPPATH . "applications/$app/config/";
            $controllerPath = 'controller/admin/';
            $listConfig     = \File::read_dir($configDir . $controllerPath);
            $found          = $this->searchDir($listConfig, $class, $app, $controllerPath);
            if (!empty($found)) {
                static::$_cacheCrud[$class] = \Arr::get($found, 'controller_url');
            }
        }
        return static::$_cacheCrud[$class];
    }

    protected function searchDir($dir, $class, $app, $currentDirectory)
    {
        $dir = (array)$dir;
        foreach ($dir as $dirname => $file) {
            if (is_array($file)) {
                foreach ($dir as $file) {
                    $return = $this->searchDir($file, $class, $app, $currentDirectory . $dirname);
                    if (!empty($return)) {
                        return $return;
                    }
                }
                continue;
            }
            $filename = $this->cleanFilename($file);
            $config   = \Config::load("$app::{$currentDirectory}{$filename}", true);
            if (empty($config)) {
                continue;
            }
            if (\Arr::get($config, 'model') === $class && \Arr::get($config, 'controller_url', null)) {
                return $config;
            }
        }
        return false;

    }

    protected function cleanFilename($file)
    {
        $infos     = pathinfo($file);
        $filename  = $infos['filename'];
        $suffixPos = strpos($filename, '.config');
        if ($suffixPos !== false) {
            $filename = substr($filename, 0, $suffixPos);
        }
        return $filename;
    }
}
