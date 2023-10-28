<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Actions;

use Juzaweb\CMS\Abstracts\Action;

class ResouceAction extends Action
{
    public function handle(): void
    {
        $this->addAction(Action::BACKEND_INIT, [$this, 'registerResource']);
        $this->addAction(Action::BACKEND_INIT, [$this, 'registerConfigs']);
    }

    public function registerConfigs(): void
    {
        $this->hookAction->registerSettingPage(
            'storages',
            [
                'label' => trans('Setting'),
                'menu' => [
                    'parent' => 'storages',
                    'position' => 99,
                    'icon' => 'fa fa-cogs',
                ]
            ]
        );

        $this->hookAction->addSettingForm(
            'storages',
            [
                'name' => trans('Settings'),
                'page' => 'storages',
            ]
        );

        $this->hookAction->registerConfig(
            [
                'storages_auto_upload' => [
                    'type' => 'select',
                    'label' => trans('Auto upload'),
                    'form' => 'storages',
                    'data' => [
                        'options' => [
                            0 => trans('cms::app.disabled'),
                            1 => trans('cms::app.enable')
                        ],
                        'description' => trans('Auto upload files to storage'),
                    ]
                ],
                'storages_auto_upload_order' => [
                    'type' => 'select',
                    'label' => trans('Auto upload order'),
                    'form' => 'storages',
                    'data' => [
                        'options' => [
                            'desc' => 'Largest free space',
                            'ramdom' => 'Random',
                        ],
                    ]
                ]
            ]
        );
    }

    public function registerResource(): void
    {
        $this->hookAction->addAdminMenu(
            trans('Storages'),
            'storages',
            [
                'icon' => 'fa fa-database',
            ]
        );

        $this->hookAction->registerAdminPage(
            'storages',
            [
                'title' => trans('All Storages'),
                'menu' => [
                    'parent' => 'storages',
                    'icon' => 'fa fa-hdd-o',
                ]
            ]
        );
    }
}
