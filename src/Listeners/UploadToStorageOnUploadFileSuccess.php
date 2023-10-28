<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\MultipleStorage\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Juzaweb\Backend\Events\UploadFileSuccess;
use Juzaweb\CMS\Contracts\Media\Disk;
use Juzaweb\MultipleStorage\Contracts\StorageManager;
use Juzaweb\MultipleStorage\Exceptions\StorageFullException;

class UploadToStorageOnUploadFileSuccess implements ShouldQueue
{
    public int $timeout = 3600;

    public function __construct(protected StorageManager $storageManager)
    {
    }

    public function handle(UploadFileSuccess $event): void
    {
        if (!(bool) get_config('storages_auto_upload', 0)) {
            return;
        }

        $storage = $this->getStorageDisk();

        if ($storage->freeSpage() < $event->file->size) {
            throw new StorageFullException("Storage is full");
        }

        DB::transaction(
            function () use ($storage, $event) {
                $storage->upload(
                    Storage::disk($event->file->disk)->path($event->file->path),
                    $event->file->path
                );

                $event->file->deleteFile();

                $event->file->disk = $storage->getName();
                $event->file->save();
            }
        );
    }

    protected function getStorageDisk(): Disk
    {
        $order = get_config('storages_auto_upload_order', 'desc');

        if ($order === 'desc') {
            return $this->storageManager->largestFreeSpace();
        }

        return $this->storageManager->random();
    }
}
