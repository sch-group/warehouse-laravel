<?php

namespace SchGroup\MyWarehouse\Traits;

use Illuminate\Database\Eloquent\Builder;
use SchGroup\MyWarehouse\Models\MyWarehouseEntity;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property MyWarehouseEntity morphMyWareHouse
 * Trait HasMyWarehouseEntity
 * @package SchGroup\MyWarehouse\Traits
 */
trait HasMyWarehouseEntity
{
    /**
     * Returns domainable attributes relation
     *
     * @return mixed
     */
    public function morphMyWareHouse(): MorphOne
    {
        return $this->morphOne(MyWarehouseEntity::class, 'entity');
    }

    /**
     * @param string $uuid
     * @param string $entityCode
     * @param string $entityType
     */
    public function saveMyWareHouseEntity(string $uuid, string $entityCode): void
    {
        $this->morphMyWareHouse()->create([
            'uuid' => $uuid,
            'entity_code' => $entityCode,
        ]);
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->morphMyWareHouse->uuid ?? "";
    }
    /*
      |--------------------------------------------------------------------------
      | SCOPES
      |--------------------------------------------------------------------------
    */
    /**
     * @param $query
     * @return mixed - Query Builder
     */
    public function scopeHasNotMappedYet(Builder $query)
    {
        return $query->whereDoesntHave('morphMyWareHouse');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeAlreadyMapped(Builder $query)
    {
        return $query->whereHas('morphMyWareHouse');
    }
}