<?php

namespace Juzaweb\MultipleStorage\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Juzaweb\CMS\Models\Model;
use Juzaweb\CMS\Traits\ResourceModel;
use Juzaweb\CMS\Traits\UUIDPrimaryKey;

/**
 * Juzaweb\MultipleStorage\Models\Storage
 *
 * @property string $id
 * @property string $name
 * @property string $type
 * @property array|null $configs
 * @property int $total_size KB
 * @property int $used_size KB
 * @property int $free_size KB
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Storage newModelQuery()
 * @method static Builder|Storage newQuery()
 * @method static Builder|Storage query()
 * @method static Builder|Storage whereConfigs($value)
 * @method static Builder|Storage whereCreatedAt($value)
 * @method static Builder|Storage whereFilter($params = [])
 * @method static Builder|Storage whereId($value)
 * @method static Builder|Storage whereName($value)
 * @method static Builder|Storage whereTotalSize($value)
 * @method static Builder|Storage whereType($value)
 * @method static Builder|Storage whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Storage extends Model
{
    use UUIDPrimaryKey, ResourceModel;

    protected $keyType = 'string';

    protected $table = 'multi_storage_storages';

    protected $fillable = [
        'name',
        'type',
        'active',
    ];

    protected $casts = [
        'configs' => 'array',
        'total_size' => 'integer',
        'used_size' => 'integer',
        'free_size' => 'integer',
        'active' => 'boolean',
    ];
}
