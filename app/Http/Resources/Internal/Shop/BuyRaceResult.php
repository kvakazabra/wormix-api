<?php

namespace App\Http\Resources\Internal\Shop;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\Wormix\CharData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
/**
 * @property-read CharData $resource
 */
class BuyRaceResult extends JsonResource
{
    public const SUCCESS = 0;
    public const ERROR = 1;
    public const MIN_REQUIREMENTS_ERROR = 2;
    public const NOT_ENOUGH_MONEY = 3;
    public const CONFIRM_FAILURE = 4;
    public const NOT_ENOUGH_BATTLE_TOKENS = 5;
    public const NOT_ENOUGH_REAGENTS = 6;
    public const NOT_FOR_SALE = 7;

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
        return [
            'Result' => $this->result,
            'Costs' => [],
            'SessionKey' => "",
            'Race' => $this->resource->race,
            'Races' => WormixTrashHelper::generateRacesBitfield($this->resource->races),
        ];
    }
}
