<?php

namespace SchGroup\MyWarehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class MyWarehouseEntity
 * @package SchGroup\MyWarehouse\Models
 * @property string uuid
 * @property string entity_type,
 * @property string entity_code
 */
class MyWarehouseEntity extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'entity_id',
        'entity_type',
        'uuid',
        'entity_code',
    ];

    /**
     * @return MorphTo
     */
    public function warehouseEntity(): MorphTo
    {
        return $this->morphTo();
    }
}