<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Account\DistributePointsRequest;
use App\Http\Requests\Internal\Account\GetProfilesRequest;
use App\Http\Requests\Internal\Account\SelectRaceRequest;
use App\Http\Resources\Internal\Account\BuySelectRaceResult;
use App\Http\Resources\Internal\Account\DistributePointsResult;
use App\Http\Resources\Internal\Account\ProfilesResult;
use App\Http\Resources\Internal\Account\SelectRaceResult;
use App\Models\User;
use App\Models\Wormix\CharData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class InternalAccountController extends Controller
{
    public function distributePoints(DistributePointsRequest $request)
    {
        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->firstOrFail();

        $char = $user->char();

        $availablePoints = $char->level * 2 - ($char->armor + $char->attack);
        $totalPoints = $request->json('Armor') + $request->json('Attack');
        if ($availablePoints < $totalPoints)
        {
            return [
                'data' => new DistributePointsResult(Collection::empty(),
                    DistributePointsResult::NotEnoughPoints)
            ];
        }

        $char->armor += $request->json('Armor');
        $char->attack += $request->json('Attack');
        $char->save();

        return [
            'data' => new DistributePointsResult(Collection::empty(),
                DistributePointsResult::Success)
        ];
    }

    // Returns SelectRaceResult
    public function selectRaceCommon(SelectRaceRequest $request) : int
    {
        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->first();
        $profile = $user->profile;
        $char = $user->char();

        $race = $request->json('RaceId');
        $skin = $request->json('SkinId');

        // Check if the race has been bought
        if (!in_array($race, $profile->races))
        {
            return SelectRaceResult::ERROR;
        }

        // Check if the skin has been bought
        if ($skin != 0 && !in_array($skin, $profile->skins))
        {
            return SelectRaceResult::ERROR;
        }

        $raceIsChanging = $race != $char->race;

        // Check if free change is available
        // If it's not then deduct a price from users account
        // Also the logic must be tweaked a bit when VIP will get available
        $freeRaceChangeInterval = config('wormix.game.race.free_change_interval');
        $lastRaceChangeTimestamp = $profile->race_change_timestamp;
        if ($lastRaceChangeTimestamp + $freeRaceChangeInterval > time() &&
            $raceIsChanging)
        {
            $realPrice = config('wormix.game.race.change_real_price');
            if ($realPrice > $profile->real_money)
            {
                return SelectRaceResult::ERROR;
            }

            $profile->real_money -= $realPrice;
            $profile->save();
        }

        // Don't update the time in case only a skin was changed
        if ($raceIsChanging)
        {
            $profile->race_change_timestamp = time();
            $profile->save();
        }

        $char->race = $race;
        $char->skin = $skin;
        $char->save();

        return SelectRaceResult::SUCCESS;
    }

    public function selectRacePaid(SelectRaceRequest $request)
    {
        $result = $this->selectRaceCommon($request);

        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->firstOrFail();

        $char = $user->char();

        return [
            'data' => new BuySelectRaceResult($char, $result)
        ];
    }

    public function selectRace(SelectRaceRequest $request)
    {
        $result = $this->selectRaceCommon($request);

        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->firstOrFail();

        $char = $user->char();

        return [
            'data' => new SelectRaceResult($char, $result)
        ];
    }

    public function getProfiles(GetProfilesRequest $request)
    {
        try
        {
            $ids = $request->json('Ids');

            foreach($ids as &$id)
            {
                $id = (int)$id;
            }

            return [
                'data' => new ProfilesResult($ids)
            ];
        }
        catch(\Exception $e)
        {
            Log::error($e);
            return [
                'data' => new ProfilesResult(Collection::empty())
            ];
        }
    }
}
