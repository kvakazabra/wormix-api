<?php

namespace App\Http\Resources\Internal\Account;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\Wormix\CharData;
use App\Models\Wormix\UserTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserTeam $resource
 */
class TeamMemberStructure extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        $char = $this->resource->char;
        $owner = $this->resource->owner;
        $ownerChar = $owner->char();

        $armor = $char->armor;
        $attack = $char->attack;
        $level = $char->level;
        $name = $char->name;

        switch ($char->type)
        {
            case CharData::TEAM_MEMBER_FRIEND:
            case CharData::TEAM_MEMBER_MERCENARY:
            case CharData::TEAM_MEMBER_CLANMATE:
            {
                // Clanmates are kept as they were hired
                if ($ownerChar->level < $char->level &&
                    $char->type != CharData::TEAM_MEMBER_CLANMATE)
                {
                    $level = min($ownerChar->level, $level);
                    $points = WormixTrashHelper::rebalancePointsByLevel(
                        $ownerChar->level,
                        $char->level,
                        $armor,
                        $attack
                    );

                    $armor = $points['Armor'];
                    $attack = $points['Attack'];
                }

                // Friends or a clanmates name cannot be overwritten
                // Unless the char associated with him has been deleted
                if ($char->type === CharData::TEAM_MEMBER_FRIEND ||
                    $char->type === CharData::TEAM_MEMBER_CLANMATE)
                {
                    $actualName = CharData::query()
                        ->where('owner_id', $char->profile_id)
                        ->where('type', CharData::TEAM_MEMBER_SELF)
                        ->first()
                        ?->name;
                    if ($actualName !== null)
                    {
                        $name = $actualName;
                    }
                }

                break;
            }
            case CharData::TEAM_MEMBER_SELF:
                break;
        }

        return [
            'OwnerId' => $this->resource->char->profile_id,
            'Armor' => $armor,
            'Attack' => $attack,
            'Level' => $level,
            'Experience' => $this->resource->char->experience,
            'HatId' => $this->resource->char->hat,
            'RaceId' => $this->resource->char->race,
            'Skin' => $this->resource->char->skin,
            'ArtifactId' => $this->resource->char->artifact,
            'SocialOwnerId' => (string)$this->resource->owner->id,
            'Name' => $name,
            'TeamMemberType' => $this->resource->char->type,
            'IsActive' => $this->resource->active,
        ];

    }
}
