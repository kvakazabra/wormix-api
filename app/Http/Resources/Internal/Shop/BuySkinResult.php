<?php

namespace App\Http\Resources\Internal\Shop;

use App\Models\Wormix\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserProfile $resource
 */
class BuySkinResult extends JsonResource
{
    public const SUCCESS = 0;
    public const ERROR = 1;
    public const MIN_REQUIREMENTS_ERROR = 2;
    public const NOT_ENOUGH_MONEY = 3;
    public const CONFIRM_FAILURE = 4;
    public const NOT_ENOUGH_BATTLE_TOKENS = 5;
    public const NOT_ENOUGH_REAGENTS = 6;
    public const NOT_FOR_SALE = 7;

    public function __construct($resource, int $result, int $skinId)
    {
        $this->result = $result;
        $this->skinId = $skinId;
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
            'Skin' => $this->skinId,
            'Skins' => $this->resource->skins,
        ];
    }
}
