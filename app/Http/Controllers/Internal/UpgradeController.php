<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Craft\DowngradeWeaponRequest;
use App\Http\Requests\Internal\Craft\UpgradeWeaponRequest;
use App\Http\Resources\Internal\Craft\DowngradeWeaponResult;
use App\Http\Resources\Internal\Craft\UpgradeWeaponResult;
use App\Models\Wormix\Craft;
use App\Models\Wormix\Reagent;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\WormData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UpgradeController extends Controller
{
    public const UPGRADE_BASE = 300;

    private function isUpgradeAvailable(
        Craft $craft,
        UserProfile $userProfile,
        WormData $wormData,
        int $recipeId) : bool
    {
        // Requires owning the base weapon
        if ($craft->prev_upgrade_id < self::UPGRADE_BASE &&
            UserItem::query()
                ->where('owner_id', $wormData->owner_id)
                ->where('item_id', $craft->prev_upgrade_id)
                ->where('count', -1)
                ->count() === 0)
        {
            return false;
        }

        // Requires the previous item to be upgraded
        if ($craft->prev_upgrade_id > self::UPGRADE_BASE &&
            !in_array($craft->prev_upgrade?->id, $userProfile->recipes))
        {
            return false;
        }

        // Already upgraded
        if (in_array($craft->id, $userProfile->recipes))
        {
            return false;
        }

        if ($wormData->level < $craft->required_level)
        {
            return false;
        }

        if ($craft->prev_upgrade === null)
        {
            return true;
        }

        // A competing recipe for the same upgrade is already upgraded
        $competingCraft = Craft::query()
            ->where('upgrade_id', $craft->prev_upgrade->id)
            ->where('id', '!=', $recipeId)
            ->first();
        if ($competingCraft !== null &&
            in_array($competingCraft->id, $userProfile->recipes))
        {
            return false;
        }

        return true;
    }

    public function upgradeWeapon(UpgradeWeaponRequest $request)
    {
        $craft = Craft::query()
            ->where('id', $request->json('RecipeId'))
            ->first();

        $userProfile = UserProfile::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->first();

        $wormData = WormData::query()
            ->where('owner_id', $request->json('internal_user_id'))
            ->first();

        if (!$this->isUpgradeAvailable(
            $craft,
            $userProfile,
            $wormData,
            $request->json('RecipeId')))
        {
            return [
                'data' => new UpgradeWeaponResult(
                    Collection::empty(),
                    UpgradeWeaponResult::Error,
                    $request->json('RecipeId')
                )
            ];
        }

        $reagentsToCraft = Reagent::query()
            ->select('reagent_id', 'reagent_price')
            ->whereIn('reagent_id', array_map(function ($x) {return $x[0];}, $craft->reagents))
            ->get()
            ->pluck('reagent_price', 'reagent_id')
            ->toArray();

        $maxReagentId = max(array_map(function ($x) {return $x[0];}, $craft->reagents));

        $changedReagents = $userProfile->reagents;
        if (count($changedReagents) < $maxReagentId + 1)
        {
            $oldReagents = $changedReagents;
            $changedReagents = array_fill(0, $maxReagentId + 1, 0);
            foreach ($oldReagents as $k => $v)
            {
                $changedReagents[$k] = $v;
            }
        }

//        Log::debug("PREPARED REAGENT DATA",
//            [
//                'reagents' => $reagentsToCraft,
//                'craft_reagents' => $craft->reagents,
//                'max_id' => $maxReagentId,
//                'user_reagents' => $changedReagents
//            ]
//        );

        $sum = 0;

        foreach ($craft->reagents as $reagent)
        {
            $sum += max(0, ($reagent[1] - $changedReagents[$reagent[0]]) * $reagentsToCraft[(string)$reagent[0]]);
            $changedReagents[$reagent[0]] = max(0, $changedReagents[$reagent[0]] - $reagent[1]);
        }

        $sum = (int)(round($sum / 100));

//        Log::debug("NeedSum", [
//            'sum' => $sum,
//        ]);

        if ($userProfile->real_money < $sum)
        {
            return [
                'data' => new UpgradeWeaponResult(Collection::empty(), UpgradeWeaponResult::NotEnoughMoney, $request->json('RecipeId'))
            ];
        }

        $userProfile->real_money -= $sum;
        $userProfile->recipes = array_merge($userProfile->recipes, [$request->json('RecipeId')]);
        $userProfile->reagents = $changedReagents;
        $userProfile->save();

        return [
            'data' => new UpgradeWeaponResult(Collection::empty(), UpgradeWeaponResult::Success, $request->json('RecipeId'))
        ];
    }

    public function downgradeWeapon(DowngradeWeaponRequest $request)
    {
        $craft = Craft::query()
            ->where('id', $request->json('RecipeId'))
            ->first();

        $userProfile = UserProfile::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->first();

        $recipes = $userProfile->recipes;
        $reagents = $userProfile->reagents;

        //Add cross craft checks
        if (!in_array($craft->id, $recipes) ||
            $userProfile->real_money < config('wormix.game.buy.downgrade'))
        {
            return [
                'data' => new DowngradeWeaponResult(Collection::empty(), DowngradeWeaponResult::Error, $request->json('RecipeId'))
            ];
        }

        foreach ($craft->reagents as $reagent)
        {
            $reagents[$reagent[0]] += (int)($reagent[1] * 0.8);
        }

        unset($recipes[array_search($request->json('RecipeId'), $recipes)]);
        $recipes = array_values($recipes);

        $userProfile->recipes = $recipes;
        $userProfile->reagents = $reagents;
        $userProfile->real_money -= config('wormix.game.buy.downgrade');
        $userProfile->save();

        return [
            'data' => new DowngradeWeaponResult(Collection::empty(), DowngradeWeaponResult::Success, $request->json('RecipeId'))
        ];
    }
}