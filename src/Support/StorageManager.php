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

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Juzaweb\CMS\Contracts\Media\Disk;
use Juzaweb\CMS\Contracts\Media\Media;
use Juzaweb\MultipleStorage\Contracts\StorageManager as StorageManagerContract;
use Juzaweb\MultipleStorage\Exceptions\StorageNotFoundException;
use Juzaweb\MultipleStorage\Models\Storage;
use Juzaweb\MultipleStorage\Support\Disk as DiskSupport;

class StorageManager implements StorageManagerContract
{
    protected static array $storages = [];

    public function __construct(protected Media $media)
    {
        //
    }

    public function registerStorage(string $key, array $args = []): void
    {
        self::$storages[$key] = [
            'name' => $args['name'],
            'creater' => $args['creater'],
            'configs' => $args['configs'],
        ];
    }

    public function all(bool $collection = true): Collection|array
    {
        if ($collection) {
            return collect(self::$storages);
        }

        return self::$storages;
    }

    public function get(string $key): ?array
    {
        return self::$storages[$key] ?? null;
    }

    public function random(): Disk
    {
        $storage = Storage::where(['active' => true])->inRandomOrder()->first();

        throw_if($storage === null, StorageNotFoundException::class);

        return $this->createFileSystem($storage);
    }

    public function largestFreeSpace(): Disk
    {
        $storage = Storage::where(['active' => true])->orderBy('free_size', 'DESC')->first();

        throw_if($storage === null, StorageNotFoundException::class);

        return $this->createFileSystem($storage);
    }

    public function createFileSystemByName(string $storage): ?Disk
    {
        $id = Str::after($storage, 'mtt_');

        $storage = Storage::find($id);

        return $storage ? $this->createFileSystem($storage) : null;
    }

    public function createFileSystem(Storage $storage): Disk
    {
        $filesystem = app()->make($this->get($storage->type)['creater'])->create($storage->configs);

        return (new DiskSupport(
            "mtt_{$storage->id}",
            $this->media->getFactory(),
        ))
            ->setFileSystem($filesystem)
            ->setStorage($storage);
    }
}
