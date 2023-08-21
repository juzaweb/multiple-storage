<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Support;

use Illuminate\Support\Facades\File;
use Juzaweb\CMS\Interfaces\Media\FileInterface;
use Juzaweb\CMS\Support\Media\Disk as DiskMedia;
use Juzaweb\MultipleStorage\Models\Storage;

class Disk extends DiskMedia
{
    protected Storage $storage;

    public function setStorage(Storage $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function upload(string $source, string $name): FileInterface
    {
        $file = parent::upload($source, $name);

        $this->storage->increment('used_size', File::size($source));

        return $file;
    }
}
