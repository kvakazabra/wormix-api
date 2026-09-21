<?php

namespace App\Models\Wormix;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property int required_level
 * @property array awards
 */
class Mission extends Model
{
    protected $table = 'wormix_missions';

    protected $casts = [
        'awards' => 'array'
    ];

    public function next() : Mission
    {
        $nextId = WormixTrashHelper::isTutorialMissionId($this->id) ?
            $this->id - 1 : $this->id + 1;

        return Mission::query()
            ->where('id', $nextId)
            ->first();
    }

    public function progressUser(User $user) : void
    {
        $arena = $user->arena;
        // Set previous mission as completed
        $fixedId = WormixTrashHelper::isTutorialMissionId($this->id) ?
            $this->id + 1 : $this->id - 1;

        // Also use min/max so that progress won't get rollback
        match (true)
        {
            WormixTrashHelper::isSoloMissionId($this->id)
                => $arena->solo_mission_id = max($fixedId, $arena->solo_mission_id),
            WormixTrashHelper::isCoopMissionId($this->id)
                => $arena->coop_mission_id = max($fixedId, $arena->coop_mission_id),
            WormixTrashHelper::isTutorialMissionId($this->id)
                => $arena->solo_mission_id = min($fixedId, $arena->solo_mission_id),
        };

        $arena->save();
    }

    public function award(User $user) : void
    {
        $award = $this->awards[$this->isFirstCompletion($user->arena) ? 0 : 1];

        $profile = $user->user_profile;
        $char = $user->char_data;

        if (isset($award['real_money']))
        {
            $profile->real_money += $award['real_money'];
        }
        if (isset($award['money']))
        {
            $profile->money += $award['money'];
        }
        $profile->save();

        if (isset($award['experience']))
        {
            $char->experience += $award['experience'];
        }
        $char->save();

        $profile->grantItems(WormixTrashHelper::pairsToAssociativeArray($award['weapons'] ?? []), true, true);
        // todo: weapons (infinite with finite count), equipments
    }

    private function isFirstCompletion(UserArena $arena) : bool
    {
        $result = false;
        if (WormixTrashHelper::isTutorialMissionId($this->id))
        {
            $result = true;
        }
        else
        {
            match (true)
            {
                WormixTrashHelper::isSoloMissionId($this->id)
                => $currentMissionId = $arena->solo_mission_id,
                WormixTrashHelper::isCoopMissionId($this->id)
                => $currentMissionId = $arena->coop_mission_id
            };

            if ($currentMissionId + 1 === $this->id)
            {
                $result = true;
            }
        }

        return $result;
    }
}
