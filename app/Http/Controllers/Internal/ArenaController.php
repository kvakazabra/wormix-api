<?php

namespace App\Http\Controllers\Internal;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Arena\EndBattleRequest;
use App\Http\Requests\Internal\Arena\GetArenaRequest;
use App\Http\Requests\Internal\Arena\StartBattleRequest;
use App\Http\Resources\Internal\Arena\ArenaLocked;
use App\Http\Resources\Internal\Arena\ArenaResult;
use App\Models\Wormix\Mission;
use App\Models\Wormix\Reagent;
use App\Models\Wormix\UserBattleInfo;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\WormData;
use Illuminate\Support\Facades\Log;

class ArenaController extends Controller
{
    private const BATTLE_BASE = 1000;

    public function getArena(GetArenaRequest $request)
    {
        $battleInfo = UserBattleInfo::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->first();

        //Add battles (one battle per x minutes)
        if ($battleInfo->battles_count < config('wormix.game.missions.max'))
        {
            $battleInfo->battles_count += min(
                (int)((time() - $battleInfo->last_battle_time) / config('wormix.game.missions.delay')),
                config('wormix.game.missions.max')
            );
            $battleInfo->battles_count = min(
                $battleInfo->battles_count,
                config('wormix.game.missions.max')
            );
            $battleInfo->save();
        }

        if ($battleInfo->battles_count === 0)
        {
            return [
                'type' => 'ArenaLocked',
                'data' => new ArenaLocked($battleInfo)
            ];
        }
        return [
            'type' => 'ArenaResult',
            'data' => new ArenaResult($battleInfo, $request->json('ReturnUsersProfiles'))
        ];
    }

    public function startBattle(StartBattleRequest $request)
    {
        $battleInfo = UserBattleInfo::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->first();

        if ($battleInfo->battles_count <= 0)
        {
            return response([
                'message' => 'Not enough battles'
            ], 422);
        }

        if ($request->json('MissionId') !== 0)
        {
            if (
                //Check for bosses
                ($request->json('MissionId') > ($battleInfo->last_mission_id + 1)) ||

                //Check for lessons
                ($request->json('MissionId') !== ($battleInfo->last_mission_id - 1) && $battleInfo->last_mission_id < -1)
            )
            {
                return response([
                    'message' => 'Try to start invalid mission'
                ], 422);
            }

            $mission = Mission::query()->where('mission_id', $request->json('MissionId'))->first();
            $wormData = WormData::query()->where('owner_id', $request->json('internal_user_id'))->first();
            if ($wormData->level < $mission->required_level)
            {
                return response([
                    'message' => 'Mission required level mismatch'
                ], 422);
            }
        }

        $battleInfo->last_battle_time = time();

        if ($request->json('MissionId') >= 0)
        {
            $battleInfo->battles_count -= 1;
        }

        $battleInfo->current_battle_id = self::BATTLE_BASE + $battleInfo->user_id + $request->json('MissionId');

        //Random reagents generation
        srand(time());
        $reagents = [];
        if ($request->json('MissionId') >= 0)
        {
            $reagents = Reagent::query()->select('reagent_id')->pluck('reagent_id')->random(rand(0, 3))->toArray();
        }

        if ($request->json('MissionId') === 0)
        {
            $battleInfo->battle_type = 0;
            $awards = [];
        }
        else
        {
            $battleInfo->battle_type = 1;
            $awards = Mission::query()
                ->where('mission_id', $request->json('MissionId'))
                ->first()
                ->awards;
        }

        $battleInfo->mission_id = $request->json('MissionId');
        $battleInfo->awards = [
            'reagents' => $reagents,
            'awards'   => $awards
        ];
        $battleInfo->save();

        return [
            'data' => [
                'Awards' => $awards,
                'BattleId' => $battleInfo->current_battle_id,
                'ReagentForBattle' => $reagents
            ]
        ];
    }

    public function endBattle(EndBattleRequest $request)
    {
        $battleInfo =
            UserBattleInfo::query()
                ->where('user_id', $request->json('internal_user_id'))
                ->first();

        $result = $request->json('Result') - $battleInfo->current_battle_id;
        if (abs($result) >= 2)
        {
            Log::debug("Invalid battle id", [
                'request' => $request->json('BattleId'),
                'current' => $battleInfo->current_battle_id,
            ]);
            return response([]);
        }

        if ($request->json('MissionId') !== $battleInfo->mission_id)
        {
            Log::debug("Invalid mission id", [
                'request' => $request->json('MissionId'),
                'current' => $battleInfo->mission_id,
            ]);
            return response([]);
        }

        $this->processBattleResult($request, $result, $battleInfo);
        return [
            'Status' => 'OK'
        ];
    }

