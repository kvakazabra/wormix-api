<?php

namespace App\Http\Controllers\Internal;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Shop\BuyBattleRequest;
use App\Http\Requests\Internal\Shop\BuyReactionRateRequest;
use App\Http\Requests\Internal\Shop\BuyShopItemsRequest;
use App\Http\Requests\Internal\Shop\ChangeRaceRequest;
use App\Http\Requests\Internal\Shop\UnlockMissionRequest;
use App\Http\Resources\Internal\Shop\BuyBattleResult;
use App\Http\Resources\Internal\Shop\BuyReactionRateResult;
use App\Http\Resources\Internal\Shop\ChangeRaceResult;
use App\Http\Resources\Internal\Shop\ShopResult;
use App\Http\Resources\Internal\Shop\UnlockMissionResult;
use App\Models\User;
use App\Models\Wormix\Equipment;
use App\Models\Wormix\Mission;
use App\Models\Wormix\Race;
use App\Models\Wormix\UserBattleInfo;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\Weapon;
use App\Models\Wormix\WormData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    public function buyItems(BuyShopItemsRequest $request)
    {
        try
        {
            $shopItems = [];
            foreach ($request->json('ShopItems') as $item)
            {
                $shopItems["{$item['Id']}"] = [
                    'Count' => $item['Count'],
                    'MoneyType' => $item['MoneyType'],
                ];
            }
            $sum = 0;
            $realSum = 0;

            foreach (Weapon::query()
                         ->whereIn('id', array_keys($shopItems))
                         ->get() as $weapon)
            {
                if ($weapon->hide_in_shop && $weapon->ref_id === null)
                {
                    throw new \Exception("Weapon: Attempt to buy hidden item!");
                }

                if (!$weapon->infinity && $shopItems["{$weapon->id}"]['Count'] === -1)
                {
                    return new ShopResult(Collection::empty(), ShopResult::Error);
                }

                if ($shopItems["{$weapon->id}"]['MoneyType'] === 0)
                {
                    if ($weapon->real_price === 0)
                    {
                        return new ShopResult(Collection::empty(), ShopResult::Error);
                    }

                    $realSum += $weapon->infinity ?
                        $weapon->real_price :
                        $weapon->real_price * $shopItems["{$weapon->id}"]['Count'];
                }

                if ($shopItems["{$weapon->id}"]['MoneyType'] === 1)
                {
                    if ($weapon->price === 0)
                    {
                        return new ShopResult(Collection::empty(), ShopResult::Error);
                    }

                    $sum += $weapon->infinity ?
                        $weapon->price :
                        $weapon->price * $shopItems["{$weapon->id}"]['Count'];
                }

                // todo: add required_* validation
            }

            // todo: this doesnt work properly with multiple hat purchases on 1.05.0
            $lastEquipmentId = -1;
            foreach (Equipment::query()
                         ->whereIn('id', array_keys($shopItems))
                         ->get() as $equipment)
            {
                if ($equipment->hide_in_shop || $equipment->duration > 0)
                {
                    throw new \Exception("Equipment: Attempt to buy hidden item or temporary item!");
                }

                if ($shopItems["{$equipment->id}"]['Count'] !== -1)
                {
                    throw new \Exception("Equipment: Count for hats must be -1!");
                }

                switch ($shopItems["{$equipment->id}"]['MoneyType'])
                {
                    case 0:
                        if ($equipment->real_price === 0)
                        {
                            return new ShopResult(Collection::empty(), ShopResult::Error);
                        }

                        $realSum += $equipment->real_price;
                        break;
                    case 1:
                        if ($equipment->price === 0)
                        {
                            return new ShopResult(Collection::empty(), ShopResult::Error);
                        }

                        $sum += $equipment->price;
                        break;
                    default:
                        Log::error("Naturoi ne oplacivaetsa");
                        return new ShopResult(Collection::empty(), ShopResult::Error);
                }

                $lastEquipmentId = $equipment->id;
                // todo: add required_* validation
            }

            $userProfile = UserProfile::query()
                ->where('user_id', $request->json('internal_user_id'))
                ->first();
            if ($userProfile->money < $sum || $userProfile->real_money < $realSum)
            {
                return new ShopResult(Collection::empty(), ShopResult::NotEnoughMoney);
            }

            $userProfile->money -= $sum;
            $userProfile->real_money -= $realSum;
            $userProfile->save();

            if ($lastEquipmentId !== -1)
            {
                $wormData = WormData::query()
                    ->where('owner_id', $userProfile->user_id)
                    ->first();
                $wormData->hat = $lastEquipmentId;
                $wormData->save();
            }

            $newWeapons = Collection::empty();
            foreach ($request->json('ShopItems') as $item)
            {
                $oldWeapon = UserItem::query()
                    ->where('owner_id', $request->json('internal_user_id'))
                    ->where('item_id', $item['Id'])
                    ->first();

                if ($item['Count'] == -1 || $oldWeapon === null)
                {
                    $userWeapon = new UserItem();
                    $userWeapon->owner_id = $request->json('internal_user_id');
                    $userWeapon->item_id = $item['Id'];
                    $userWeapon->item_type = UserItem::itemTypeForId($userWeapon->item_id);
                    $userWeapon->count = $item['Count'];
                    $userWeapon->save();
                    $newWeapons->add($userWeapon);
                }
                else
                {
                    if ($oldWeapon->weapon->infinity)
                    {
                        $oldWeapon->count = $item['Count'];
                    }
                    else
                    {
                        $oldWeapon->count += $item['Count'];
                    }

                    $oldWeapon->save();

                    $oldWeapon->count = $item['Count'];
                    $newWeapons->add($oldWeapon);
                }
            }

            return new ShopResult($newWeapons, ShopResult::Success);
        }
        catch (\Exception $ex)
        {
            Log::error("Internal exception", [
                'exception' => $ex,
            ]);
            return new ShopResult(Collection::empty(), ShopResult::Error);
        }
    }

    public function changeRace(ChangeRaceRequest $request)
    {
        $userWorm = WormData::query()
            ->where('owner_id', $request->json('internal_user_id'))
            ->first();
        $userProfile = UserProfile::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->first();
        $race = Race::query()
            ->where('race_id', $request->json('RaceId'))
            ->first();

        if ($userWorm->race === $request->json('RaceId'))
        {
            return new ChangeRaceResult(Collection::empty(), ChangeRaceResult::Error);
        }

        if (($request->json('MoneyType') === 1 && $userWorm->level < $race->required_level) ||
            ($request->json('MoneyType') === 0 && $userProfile->real_money < $race->real_price) ||
            ($request->json('MoneyType') === 1 && $userProfile->money < $race->price))
        {
            return new ChangeRaceResult(Collection::empty(), ChangeRaceResult::MinRequirementsError);
        }

        if ($request->json('MoneyType') === 0)
        {
            $userProfile->real_money -= $race->real_price;
        }
        else
        {
            $userProfile->money -= $race->price;
        }

        $userProfile->save();

        $userWorm->race = $request->json('RaceId');
        $userWorm->save();

        return new ChangeRaceResult(Collection::empty(), ChangeRaceResult::Success);
    }

    public function buyReaction(BuyReactionRateRequest $request)
    {
        $userProfile = UserProfile::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->first();

        if ($request->json('ReactionRateCount') % 3 !== 0 ||
            $userProfile->real_money < $request->json('ReactionRateCount') / 3)
        {
            return [
                'data' => new BuyReactionRateResult(Collection::empty(),
                    BuyReactionRateResult::Error, 0)
            ];
        }

        $userProfile->real_money -= $request->json('ReactionRateCount') / 3;
        $userProfile->reaction_rate += $request->json('ReactionRateCount');
        $userProfile->save();

        return [
            'data' => new BuyReactionRateResult(Collection::empty(),
                BuyReactionRateResult::Success, $request->json('ReactionRateCount'))
        ];
    }

    public function buyBattle(BuyBattleRequest $request)
    {
        $battleInfo = UserBattleInfo::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->first();

        $userProfile = UserProfile::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->first();

        if ($battleInfo->battles_count >= config('wormix.game.missions.max'))
        {
            return [
                'data' => new BuyBattleResult(Collection::empty(),
                    BuyBattleResult::Error)
            ];
        }

        if (($request->json('MoneyType') === 0 &&
                $userProfile->real_money < config('wormix.game.missions.buy.real_money')) ||
            ($request->json('MoneyType') === 1 &&
                $userProfile->money < config('wormix.game.missions.buy.money')))
        {
            return [
                'data' => new BuyBattleResult(Collection::empty(), BuyBattleResult::NotEnoughMoney)
            ];
        }

        $battleInfo->battles_count += 1;
        $battleInfo->save();

        if ($request->json('MoneyType') === 0)
        {
            $userProfile->real_money -= config('wormix.game.missions.buy.real_money');
        }
        else
        {
            $userProfile->money -= config('wormix.game.missions.buy.money');
        }

        $userProfile->save();

        return [
            'data' => new BuyBattleResult(Collection::empty(),
                BuyBattleResult::Success)
        ];
    }

    public function unlockMission(UnlockMissionRequest $request)
    {
        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->with([
                'battle_info',
                'worm_data',
                'user_profile'
            ])
            ->first();

        $mission = Mission::query()
            ->where('mission_id', $request->json('MissionId'))
            ->first();

        $mission_price =
            ($request->json('MissionId') - 1 - $user->battle_info->last_mission_id)
            * config('wormix.game.buy.boss_mission');

        if ($user->worm_data->level < $mission->required_level ||
            $user->battle_info->last_mission_id >= $mission->mission_id ||
            $user->user_profile->real_money < $mission_price)
        {
            return [
                'data' => new UnlockMissionResult(Collection::empty(),
                    UnlockMissionResult::Error)
            ];
        }

        $battleInfo = $user->battle_info;
        $userProfile = $user->user_profile;

        $battleInfo->last_mission_id = $request->json('MissionId') - 1;
        $userProfile->real_money -= $mission_price;
        $userProfile->save();
        $battleInfo->save();

        return [
            'data' => new UnlockMissionResult(Collection::empty(),
                UnlockMissionResult::Success)
        ];
    }

}
