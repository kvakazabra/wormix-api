<?php

namespace App\Http\Controllers\Internal;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\House\PumpReactionRateRequest;
use App\Http\Requests\Internal\House\PumpReactionsRateRequest;
use App\Http\Requests\Internal\House\SearchTheHouseRequest;
use App\Http\Resources\Internal\House\PumpReactionTheHouseResult;
use App\Http\Resources\Internal\House\SearchTheHouseResult;
use App\Models\Wormix\HouseAction;
use App\Models\Wormix\Reagent;
use App\Models\Wormix\UserProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class HouseController extends Controller
{
    public function pumpReactions(PumpReactionsRateRequest $request)
    {
        $result = [];

        foreach ($request->json('FriendsIds') as $friendId)
        {
            if (HouseAction::query()
                    ->where('user_id', $request->json('internal_user_id'))
                    ->where('to_user_id', $friendId)
                    ->where('action_type', 0)
                    ->where('created_at', '>', now()->subDay())
                    ->exists() ||
                !HouseAction::query()
                    ->where('user_id', $friendId)
                    ->where('to_user_id', $request->json('internal_user_id'))
                    ->where('action_type', 0)
                    ->where('created_at', '>', now()->subDays(3))
                    ->exists())
            {
                continue;
            }

            $result[] = [
                'FriendId' => $friendId,
                'Result' => 0
            ];

            $userProfile = UserProfile::query()
                ->where('user_id', $friendId)
                ->first();

            $userProfile->reaction_rate += 1;
            $userProfile->save();

            $action = new HouseAction();
            $action->action_type = 0;
            $action->user_id = $request->json('internal_user_id');
            $action->to_user_id = $friendId;
            $action->save();
        }

        return [
            'data' => [
                'PumpedFriends' => $result
            ]
        ];
    }

    public function pumpReaction(PumpReactionRateRequest $request)
    {
        if ($request->json('internal_user_id') === $request->json('FriendId'))
        {
            return new PumpReactionTheHouseResult(Collection::empty(), PumpReactionTheHouseResult::Error);
        }

        $pumpReactionFriend = HouseAction::query()
            ->where('to_user_id', $request->json('FriendId'))
            ->where('user_id', $request->json('internal_user_id'))
            ->where('action_type', 0)
            ->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-1 day')))
            ->first();

        if ($pumpReactionFriend !== null)
        {
            return new PumpReactionTheHouseResult(Collection::empty(), PumpReactionTheHouseResult::TodayAlreadyPumped);
        }

        $toUserProfile = UserProfile::query()->where('user_id', $request->json('FriendId'))
            ->first();

        $toUserProfile->reaction_rate += 1;
        $toUserProfile->save();

        $newAction = new HouseAction();
        $newAction->user_id = $request->json('internal_user_id');
        $newAction->to_user_id = $request->json('FriendId');
        $newAction->action_type = 0;
        $newAction->save();

        return new PumpReactionTheHouseResult(Collection::empty(), PumpReactionTheHouseResult::Ok);
    }

    public function searchTheHouse(SearchTheHouseRequest $request)
    {
        $searchAction = new HouseAction();
        $searchAction->user_id = $request->json('internal_user_id');
        $searchAction->to_user_id = $request->json('FriendId');
        $searchAction->action_type = 1;

        if ($request->json('internal_user_id') === $request->json('FriendId'))
        {
            return new SearchTheHouseResult($searchAction, SearchTheHouseResult::Error, 0);
        }

        $searchKeys = WormixTrashHelper::getSearchKeys($request->json('internal_user_id'));

        if ($searchKeys <= 0)
        {
            return new SearchTheHouseResult($searchAction, SearchTheHouseResult::KeyLimitExceed, 0);
        }

        if (WormixTrashHelper::isSearchedToday($request->json('internal_user_id'), $request->json('FriendId')))
        {
            return new SearchTheHouseResult($searchAction, SearchTheHouseResult::Empty, 0);
        }

        $userProfile = UserProfile::query()->where('user_id', $request->json('internal_user_id'))->first();
        $searchAction->save();

        srand(time());

        switch (rand(0, 3))
        {
            case 0: //Empty
                return [
                    'data' => new SearchTheHouseResult($searchAction, SearchTheHouseResult::Empty, 0)
                ];

            case 1: //Money
                $money = rand(1, 20);
                $userProfile->money += $money;
                $userProfile->save();
                return [
                    'data' => new SearchTheHouseResult($searchAction, SearchTheHouseResult::Money, $money)
                ];

            case 2: //Real money
                $money = rand(1, 5);
                $userProfile->real_money += $money;
                $userProfile->save();
                return [
                    'data' => new SearchTheHouseResult($searchAction, SearchTheHouseResult::RealMoney, $money)
                ];

            case 3: //Reagent
                $reagents = Reagent::query()->select('reagent_id')->pluck('reagent_id')->toArray();
                $reagent = $reagents[array_rand($reagents)];
                WormixTrashHelper::addReagents($userProfile, [$reagent]);
                return [
                    'data' => new SearchTheHouseResult($searchAction, SearchTheHouseResult::Reagent, $reagent)
                ];
        }

        return new SearchTheHouseResult($searchAction, SearchTheHouseResult::Error, 0);
    }
}