    private function processBattleResult(EndBattleRequest $request, int $result, UserBattleInfo $battleInfo)
    {
        $wormData = WormData::query()->where('owner_id', $battleInfo->user_id)->first();
        $userInfo = UserProfile::query()->where('user_id', $battleInfo->user_id)->first();

        if ($battleInfo->battle_type === 0)
        {
            $wormData->experience += $request->json('ExpBonus');
        }

        //Decrease used weapons
        foreach ($request->json('Items') as $item)
        {
            if ($item['Count'] <= 0)
            {
                continue;
            }
            $userWeapon = UserItem::query()
                ->where('owner_id', $battleInfo->user_id)
                ->where('item_id', $item['Id'])
                ->where('count', '!=', -1)
                ->first();

            $userWeapon->count -= $item['Count'];
            if ($userWeapon->count < 0)
            {
                $userWeapon->count = 0;
            }
            $userWeapon->save();
        }

        $mission = null;
        $twiceRun = false;

        switch ($result)
        {
            case 0: //Draw
                if ($battleInfo->battle_type === 0)
                {
                    switch ($request->json('Type'))
                    {
                        case 0: //MyLevel draw
                            $userInfo->money += config('wormix.game.missions.awards.draw.money.medium');
                            $wormData->experience += config('wormix.game.missions.awards.draw.experience.medium');
                            break;
                        case 1: //High level draw
                            $userInfo->money += config('wormix.game.missions.awards.draw.money.high');
                            $wormData->experience += config('wormix.game.missions.awards.draw.experience.high');
                            break;
                        case 2: //Low level draw
                            $userInfo->money += config('wormix.game.missions.awards.draw.money.low');
                            $wormData->experience += config('wormix.game.missions.awards.draw.experience.low');
                            break;
                    }
                }
                break;
            case 1: //Winner
                if ($battleInfo->battle_type === 1)
                {
                    $mission = Mission::query()->where('mission_id', $battleInfo->mission_id)
                        ->first();
                    if ($battleInfo->mission_id < 0)
                    {
                        $battleInfo->last_mission_id = $battleInfo->mission_id;
                    }
                    else
                    {
                        $twiceRun = $battleInfo->mission_id - $battleInfo->last_mission_id <= 0;
                        if (!$twiceRun)
                        {
                            $battleInfo->last_mission_id = $battleInfo->mission_id;
                        }
                        $battleInfo->last_boss_fight_time = time();
                    }
                }
                else
                {
                    switch ($request->json('Type'))
                    {
                        case 0: //MyLevel win
                            $userInfo->money += config('wormix.game.missions.awards.win.money.medium');
                            $wormData->experience += config('wormix.game.missions.awards.win.experience.medium');
                            break;
                        case 1: //High level win
                            $userInfo->money += config('wormix.game.missions.awards.win.money.high');
                            $wormData->experience += config('wormix.game.missions.awards.win.experience.high');
                            break;
                        case 2: //Low level win
                            $userInfo->money += config('wormix.game.missions.awards.win.money.low');
                            $wormData->experience += config('wormix.game.missions.awards.win.experience.low');
                            break;
                    }
                }
                break;
            case -1: //Looser
                if ($battleInfo->battle_type == 0)
                {
                    $userInfo->money += config('wormix.game.missions.awards.loose.money');
                    $wormData->experience += config('wormix.game.missions.awards.loose.experience');
                }
                break;
        }

        $userInfo->save();
        $wormData->save();

        $valid = true;
        foreach ($request->json('CollectedReagents') as $reagent)
        {
            if (!in_array($reagent, $battleInfo->awards['reagents']))
            {
                $valid = false;
                break;
            }
        }

        if ($valid)
        {
            WormixTrashHelper::addReagents($userInfo, $request->json('CollectedReagents'));
        }

        if ($mission !== null)
        {
            $this->processAwards($mission, $twiceRun, $userInfo, $wormData);
        }

        $battleInfo->current_battle_id = 0;
        $battleInfo->mission_id = 0;
        $battleInfo->awards = [];
        $battleInfo->save();
    }

    private function processAwards(Mission $mission, bool $isDouble, UserProfile $userProfile, WormData $wormData) : void
    {
        $awards = $mission->awards;
        if (count($awards) === 0)
        {
            return;
        }

        if (count($awards) === 1)
        {
            $awards = $awards[0];
        }
        elseif (!$isDouble)
        {
            $awards = $awards[0];
        }
        else
        {
            $awards = $awards[1];
        }

        $userProfile->money += $awards['money'];
        $userProfile->real_money += $awards['real_money'];
        $userProfile->save();

        WormixTrashHelper::addWeaponsAwards($awards['weapons'], $wormData);

        $wormData->experience += $awards['experience'];
        $wormData->save();
    }
}