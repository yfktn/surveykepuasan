<?php namespace Yfktn\SurveyKepuasan;

use Backend;
use System\Classes\PluginBase;

/**
 * SurveyKepuasan Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'SurveyKepuasan',
            'description' => 'Plugin OctoberCMS untuk menampilkan survey kepuasan.',
            'author'      => 'Yfktn',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Yfktn\SurveyKepuasan\Components\SurveyKepuasanComponent' => 'surveyKepuasanComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'yfktn.surveykepuasan.manajemen' => [
                'tab' => 'Survey Kepuasan',
                'label' => 'Manajemen Survey'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'surveykepuasan' => [
                'label'       => 'Survey Kepuasan',
                'url'         => Backend::url('yfktn/surveykepuasan/surveykepuasan'),
                'icon'        => 'icon-crosshairs',
                'permissions' => ['yfktn.surveykepuasan.*'],
                'order'       => 500,
            ],
        ];
    }
}
