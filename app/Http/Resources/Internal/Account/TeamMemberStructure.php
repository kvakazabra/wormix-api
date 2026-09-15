<?php

namespace App\Http\Resources\Internal\Account;

use App\Models\Wormix\CharData;
use App\Models\Wormix\UserTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserTeam $resource
 * @property-read CharData $teammate
 */
class TeamMemberStructure extends JsonResource
{
    private const TEAM_MEMBER_SELF = 0;

    private const TEAM_MEMBER_FRIEND = 1;

    private const TEAM_MEMBER_CLANMATE = 2;

    private const TEAM_MEMBER_MERCENARY = 4;

    /*
- 3	OTHER_CLAN_MATE	Clan-mate, but disabled for teaming (gift-team slot / not fully active)
- 5	HILEVEL_CLAN_MATE	High-level clan-mate, also disabled
     */

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        $armor = $this->teammate->armor;
        $attack = $this->teammate->attack;
        $level = $this->teammate->level;

//        if ($this->user_id !== $this->teammate_id &&
//            $this->owner->level !== $this->teammate->level)
//        {
//            $newPoints =
//                WormixBotHelper::stripData($level, $this->owner->level, $armor, $attack);
//            $level = $this->owner->level;
//            $armor = $newPoints['armor'];
//            $attack = $newPoints['attack'];
//        }

        return [
            'OwnerId' => $this->resource->teammate_id,
            'Armor' => $armor,
            'Attack' => $attack,
            'Level' => $level,
            'Experience' => $this->teammate->experience,
            'HatId' => $this->teammate->hat,
            'RaceId' => $this->teammate->race,
            'Skin' => $this->teammate->skin,
            'ArtifactId' => $this->teammate->artifact,
            'SocialOwnerId' => (string)$this->resource->teammate_id,
            'Name' => $this->teammate->name,
            'TeamMemberType' =>
                $this->resource->teammate_id == $this->resource->user_id ?
                    self::TEAM_MEMBER_SELF : self::TEAM_MEMBER_FRIEND,
            'IsActive' => true,
        ];

    }
}
