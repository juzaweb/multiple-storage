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

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Filesystem\FilesystemAdapter;
use Juzaweb\MultipleStorage\Interfaces\FilesystemCreaterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalFilesystemCreater implements FilesystemCreaterInterface
{
    public function create(array $config): FilesystemContract
    {
        $adapter = new LocalFilesystemAdapter($config['path']);

        return new FilesystemAdapter(
            new Filesystem($adapter, $config),
            $adapter,
            $config
        );
    }
}
