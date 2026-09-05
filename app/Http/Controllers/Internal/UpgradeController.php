<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Craft\DowngradeWeaponRequest;
use App\Http\Requests\Internal\Craft\UpgradeWeaponRequest;
use App\Http\Resources\Internal\Craft\DowngradeWeaponResult;
use App\Http\Resources\Internal\Craft\UpgradeWeaponResult;
use App\Models\Wormix\Upgrade;
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
        Upgrade     $upgrade,
        UserProfile $user_profile,
        WormData    $wormData,
        int         $recipeId) : bool
    {
        // Requires owning the base weapon
        if ($upgrade->prev_upgrade_id < self::UPGRADE_BASE &&
            UserItem::query()
                ->where('owner_id', $wormData->owner_id)
                ->where('item_id', $upgrade->prev_upgrade_id)
                ->where('count', -1)
                ->count() === 0)
        {
            return false;
        }

        // Requires the previous item to be upgraded
        if ($upgrade->prev_upgrade_id > self::UPGRADE_BASE &&
            !in_array(@$upgrade->prev_upgrade->id, $user_profile->recipes))
        {
            return false;
        }

        // Already upgraded
        if (in_array($upgrade->id, $user_profile->recipes))
        {
            return false;
        }

        if ($wormData->level < $upgrade->required_level)
        {
            return false;
        }

        if ($upgrade->prev_upgrade === null)
        {
            return true;
        }

        // A competing recipe for the same upgrade is already upgraded
        $competing_rename = Upgrade::query()
            ->where('upgrade_id', $upgrade->prev_upgrade->id)
            ->where('id', '!=', $recipeId)
            ->first();
        if ($competing_rename !== null &&
            in_array($competing_rename->id, $user_profile->recipes))
        {
            return false;
        }

        return true;
    }

    public function upgradeWeapon(UpgradeWeaponRequest $request)
    {
        $upgrade = Upgrade::query()
            ->where('id', $request->json('RecipeId'))
            ->get()
            ->first();

        $userProfile = UserProfile::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->get()
            ->first();

        $wormData = WormData::query()
            ->where('owner_id', $request->json('internal_user_id'))
            ->get()
            ->first();

        if (!$this->isUpgradeAvailable(
            $upgrade,
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
            ->whereIn('reagent_id', array_map(function ($x) {return $x[0];}, $upgrade->reagents))
            ->get()
            ->pluck('reagent_price', 'reagent_id')
            ->toArray();

        $maxReagentId = max(array_map(function ($x) {return $x[0];}, $upgrade->reagents));

        $changedReagents =  $userProfile->reagents;
        if(count($changedReagents) < $maxReagentId + 1){
            $oldReagents = $changedReagents;
            $changedReagents = array_fill(0, $maxReagentId + 1, 0);
            foreach($oldReagents as $k => $v) {
                $changedReagents[$k] = $v;
            }
        }

//        Log::debug("PREPARED REAGENT DATA",
//            [
//                'reagents' => $reagentsToCraft,
//                'craft_reagents' => $upgrade->reagents,
//                'max_id' => $maxReagentId,
//                'user_reagents' => $changedReagents
//            ]
//        );

        $sum = 0;

        foreach($upgrade->reagents as $reagent){
            $sum += max(0, ($reagent[1] - $changedReagents[$reagent[0]]) * $reagentsToCraft[(string)$reagent[0]]);
            $changedReagents[$reagent[0]] = max(0,  $changedReagents[$reagent[0]] - $reagent[1]);
        }

        $sum = (int)(round($sum / 100));

//        Log::debug("NeedSum", [
//            'sum' => $sum,
//        ]);

        if($userProfile->real_money < $sum)
            return [
                'data' => new UpgradeWeaponResult(Collection::empty(), UpgradeWeaponResult::NotEnoughMoney, $request->json('RecipeId'))
            ];

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
        $upgrade = Upgrade::query()
            ->where('id', $request->json('RecipeId'))
            ->get()
            ->first();

        $user_profile = UserProfile::query()
            ->where('user_id', $request->json('internal_user_id'))
            ->get()
            ->first();

        $recipes = $user_profile->recipes;
        $reagents = $user_profile->reagents;

        //Add cross craft checks
        if (!in_array($upgrade->id, $recipes) ||
            $user_profile->real_money < config('wormix.game.buy.downgrade'))
        {
            return [
                'data' => new DowngradeWeaponResult(Collection::empty(), DowngradeWeaponResult::Error, $request->json('RecipeId'))
            ];
        }

        foreach ($upgrade->reagents as $reagent){
            $reagents[$reagent[0]] += (int)($reagent[1] * 0.8);
        }

        unset($recipes[array_search($request->json('RecipeId'), $recipes)]);
        $recipes = array_values($recipes);

        $user_profile->recipes = $recipes;
        $user_profile->reagents = $reagents;
        $user_profile->real_money -= config('wormix.game.buy.downgrade');
        $user_profile->save();

        return [
            'data' => new DowngradeWeaponResult(Collection::empty(), DowngradeWeaponResult::Success, $request->json('RecipeId'))
        ];
    }
}
