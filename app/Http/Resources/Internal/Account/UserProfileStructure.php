<?php

namespace App\Http\Resources\Internal\Account;

use App\Http\Resources\Internal\Clans\ClanMemberStructure;
use App\Models\Wormix\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserProfile $resource
 */
class UserProfileStructure extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'Id' => $this->resource->user_id,
            'Money' => $this->resource->money,
            'RealMoney' => $this->resource->real_money,
            'Rating' => $this->resource->rating,
            'Units' =>
                TeamMemberStructure::collection($this->resource->teammates()
                    ->orderBy('order')->get()), // todo
            'WeaponRecordList' =>
                WeaponRecordList::collection($this->resource->items),
            'Stuff' =>
                $this->resource->equipments->pluck('item_id')->toArray(),
            'TemporalStuff' => (object)[], // todo, returns stuffId to expirationTime dictionary
            'ReactionRate' => $this->resource->reaction_rate,
            'SocialId' => (string)$this->resource->user_id,
            'Recipes' => $this->resource->recipes,
            'ClanMember' => new ClanMemberStructure($this),
            'ExtraGroupSlotsCount' => $this->resource->extra_group_slots,
            'RankPoints' => $this->resource->rank_points,
            'BestRank' => $this->resource->rank,
        ];
    }
}
