<?php

namespace App\Http\Resources\Internal\Arena;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EndBattleResult extends JsonResource
{
    public const SUCCESS = 0;

    public const CHEAT = 1;

    public const DUPLICATED = 2;

    public const INVALID_REQUEST = 3;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'ValidateResult' => $this->resource['ValidateResult'],
            'Result' => $this->resource['Result'],
            'BattleId' => $this->resource['BattleId'],
            'MissionId' => $this->resource['MissionId'],
            'SessionKey' => "",
        ];
    }
}
