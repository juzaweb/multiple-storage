<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Support\Creater;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Support\Arr;
use Juzaweb\MultipleStorage\Interfaces\FilesystemCreaterInterface;
use Juzaweb\MultipleStorage\Support\Adapters\GoogleDriveAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;

class GoogleDriveFilesystemCreater implements FilesystemCreaterInterface
{
    public function create(array $config): FilesystemContract
    {
        $client = new Client();
        // $client->setClientId($config['clientId']);
        // $client->setClientSecret($config['clientSecret']);
        // $client->refreshToken($config['refreshToken']);
        $client->setAuthConfig(base_path(Arr::get($config, 'credentials_file')));
        $client->setScopes('https://www.googleapis.com/auth/drive');
        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service, $config['folder_id'], []);

        return new FilesystemAdapter(
            new Filesystem($adapter, $config),
            $adapter,
            $config
        );
    }
}
