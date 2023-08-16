<?php

namespace Juzaweb\MultipleStorage\Providers;

use Juzaweb\CMS\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

class MultipleStorageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
