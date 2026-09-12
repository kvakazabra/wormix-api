<?php

namespace App\Http\Resources\Internal\Clans;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClanInviteStructure extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'ClanId' => 0,
            'ClanName' => "",
            'ClanEmblem' => [],
            'SocialId' => 0,
            'ProfileId' => 0,
            'StringProfileId' => "",
            'Rank' => 0,
            'Name' => "",
            'InviteDate' => 0,
        ];
    }
}
