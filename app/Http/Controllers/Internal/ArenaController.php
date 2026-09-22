<?php

namespace App\Http\Controllers\Internal;

use App\Exceptions\Wormix\InternalServerException;
use App\Exceptions\Wormix\RequirementsNotMetException;
use App\Exceptions\Wormix\SecurityException;
use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Arena\EndBattleRequest;
use App\Http\Requests\Internal\Arena\GetArenaRequest;
use App\Http\Requests\Internal\Arena\StartBattleRequest;
use App\Http\Resources\Internal\Arena\ArenaLocked;
use App\Http\Resources\Internal\Arena\ArenaResult;
use App\Http\Resources\Internal\Arena\EndBattleResult;
use App\Http\Resources\Internal\Arena\StartBattleResult;
use App\Models\User;
use App\Models\Wormix\Mission;
use App\Models\Wormix\UserBattle;
use App\Models\Wormix\UserArena;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ArenaController extends Controller
{
    public function getArena(GetArenaRequest $request)
    {
        $userArena = UserArena::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->firstOrFail();

        $userArena->checkTimeGrantBattles();

        return [
            'type' => 'ArenaResult',
            'data' => new ArenaResult($userArena),
        ];
    }

    public function startBattle(StartBattleRequest $request)
    {
        $invalidDelayMs = 2000;
        $missionId = $request->json('MissionId');

        try
        {
            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();

            $arena = $user->arena;
            $char = $user->char_data;

            if ($arena->battle_tokens <= 0 && !WormixTrashHelper::isTutorialMissionId($missionId))
            {
                return [
                    'type' => 'ArenaLocked',
                    'data' => new ArenaLocked([
                        'MissionId' => $missionId,
                        'Delay' => $invalidDelayMs,
                    ])
                ];
            }

            // Check if the user has completed previous tutorial
            // +1 cuz tutorials ids are below 0
            if (WormixTrashHelper::isTutorialMissionId($missionId) &&
                $arena->solo_mission_id !== $missionId + 1)
            {
                throw new RequirementsNotMetException("Tutorial is unavailable");
            }

            // Check if the boss is available
            if (WormixTrashHelper::isSoloMissionId($missionId) ||
                WormixTrashHelper::isCoopMissionId($missionId))
            {
                $currentMissionId = WormixTrashHelper::isSoloMissionId($missionId) ?
                    $arena->solo_mission_id : $arena->coop_mission_id;
                // Requested mission id should be below (completed bosses) currentMissionId
                // or equal (uncompleted bosses) to it
                if ($missionId - 1 > $currentMissionId)
                {
                    throw new RequirementsNotMetException("Boss is unavailable");
                }
            }

            $awards = [];
            if ($missionId !== 0)
            {
                $mission = Mission::query()
                    ->where('id', $missionId)
                    ->firstOrFail();
                if ($mission->required_level > $char->level)
                {
                    throw new RequirementsNotMetException();
                }

                $awards = $mission->awards;
            }

            $battle = new UserBattle();
            $battle->user_id = $user->id;
            $battle->mission_id = $missionId;
            $battle->awards = $awards;
            if ($battle->mission_id === 0)
            {
                // Generate reagents for regular battle
                $battle->reagents = WormixTrashHelper::generateBattleReagents();
            }
            $battle->save();

            if(!WormixTrashHelper::isTutorialMissionId($missionId))
            {
                $arena->battle_tokens -= 1;
            }

            $arena->current_battle_id = $battle->id;
            $arena->save();

            return [
                'type' => 'StartBattleResult',
                'data' => new StartBattleResult($battle)
            ];
        }
        catch(RequirementsNotMetException $e)
        {
            Log::warning("Requirements not met: " . $e->getMessage());
            return [
                'type' => 'ArenaLocked',
                'data' => new ArenaLocked([
                    'MissionId' => $missionId,
                    'Delay' => $invalidDelayMs,
                    'ErrorCode' => 502
                ])
            ];
        }
        catch(Exception $e)
        {
            Log::error($e);
            return [
                'type' => 'ArenaLocked',
                'data' => new ArenaLocked([
                    'MissionId' => $missionId,
                    'Delay' => $invalidDelayMs,
                    'ErrorCode' => 500
                ])
            ];
        }
    }

    public function endBattle(EndBattleRequest $request)
    {
        // Client does not give a fuck about result
        // Just send whatever he sends at the end
        $result = [
            'data' => new EndBattleResult([
                'ValidateResult' => EndBattleResult::SUCCESS,
                'Result' => 1,
                'BattleId' => $request->json('BattleId'),
                'MissionId' => $request->json('MissionId'),
            ])
        ];

        try
        {
            DB::beginTransaction();

            $battle = UserBattle::query()
                ->where('id', $request->json('BattleId'))
                ->firstOrFail();

            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();

            $profile = $user->user_profile;
            $arena = $user->arena;
            $char = $user->char_data;

            if ($battle->result !== null)
            {
                Log::warning("Battle is already finished (" . $battle->id . ")");
                DB::rollBack();
                return $result;
            }

            // Check if the user requested EndBattle for someone else
            if ($battle->user_id !== $user->id)
            {
                // todo check co_battle
                throw new SecurityException("Requesting to end another user battle. UID: " . $user->id);
            }

            $currentBattleId = $arena->current_battle_id;

            $arena->current_battle_id = null;
            $arena->save();

            // Check if user is currently in battle he's requesting to end
            // And check if battle mission id matches requested mission id
            if ($currentBattleId !== $battle->id ||
                $battle->mission_id !== $request->json('MissionId'))
            {
                throw new SecurityException("Requesting to end different mission id. UID: " . $user->id);
            }

            $battle->result = $request->json('Result') - $battle->id;
            $battle->type = $request->json('Type');

            if ($battle->mission_id === 0)
            {
                // Award for regular pve battles
                $resultMap = config('wormix.game.missions.result_types');
                if (!isset($resultMap[$battle->result]))
                {
                    throw new InternalServerException("Missing result type " . $battle->result);
                }

                $typesMap = config('wormix.game.missions.types');
                if (!isset($typesMap[$battle->type]))
                {
                    throw new InternalServerException("Missing type " . $battle->type);
                }

                $awards = config('wormix.game.missions.awards');

                $resultKey = $resultMap[$battle->result];
                $typeKey = $typesMap[$battle->type];

                $xp = $awards['experience'][$resultKey][$typeKey];
                $money = $awards['money'][$resultKey][$typeKey];
                if ($xp === null || $money === null)
                {
                    throw new InternalServerException("Xp or money is null, restype: "
                        . $battle->result . ", type: " . $battle->type);
                }

                $char->experience += $xp;
                $char->save();

                $profile->money += $money;
                $profile->save();
            }
            else
            {
                $resultMap = config('wormix.game.missions.result_types');
                if (!isset($resultMap[$battle->result]))
                {
                    throw new InternalServerException("Missing result type " . $battle->result);
                }

                $resultKey = $resultMap[$battle->result];
                if ($resultKey === "win")
                {
                    $mission = Mission::query()
                        ->where('id', $battle->mission_id)
                        ->firstOrFail();

                    $mission->award($user);
                    $mission->next()?->progressUser($user);

                    if (!WormixTrashHelper::isTutorialMissionId($battle->mission_id))
                    {
                        $arena->boss_tokens -= 1;
                        $arena->save();
                    }
                }
            }

            $battle->exp_bonus = $request->json('ExpBonus') ?? 0;
            // Grant xp bonus
            if ($battle->exp_bonus > 0)
            {
                $profile->money +=
                    $battle->exp_bonus * config('wormix.game.missions.star_money_factor');
                $profile->save();

                $char->experience += $battle->exp_bonus;
                $char->save();
            }

            $battle->ban_type = $request->json('BanType');
            $battle->ban_note = $request->json('BanNote') ?? "";

            $battle->collected_reagents = $request->json('CollectedReagents');
            if (WormixTrashHelper::validateBattleReagents($battle->reagents, $battle->collected_reagents))
            {
                $profile->grantReagents(array_count_values($battle->collected_reagents), false);
            }

            $battle->used_items = $request->json('Items') ?? [];
            $profile->consumeItems($battle->used_items);

            // Informational fields, no need to read them currently
            $battle->random_seed = $request->json('RandomSeed');
            $battle->total_turns = $request->json('TotalTurnsCount');
            $battle->total_damage_to_player = $request->json('TotalDamageToPlayer');
            $battle->total_damage_to_boss = $request->json('TotalDamageToBoss');
            $battle->total_used_items = $request->json('TotalUsedItems') ?? [];

            $battle->save();

            DB::commit();

            return $result;
        }
        catch(SecurityException $e)
        {
            Log::error($e->getMessage());
            DB::rollBack();
            return $result;
        }
        catch(\Exception $e)
        {
            Log::error($e);
            DB::rollBack();
            return $result;
        }
    }
}
