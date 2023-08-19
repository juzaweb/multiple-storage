<?php

namespace Juzaweb\MultipleStorage\Repositories;

use Juzaweb\MultipleStorage\Models\Storage;
use Juzaweb\CMS\Repositories\BaseRepositoryEloquent;

class StorageRepositoryEloquent extends BaseRepositoryEloquent implements StorageRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return Storage::class;
    }
}
