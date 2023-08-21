<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/juzacms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Contracts;

use Illuminate\Support\Collection;
use Juzaweb\CMS\Contracts\Media\Disk;
use Juzaweb\MultipleStorage\Models\Storage;

interface StorageManager
{
    public function registerStorage(string $key, array $args = []): void;

    public function largestFreeSpace(): Disk;

    public function random(): Disk;

    public function all(bool $collection = true): Collection|array;

    public function get(string $key): ?array;

    public function createFileSystemByName(string $storage): ?Disk;

    public function createFileSystem(Storage $storage): Disk;
}
