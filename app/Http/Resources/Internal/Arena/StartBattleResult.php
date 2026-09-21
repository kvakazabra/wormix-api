<?php

namespace App\Http\Resources\Internal\Arena;

use App\Models\Wormix\UserBattle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserBattle $resource
 */
class StartBattleResult extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'BattleId' => $this->resource->id,
            'ReagentsForBattle' => $this->resource->reagents ?? [],
        ];
    }
}
