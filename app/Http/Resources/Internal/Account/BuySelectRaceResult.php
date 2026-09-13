<?php

namespace App\Http\Resources\Internal\Account;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\Wormix\CharData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read CharData $resource
 */
class BuySelectRaceResult extends JsonResource
{
    // Result codes are in SelectRaceResult

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
            'Skin' =>
                WormixTrashHelper::mergeSkinAndRaceIds($this->resource->skin, $this->resource->race),
        ];
    }
}
