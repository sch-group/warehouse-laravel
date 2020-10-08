<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Entities\Organization;
use SchGroup\MyWarehouse\Traits\HasMyWarehouseEntity;

trait WarehouseEntityHelper
{
    /**
     * @param EntityList $existedRemoteEntities
     * @param Collection $ourEntities
     * @throws \Throwable
     */
    public function applyUuidsToOurEntity(EntityList $existedRemoteEntities, Collection $ourEntities): void
    {
        \DB::transaction(function () use ($existedRemoteEntities, $ourEntities) {
            $existedRemoteEntities->each(function ($remoteEntity) use ($ourEntities) {
                $code = $remoteEntity->code;
                $uuid = $remoteEntity->id;
                /** @var HasMyWarehouseEntity $ourEntityToUpdate */
                $ourEntityToUpdate = $ourEntities[$code];
                $ourEntityToUpdate->saveMyWareHouseEntity($uuid, $code);
            });
        });
        dd("kek");
    }

    /**
     * @param string $entityClass
     * @param \Illuminate\Support\Collection $ourEntities
     * @return \MoySklad\Lists\EntityList
     */
    public function findExistedRemoteEntities(string $entityClass, Collection $ourEntities): EntityList
    {
        $client = app(MoySklad::class);

        return $entityClass::query($client)
            ->getList()
            ->filter(function ($remoteEntity) use ($ourEntities) {
                return $ourEntities->keys()->contains($remoteEntity->code ?? "");
            });
    }

    /**
     * @return \MoySklad\Entities\AbstractEntity
     * @throws \Throwable
     */
    public function getOrganization(): \MoySklad\Entities\AbstractEntity
    {
        $organizationId = config('my_warehouse.organization_uuid');

        return Organization::query($this->client)->byId($organizationId);
    }
}