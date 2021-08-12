<?php
namespace Dimti\Menu\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Carbon\Carbon;
use Dimti\Menu\Components\MenuPageInjector;
use Dimti\Mirsporta\Exports\YandexMapFeedExport;
use Dimti\Mirsporta\Plugin;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\ResponseCache\Facades\ResponseCache;

class AdministrationWidget extends ReportWidgetBase
{
    protected $defaultAlias = 'administrationWidget';

    public function render()
    {
        return $this->makePartial('widget');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'Администрирование',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error'
            ],
        ];
    }

    public function onClearCache()
    {
        \Cache::tags(MenuPageInjector::class)->clear();

        return ['status' => 1];
    }
}
