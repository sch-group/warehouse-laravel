<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;


use MoySklad\MoySklad;
use App\Models\Orders\Status;
use Illuminate\Support\Collection;
use MoySklad\Entities\Documents\Orders\CustomerOrder;

/**
 * Class OrderStatusSynchronizer
 * @package SchGroup\MyWarehouse\Synchonizers\Entities
 */
class OrderStatusSynchronizer extends AbstractEntitySynchronizer
{
    const DEFAULT_STATUS_COLOR = 8767198;

    const DEFAULT_STATE_TYPE = 'Regular';

    const STATE_CREATE_URI = 'entity/customerorder/metadata/states';

    /**
     * @var MoySklad
     */
    private $client;

    /**
     * OrderStatusSynchronizer constructor.
     * @param MoySklad $client
     */
    public function __construct(MoySklad $client)
    {
        $this->client = $client;
    }

    /**
     * @throws \Throwable
     */
    protected function applyExistedUuidsToOurEntity(): void
    {
        $keyedRemoteStatusesByName = $this->findExistedStatuses();
        $ourStatuses = $this->getOurStatuses();
        $this->saveRemoteStatusesUuids($ourStatuses, $keyedRemoteStatusesByName);
    }

    /**
     *
     */
    protected function addOurEntityToRemoteWarehouse(): void
    {

        $ourStatuses = $this->getOurStatuses();
        $ourStatuses->map(function (Status $ourStatus) {
            $remoteStatus = $this->client->getClient()->post(self::STATE_CREATE_URI, [
                "name" => $ourStatus->getTitleAttribute(),
                "color" => self::DEFAULT_STATUS_COLOR,
                "stateType" => self::DEFAULT_STATE_TYPE
            ]);
            $ourStatus->saveMyWareHouseEntity($remoteStatus->id, $ourStatus->getTitleAttribute());
        });
    }

    /**
     * @return array
     * @throws \Throwable
     */
    protected function findExistedStatuses(): array
    {
        $remoteStatuses = CustomerOrder::getMetaData($this->client);
        $keyedRemoteStatusesByName = [];
        foreach ($remoteStatuses->states as $remoteStatus) {
            $keyedRemoteStatusesByName[$remoteStatus->name] = $remoteStatus->id;
        }

        return $keyedRemoteStatusesByName;
    }

    /**
     * @param Collection $ourStatuses
     * @param array $keyedRemoteStatusesByName
     */
    protected function saveRemoteStatusesUuids(Collection $ourStatuses, array $keyedRemoteStatusesByName): void
    {
        $ourStatuses->map(function (Status $ourStatus) use ($keyedRemoteStatusesByName) {
            $statusName = $ourStatus->getTitleAttribute();
            if (isset($keyedRemoteStatusesByName[$statusName])) {
                $uuid = $keyedRemoteStatusesByName[$statusName];
                $ourStatus->saveMyWareHouseEntity($uuid, $statusName);
            }
        });
    }

    /**
     * @return mixed
     */
    protected function getOurStatuses(): Collection
    {
        return Status::hasNotMappedYet()->get();
    }
}