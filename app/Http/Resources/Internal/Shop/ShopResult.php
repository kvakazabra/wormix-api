<?php

namespace App\Http\Resources\Internal\Shop;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Resources\Internal\Account\WeaponRecordList;
use App\Models\User;
use App\Models\Wormix\UserItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Collection|UserItem $resource
 */
class ShopResult extends JsonResource
{
    public const Success = 0;
    public const Error = 1;
    public const MinRequirementsError = 2;
    public const NotEnoughMoney = 3;
    public const ConfirmFailure = 4;

    private int $result;

    public function __construct($resource, int $result)
    {
        $this->result = $result;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        $weapons = [];
        $equipments = [];

        foreach ($this->resource as $item)
        {
            unset($item['MoneyType']);

            $itemId = $item['Id'];
            $count = $item['Count'];

            if (WormixTrashHelper::isWeaponType($itemId))
            {
                $new = [];
                $new['Id'] = $itemId;
                $new['Count'] = $count;
                $weapons[] = $new;
            }

            if (WormixTrashHelper::isStuffType($itemId))
            {
                $equipments[] = $itemId;
            }
        }

        return [
            'Result' => $this->result,
            'Weapons' => $weapons,
            'Stuff' => $equipments,
            'TemporalStuff' => [],
        ];
    }
}
