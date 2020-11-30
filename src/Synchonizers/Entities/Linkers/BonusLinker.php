<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities\Linkers;


use App\Models\Bonuses\Bonus;

interface BonusLinker
{
    /**
     * @param Bonus $bonus
     * @return array
     */
    public function defineBuyPrice(Bonus $bonus): array;

}