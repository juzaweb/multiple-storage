<?php

namespace Juzaweb\MultipleStorage\Models;

use Juzaweb\CMS\Models\Model;
use Juzaweb\CMS\Traits\ResourceModel;
use Juzaweb\CMS\Traits\UUIDPrimaryKey;

class Storage extends Model
{
    use UUIDPrimaryKey, ResourceModel;

    protected $keyType = 'string';

    protected $table = 'multi_storage_storages';

    protected $fillable = [
        'name',
        'type',
        'configs',
    ];

    protected $casts = [
        'configs' => 'array',
    ];
}
