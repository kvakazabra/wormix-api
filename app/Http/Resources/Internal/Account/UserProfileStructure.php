<?php

namespace App\Http\Resources\Internal\Account;

use App\Http\Resources\Internal\Clans\ClanMemberStructure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'Id' => $this->user_id,
            'Money' => $this->money,
            'RealMoney' => $this->real_money,
            'Rating' => $this->rating,
            'Units' =>
                TeamMemberStructure::collection($this->teammates()->orderBy('order')->get()), // todo
            // todo check whether it returns only weapons, cuz it seems like it returns other items as well
            'WeaponRecordList' =>
                WeaponRecordList::collection($this->items),
            'Stuff' => [], // todo  $this->items()->where('item_id', '>', 1000)->get()->pluck('item_id')
            'TemporalStuff' => (object)[], // todo, returns stuffId to expirationTime dictionary
            'ReactionRate' => $this->reaction_rate,
            'SocialId' => (string)$this->user_id,
            'Recipes' => $this->recipes,
            'ClanMember' =>
                new ClanMemberStructure($this),
            'ExtraGroupSlotsCount' => $this->extra_group_slots,
            'RankPoints' => $this->rank_points,
            'BestRank' => $this->rank,
        ];
    }
}
