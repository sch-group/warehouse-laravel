<?php

namespace SchGroup\MyWarehouse\Synchonizers\Helpers\DemandsHandlers;

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
     * StatusHandler constructor.
     * @param Order $order
     */
    public function __construct(CustomerOrder $remoteOrder)
    {
        $this->remoteOrder = $remoteOrder;
        $this->client = app(MoySklad::class);
    }


    abstract public function handle(): void;

}