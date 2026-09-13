<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Shop\BuyBattleRequest;
use App\Http\Requests\Internal\Shop\BuyRaceRequest;
use App\Http\Requests\Internal\Shop\BuyReactionRateRequest;
use App\Http\Requests\Internal\Shop\BuyShopItemsRequest;
use App\Http\Requests\Internal\Shop\BuySkinRequest;
use App\Http\Requests\Internal\Shop\UnlockMissionRequest;
use App\Http\Resources\Internal\Shop\BuyRaceResult;
use App\Http\Resources\Internal\Shop\BuyBattleResult;
use App\Http\Resources\Internal\Shop\BuyReactionRateResult;
use App\Http\Resources\Internal\Shop\BuySkinResult;
use App\Http\Resources\Internal\Shop\ShopResult;
use App\Http\Resources\Internal\Shop\UnlockMissionResult;
use App\Models\User;
use App\Models\Wormix\CharData;
use App\Models\Wormix\Equipment;
use App\Models\Wormix\Mission;
use App\Models\Wormix\Race;
use App\Models\Wormix\UserBattleInfo;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\Weapon;
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
                if ($weapon->hide_in_shop)
                {
                    throw new \Exception("Weapon: Attempt to buy hidden item!");
                }

                if (!$weapon->infinite && $shopItems["{$weapon->id}"]['Count'] === -1)
                {
                    return new ShopResult(Collection::empty(), ShopResult::Error);
                }

                if ($shopItems["{$weapon->id}"]['MoneyType'] === 0)
                {
                    if ($weapon->real_price === 0)
                    {
                        return new ShopResult(Collection::empty(), ShopResult::Error);
                    }

                    $realSum += $weapon->infinite ?
                        $weapon->real_price :
                        $weapon->real_price * $shopItems["{$weapon->id}"]['Count'];
                }

                if ($shopItems["{$weapon->id}"]['MoneyType'] === 1)
                {
                    if ($weapon->price === 0)
                    {
                        return new ShopResult(Collection::empty(), ShopResult::Error);
                    }

                    $sum += $weapon->infinite ?
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
                $wormData = CharData::query()
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
                    if ($oldWeapon->weapon->infinite)
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
                'char_data',
                'user_profile'
            ])
            ->first();

        $mission = Mission::query()
            ->where('mission_id', $request->json('MissionId'))
            ->first();

        $mission_price =
            ($request->json('MissionId') - 1 - $user->battle_info->last_mission_id)
            * config('wormix.game.buy.boss_mission');

        if ($user->char_data->level < $mission->required_level ||
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

    public function buyRace(BuyRaceRequest $request)
    {
        $race = Race::query()
            ->where('race_id', $request->json('RaceId'))
            ->first();

        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->first();
        $char = $user->char_data;
        $profile = $user->user_profile;

        // Prevent non-playable races from being bought
        // Those are temporary like donut, crab and etc. (excluding alien)
        if (!$race->playable) {
            return [
                'data' => new BuyRaceResult($char, BuyRaceResult::NOT_FOR_SALE)
            ];
        }

        // Race already bought
        if (in_array($race->race_id, $char->races))
        {
            return [
                'data' => new BuyRaceResult($char, BuyRaceResult::ERROR)
            ];
        }

        // Take the money
        switch ($request->json('MoneyType'))
        {
            case 0:
            {
                // Check the required level (only for in-game money)
                if ($race->required_level > $char->level)
                {
                    return [
                        'data' => new BuyRaceResult($char,
                            BuyRaceResult::MIN_REQUIREMENTS_ERROR)
                    ];
                }

                if ($race->price > $profile->money)
                {
                    return [
                        'data' => new BuyRaceResult($char,
                            BuyRaceResult::NOT_ENOUGH_MONEY)
                    ];
                }

                $profile->money -= $race->price;
                $profile->save();
                break;
            }
            case 1:
            {
                if ($race->real_price > $profile->real_money)
                {
                    return [
                        'data' => new BuyRaceResult($char,
                            BuyRaceResult::NOT_ENOUGH_MONEY)
                    ];
                }

                $profile->real_money -= $race->real_price;
                $profile->save();
                break;
            }
            default:
            {
                return [
                    'data' => new BuyRaceResult($char, BuyRaceResult::ERROR)
                ];
            }
        }

        // Add bought race and set it
        $races = $char->races;
        $races[] = $race->race_id;
        $char->races = $races;
        $char->race = $race->race_id;
        $char->save();

        return [
            'data' => new BuyRaceResult($char, BuyRaceResult::SUCCESS)
        ];
    }

    public function buySkin(BuySkinRequest $request)
    {
        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->first();
        $char = $user->char_data;
        $profile = $user->user_profile;

        $skinId = $request->json('SkinId');
        $raceId = intdiv($skinId, 10);

        $race = Race::query()
            ->where('race_id', $raceId)
            ->first();
        if ($race === null)
        {
            return [
                'data' => new BuySkinResult($char, BuySkinResult::ERROR)
            ];
        }

        // Prevent buying skin for non-bought race
        if (!in_array($raceId, $char->races))
        {
            return [
                'data' => new BuySkinResult($char, BuySkinResult::ERROR)
            ];
        }

        switch ($request->json('MoneyType'))
        {
            case 0: // real money
            {
                $price = config('wormix.game.race.skin_real_price');
                if ($price > $profile->real_money)
                {
                    return [
                        'data' => new BuySkinResult($char, BuySkinResult::NOT_ENOUGH_MONEY)
                    ];
                }

                $profile->real_money -= $price;
                $profile->save();
                break;
            }
            default:
            case 3: // Mutagen, todo
            {
                return [
                    'data' => new BuySkinResult($char, BuySkinResult::ERROR)
                ];
            }
        }

        $skins = $char->skins;
        $skins[] = $skinId;
        $char->skins = $skins;
        // Check if the skin's race is currently selected
        // If it is - set the skin
        if ($char->race == $raceId)
        {
            $char->skin = $skinId;
        }
        $char->save();

        return [
            'data' => new BuySkinResult($char, BuySkinResult::SUCCESS)
        ];
    }
}
