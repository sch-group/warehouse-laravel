<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\ShipmentHandlers;

use App\Models\Orders\Order;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use MoySklad\MoySklad;

abstract class DemandHandler
{
    /**
     * @var CustomerOrder
     */
    protected $remoteOrder;

    /**
     * @var MoySklad
     */
    protected $client;


    /**
     * DemandHandler constructor.
     * @param CustomerOrder $remoteOrder
     */
    public function __construct(CustomerOrder $remoteOrder)
    {
        $this->remoteOrder = $remoteOrder;
        $this->client = app(MoySklad::class);
    }


    abstract public function handle(): void;

}