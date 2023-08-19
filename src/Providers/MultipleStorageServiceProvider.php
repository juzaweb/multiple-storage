<?php

namespace Juzaweb\MultipleStorage\Providers;

use Juzaweb\CMS\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Juzaweb\MultipleStorage\Actions\ResouceAction;
use Juzaweb\MultipleStorage\Repositories\StorageRepository;
use Juzaweb\MultipleStorage\Repositories\StorageRepositoryEloquent;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

class MultipleStorageServiceProvider extends ServiceProvider
{
    public array $bindings = [
        StorageRepository::class => StorageRepositoryEloquent::class,
    ];

    public function boot(): void
    {
        $this->registerHookActions([ResouceAction::class]);

        Storage::extend(
            'dropbox',
            function ($app, $config) {
                $adapter = new DropboxAdapter(
                    new DropboxClient(
                        $config['authorization_token']
                    )
                );

                return new FilesystemAdapter(
                    new Filesystem($adapter, $config),
                    $adapter,
                    $config
                );
            }
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/multiple-storage.php', 'multiple-storage');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [StorageRepository::class];
    }
}
