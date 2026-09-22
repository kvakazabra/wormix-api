<?php

namespace App\Http\Controllers\Internal;

use App\Exceptions\Wormix\AlreadyBoughtException;
use App\Exceptions\Wormix\NotEnoughMoneyException;
use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Craft\DowngradeWeaponRequest;
use App\Http\Requests\Internal\Craft\UpgradeWeaponRequest;
use App\Http\Resources\Internal\Craft\DowngradeWeaponResult;
use App\Http\Resources\Internal\Craft\UpgradeWeaponResult;
use App\Models\User;
use App\Models\Wormix\Upgrade;
use App\Models\Wormix\UserItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArmoryController extends Controller
{
    public function upgrade(UpgradeWeaponRequest $request)
    {
        $recipeId = $request->json('RecipeId');

        try
        {
            DB::beginTransaction();

            $upgrade = Upgrade::query()
                ->where('id', $recipeId)
                ->firstOrFail();

            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();

            if (!$this->isUpgradeAvailable($user, $upgrade))
            {
                DB::rollBack();
                return [
                    'data' => new UpgradeWeaponResult(Collection::empty(),
                        UpgradeWeaponResult::Error, $recipeId)
                ];
            }

            $profile = $user->user_profile;
            $profile->consumeReagents($upgrade->reagents, true);

            $profile->recipes = array_merge($profile->recipes, [$recipeId]);
            $profile->save();

            DB::commit();

            return [
                'data' => new UpgradeWeaponResult(Collection::empty(),
                    UpgradeWeaponResult::Success, $recipeId)
            ];
        }
        catch(NotEnoughMoneyException)
        {
            DB::rollBack();
            return [
                'data' => new UpgradeWeaponResult(Collection::empty(),
                    UpgradeWeaponResult::NotEnoughMoney, $recipeId)
            ];
        }
        catch(\Exception $e)
        {
            Log::error($e->getMessage());
            DB::rollBack();
            return [
                'data' => new UpgradeWeaponResult(Collection::empty(),
                    UpgradeWeaponResult::Error, $recipeId)
            ];
        }
    }

    public function downgrade(DowngradeWeaponRequest $request)
    {
        $recipeId = $request->json('RecipeId');

        try
        {
            $upgrade = Upgrade::query()
                ->where('id', $recipeId)
                ->first();

            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();

            $profile = $user->user_profile;
            $recipes = $profile->recipes;

            // Check that it has been crafted
            if (!in_array($recipeId, $recipes))
            {
                throw new AlreadyBoughtException('Already crafted');
            }

            $totalReal = config('wormix.game.buy.downgrade.real_money');
            if ($totalReal > $profile->real_money)
            {
                throw new NotEnoughMoneyException();
            }

            // Downgrade
            unset($recipes[array_search($recipeId, $recipes)]);
            $recipes = array_values($recipes);

            // Calculate reagents to return back to the user
            // This just changes the $count of $upgrade->reagents basically
            $returnRate = config('wormix.game.buy.downgrade.return_rate');
            $upgradeReagents = array_map(
                fn ($count) => floor($count * $returnRate),
                $upgrade->reagents
            );

            $profile->recipes = $recipes;
            $profile->real_money -= $totalReal;
            $profile->grantReagents($upgradeReagents, false);
            $profile->save();

            return new DowngradeWeaponResult(Collection::empty(),
                DowngradeWeaponResult::Success, $recipeId);
        }
        catch(NotEnoughMoneyException)
        {
            DB::rollBack();
            return [
                'data' => new DowngradeWeaponResult(Collection::empty(),
                    DowngradeWeaponResult::NotEnoughMoney, $recipeId)
            ];
        }
        catch(\Exception $e)
        {
            Log::error($e->getMessage());
            DB::rollBack();
            return [
                'data' => new DowngradeWeaponResult(Collection::empty(),
                    DowngradeWeaponResult::Error, $recipeId)
            ];
        }
    }

    private function isBaseWeaponFullyBought(User $user, Upgrade $upgrade) : bool
    {
        $profile = $user->user_profile;

        $baseWeapon = $upgrade->baseWeapon();
        if ($baseWeapon === null)
        {
            return false;
        }

        /** @var UserItem $item */
        $item = $profile->weapons()
            ->where('item_id', $baseWeapon->id)
            ->first();
        if ($item === null || $item->count >= 0)
        {
            return false;
        }

        if (!$baseWeapon->is_complex && !$baseWeapon->infinite)
        {
            Log::error("Upgrade: Finite weapons can not be upgraded!");
            return false;
        }

        // Check if the weapon has been bought out fully
        if ($baseWeapon->is_complex)
        {
            if ($item->count !== $baseWeapon->maxLevel())
            {
                return false;
            }

            return true;
        }

        // Infinite baseWeapon here, check the count
        if ($item->count !== -1)
        {
            return false;
        }

        return true;
    }

    private function isUpgradeAvailable(User $user, Upgrade $upgrade) : bool
    {
        $recipes = $user->user_profile->recipes;
        $char = $user->char_data;

        // Check the level
        if ($upgrade->required_level > $char->level)
        {
            return false;
        }

        // Already upgraded it
        if (in_array($upgrade->id, $recipes))
        {
            return false;
        }

        if (!$this->isBaseWeaponFullyBought($user, $upgrade))
        {
            return false;
        }

        $prevUpgradeId = $upgrade->prev_upgrade_id;
        // No need for further checks if isBaseWeaponFullyBought returned true here
        if (WormixTrashHelper::isWeaponType($prevUpgradeId))
        {
            return true;
        }

        // todo: also should check any competing upgrades (observer)

        // Check if previous upgrade has already been made
        return in_array(Upgrade::upgradeIdToRecipeId($prevUpgradeId), $recipes);
    }
}
