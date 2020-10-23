<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;


use MoySklad\MoySklad;
use MoySklad\Entities\Store;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Organization;
use MoySklad\Entities\Documents\Movements\Enter;

class EnterMaker
{
    /**
     * @var MoySklad
     */
    private $client;

    /**
     * EnterMaker constructor.
     * @param MoySklad $client
     */
    public function __construct(MoySklad $client)
    {
        $this->client = $client;
    }

    /**
     * @param Organization $organization
     * @param Store $store
     * @param EntityList $enterPositions
     * @param string|null $enterName
     * @return mixed
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     */
    public function addNewEnter(Organization $organization,Store $store, EntityList $enterPositions, string $enterName = null): Enter
    {
        $enter = new Enter($this->client, [
            "name" => $enterName ?? $this->defineEnterName(),
        ]);

        $enter
            ->buildCreation()
            ->addOrganization($organization)
            ->addStore($store)
            ->addPositionList($enterPositions)
            ->execute();

        return $enter;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function defineEnterName(): string
    {
        return (new \DateTime('now'))->format('Y-m-d H:i:s') . "_" . hash('md5', rand());
    }
}