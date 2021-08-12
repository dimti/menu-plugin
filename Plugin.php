<?php namespace Dimti\Menu;


use Backend;
use BenFreke\MenuManager\Models\Menu;
use Dimti\Menu\ReportWidgets\AdministrationWidget;
use System\Classes\PluginBase;

/**
 * Menu Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['Benfreke.Menumanager'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Menu',
            'description' => 'Incapsulates menu logic',
            'author' => 'wpstudio',
            'icon' => 'icon-leaf'
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
        // add checksum getter to menu model
        Menu::extend(function ($model) {
            $model->addDynamicMethod('tableChecksum', function () use ($model) {
                $tableName = $model->getTable();
                $query = sprintf('CHECKSUM TABLE %s', $tableName);

                return \DB::select(\DB::raw($query))[0]->Checksum ?? null;
            });
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            \Dimti\Menu\Components\MenuPageInjector::class => 'menuInjector',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'salvoterra.menu.some_permission' => [
                'tab' => 'Menu',
                'label' => 'Some permission'
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
        return []; // Remove this line to activate

        return [
            'menu' => [
                'label' => 'Menu',
                'url' => Backend::url('salvoterra/menu/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['salvoterra.menu.*'],
                'order' => 500,
            ],
        ];
    }

    public function registerReportWidgets()
    {
        return [
            AdministrationWidget::class => [
                'label' => 'Menu cache',
                'context' => 'dashboard'
            ],
        ];
    }
}
