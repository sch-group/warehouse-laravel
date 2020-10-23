<?php


namespace SchGroup\MyWarehouse\Contracts;


interface FirstEntersCreator
{
    public function createFirstStockBalances(): void;
}