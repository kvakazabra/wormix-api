<?php

namespace App\Http\Controllers\Internal;

use App\Exceptions\Wormix\AlreadyBoughtException;
use App\Exceptions\Wormix\NotEnoughMoneyException;
use App\Helpers\Wormix\WormixTrashHelper;
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
use App\Models\Wormix\UserItem;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\Weapon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class ShopController extends Controller
{
    /**
     * @return array Real money, money
     * @throws Exception
     */
    public function countWeaponsPrices(SupportCollection $items) : array
    {
        $totalReal = 0;
        $total = 0;

        /** @var Weapon $weapon */
        foreach (Weapon::query()
                     ->whereIn('id', $items->keys())
                     ->get() as $weapon)
        {
            $count = $items[$weapon->id]['Count'] ?? 0;
            $moneyType = $items[$weapon->id]['MoneyType'] ?? -1;

            $isPureInfiniteWeapon = $weapon->infinite && !$weapon->is_complex;

            if ($weapon->hide_in_shop)
            {
                throw new Exception("Weapon: Attempt to buy hidden item");
            }

            if ($count === 0)
            {
                throw new Exception("Weapon: tried to buy with count 0");
            }

            if (!$isPureInfiniteWeapon && $count < 0)
            {
                throw new Exception("Weapon: tried to buy count<0 finite or complex items");
            }

            switch ($moneyType)
            {
                case 0:
                {
                    if ($weapon->real_price === 0)
                    {
                        throw new Exception("Weapon: Real price is not set");
                    }

                    $totalReal += $isPureInfiniteWeapon ?
                        $weapon->real_price :
                        $weapon->real_price * abs($count);
                    break;
                }
                case 1:
                {
                    if ($weapon->price === 0)
                    {
                        throw new Exception("Weapon: Price is not set");
                    }

                    $total += $isPureInfiniteWeapon ?
                        $weapon->price :
                        $weapon->price * abs($count);
                    break;
                }
                default:
                    throw new Exception("Weapon: Bad money type");
            }

            // todo: add required_* validation
        }

        return [$totalReal, $total];
    }

    /**
     * @return array Real total, total, last hat id, error
     * @throws Exception
     */
    public function countEquipmentsPrices(SupportCollection $items) : array
    {
        $totalReal = 0;
        $total = 0;
        $lastEquipmentId = 0;

        /** @var Equipment $equipment */
        foreach (Equipment::query()
                     ->whereIn('id', $items->keys())
                     ->get() as $equipment)
        {
            $count = $items[$equipment->id]['Count'] ?? 0;
            $moneyType = $items[$equipment->id]['MoneyType'] ?? -1;

            if ($count !== -1)
            {
                throw new Exception("Equipment: Count must be -1!");
            }

            if ($equipment->hide_in_shop || $equipment->duration > 0)
            {
                throw new Exception("Equipment: Attempt to buy hidden item or temporary item!");
            }

            switch ($moneyType)
            {
                case 0:
                    if ($equipment->real_price === 0)
                    {
                        throw new Exception("Equipment: Real price is not set");
                    }

                    $totalReal += $equipment->real_price;
                    break;
                case 1:
                    if ($equipment->price === 0)
                    {
                        throw new Exception("Equipment: Price is not set");
                    }

                    $total += $equipment->price;
                    break;
                default:
                    throw new Exception("Equipment: Bad money type");
            }

            $lastEquipmentId = $equipment->id;

            // todo: add required_* validation
        }

        return [$totalReal, $total, $lastEquipmentId];
    }

    public function buyItems(BuyShopItemsRequest $request)
    {
        try
        {
            DB::beginTransaction();

            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();
            $profile = $user->user_profile;

            $items = collect($request->json('ShopItems'))->keyBy('Id');

            // Calculate total prices
            // Although since some version of the game
            // Buying items is an immediate operation
            // I would still prefer to process it as a bunch of items
            [$weaponTotalReal, $weaponTotal] =
                $this->countWeaponsPrices($items);
            [$equipmentTotalReal, $equipmentTotal, $lastEquipmentId] =
                $this->countEquipmentsPrices($items);

            $totalReal = $weaponTotalReal + $equipmentTotalReal;
            $total = $equipmentTotal + $weaponTotal;

            if ($total > $profile->money || $totalReal > $profile->real_money)
            {
                throw new NotEnoughMoneyException();
            }

            $profile->money -= $total;
            $profile->real_money -= $totalReal;
            $profile->save();

            // todo this is bullshit
            // Convert it another way, to associative pairs [[id => count], ...]
            $items = collect($request->json('ShopItems'))
                ->pluck('Count', 'Id')
                ->toArray();
            $profile->grantItems($items);

            DB::commit();

            return new ShopResult($items, ShopResult::Success);
        }
        catch(NotEnoughMoneyException $e)
        {
            DB::rollBack();
            return new ShopResult(Collection::empty(),
                ShopResult::NotEnoughMoney);
        }
        catch(Throwable $t)
        {
            DB::rollBack();
            Log::error($t);
            return new ShopResult(Collection::empty(),
                ShopResult::Error);
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
//        $battleInfo = UserBattleInfo::query()
//            ->where('user_id', $request->json('internal_user_id'))
//            ->first();
//
//        $userProfile = UserProfile::query()
//            ->where('user_id', $request->json('internal_user_id'))
//            ->first();
//
//        if ($battleInfo->battles_count >= config('wormix.game.missions.max'))
//        {
//            return [
//                'data' => new BuyBattleResult(Collection::empty(),
//                    BuyBattleResult::Error)
//            ];
//        }
//
//        if (($request->json('MoneyType') === 0 &&
//                $userProfile->real_money < config('wormix.game.missions.buy.real_money')) ||
//            ($request->json('MoneyType') === 1 &&
//                $userProfile->money < config('wormix.game.missions.buy.money')))
//        {
//            return [
//                'data' => new BuyBattleResult(Collection::empty(), BuyBattleResult::NotEnoughMoney)
//            ];
//        }
//
//        $battleInfo->battles_count += 1;
//        $battleInfo->save();
//
//        if ($request->json('MoneyType') === 0)
//        {
//            $userProfile->real_money -= config('wormix.game.missions.buy.real_money');
//        }
//        else
//        {
//            $userProfile->money -= config('wormix.game.missions.buy.money');
//        }
//
//        $userProfile->save();

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
            ->where('id', $request->json('MissionId'))
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
                'data' => new BuyRaceResult($profile, BuyRaceResult::NOT_FOR_SALE, $char->race)
            ];
        }

        // Race already bought
        if (in_array($race->race_id, $profile->races))
        {
            return [
                'data' => new BuyRaceResult($profile, BuyRaceResult::ERROR, $char->race)
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
                        'data' => new BuyRaceResult($profile,
                            BuyRaceResult::MIN_REQUIREMENTS_ERROR, $char->race)
                    ];
                }

                if ($race->price > $profile->money)
                {
                    return [
                        'data' => new BuyRaceResult($profile,
                            BuyRaceResult::NOT_ENOUGH_MONEY, $char->race)
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
                        'data' => new BuyRaceResult($profile,
                            BuyRaceResult::NOT_ENOUGH_MONEY, $char->race)
                    ];
                }

                $profile->real_money -= $race->real_price;
                $profile->save();
                break;
            }
            default:
            {
                return [
                    'data' => new BuyRaceResult($profile, BuyRaceResult::ERROR, $char->race)
                ];
            }
        }

        // Add bought race and set it
        $races = $profile->races;
        $races[] = $race->race_id;
        $profile->races = $races;
        $profile->save();

        $char->race = $race->race_id;
        $char->save();

        return [
            'data' => new BuyRaceResult($profile, BuyRaceResult::SUCCESS, $char->race)
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
                'data' => new BuySkinResult($profile, BuySkinResult::ERROR, 0)
            ];
        }

        // Prevent buying skin for non-bought race
        if (!in_array($raceId, $profile->races))
        {
            return [
                'data' => new BuySkinResult($profile, BuySkinResult::ERROR, 0)
            ];
        }

        // Prevent from buying skin again
        if (in_array($skinId, $profile->skins))
        {
            return [
                'data' => new BuySkinResult($profile, BuySkinResult::ERROR, 0)
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
                        'data' => new BuySkinResult($profile, BuySkinResult::NOT_ENOUGH_MONEY, 0)
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
                    'data' => new BuySkinResult($profile, BuySkinResult::ERROR, 0)
                ];
            }
        }

        // Add the skin
        $skins = $profile->skins;
        $skins[] = $skinId;
        $profile->skins = $skins;
        $profile->save();

        // Check if the skin's race is currently selected
        // If it is - set the skin
        if ($char->race == $raceId)
        {
            $char->skin = $skinId;
        }
        $char->save();

        return [
            'data' => new BuySkinResult($profile, BuySkinResult::SUCCESS, $skinId)
        ];
    }
}
