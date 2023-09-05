<?php

namespace Juzaweb\MultipleStorage\Providers;

use Juzaweb\CMS\Support\ServiceProvider;
use Juzaweb\MultipleStorage\Actions\ResouceAction;
use Juzaweb\MultipleStorage\Repositories\StorageRepository;
use Juzaweb\MultipleStorage\Repositories\StorageRepositoryEloquent;
use Juzaweb\MultipleStorage\Support\Creater\GoogleDriveFilesystemCreater;
use Juzaweb\MultipleStorage\Support\Creater\LocalFilesystemCreater;
use Juzaweb\MultipleStorage\Support\StorageManager;
use Juzaweb\MultipleStorage\Contracts\StorageManager as StorageManagerContract;

class MultipleStorageServiceProvider extends ServiceProvider
{
    public array $bindings = [
        StorageRepository::class => StorageRepositoryEloquent::class,
    ];

    public function boot(): void
    {
        $this->registerHookActions([ResouceAction::class]);

        $this->app[StorageManagerContract::class]->registerStorage(
            'local',
            [
                'name' => 'Local',
                'creater' => LocalFilesystemCreater::class,
                'configs' => [
                    'path' => [
                        'type' => 'text',
                        'label' => 'Local Path',
                    ]
                ]
            ]
        );

        $this->app[StorageManagerContract::class]->registerStorage(
            'google_drive',
            [
                'name' => 'Google Drive',
                'creater' => GoogleDriveFilesystemCreater::class,
                'configs' => [
                    // 'client_id' => [
                    //     'type' => 'text',
                    //     'label' => 'Client ID',
                    // ],
                    // 'client_secret' => [
                    //     'type' => 'security',
                    //     'label' => 'Client Secret',
                    // ],
                    // 'refresh_token' => [
                    //     'type' => 'security',
                    //     'label' => 'Refresh Token',
                    // ],
                    'credentials_file' => [
                        'type' => 'upload_url',
                        'label' => 'Credentials File',
                        'data' => [
                            'disk' => 'protected',
                        ]
                    ],
                    'folder' => [
                        'type' => 'text',
                        'label' => 'Folder',
                    ]
                ]
            ]
        );

        // Storage::extend(
        //     'dropbox',
        //     function ($app, $config) {
        //         $adapter = new DropboxAdapter(
        //             new DropboxClient(
        //                 $config['authorization_token']
        //             )
        //         );
        //
        //         return new FilesystemAdapter(
        //             new Filesystem($adapter, $config),
        //             $adapter,
        //             $config
        //         );
        //     }
        // );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/multiple-storage.php', 'multiple-storage');

        $this->app->singleton(StorageManagerContract::class, StorageManager::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [StorageRepository::class, StorageManagerContract::class];
    }
}
