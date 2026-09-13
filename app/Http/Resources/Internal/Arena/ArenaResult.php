<?php

namespace App\Http\Resources\Internal\Arena;

use App\Models\Wormix\UserArena;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserArena $resource
 */
class ArenaResult extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'BattlesCount' =>
                $this->resource->battle_tokens,
            'CurSoloMissionId' =>
                $this->resource->solo_mission_id,
            'CurCooperativeMissionId' =>
                $this->resource->coop_mission_id,
            'BossAvailable' => true,
            'SuperBossAvailable' => true,
            'HeroicStructures' => [],
            'Delay' => 0,
            'RestoreBattlesDelay' => 0,
            'RestrictedWagers' => [],
            'RestrictedWagersLeftTime' => [],
            'HeroicMissionDailyProgress' => [],
            'DefeatContributionMoney' => 0,
            'ExtraBattlesTimetable' => "{\"ROPE_RACE\":\"2026-01-05,2026-01-20\",\"DUEL_20\":\"2026-01-05,2026-01-20\",\"MERCENARIES_DUEL\":\"2026-01-05,2026-01-20\"}",
            'WagerWinAwardToken' =>
                $this->resource->wager_tokens,
            'BossWinAwardToken' =>
                $this->resource->boss_tokens,
            'BossAwardsStructure' =>
                ['BossAwards' => [], 'HeroicAwards' => []],
            'ZombieRiseCoolDownTime' => 3600 * 67,
        ];
    }
}
