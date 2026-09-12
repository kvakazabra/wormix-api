<?php

namespace App\Http\Resources\Internal\Clans;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClanMemberStructure extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'Rank' => 0,
            'ClanId' => 0,
            'ClanName' => "",
            'ClanEmblem' => [],
            'ClanRating' => 0,
            'ClanSeasonRating' => 0,
            'ReviewState' => 0,
            'PrevSeasonTopPlace' => 0,
        ];
    }
}
