<?php

namespace App\Helpers\Wormix;

use App\Models\Wormix\Equipment;
use App\Models\Wormix\HouseAction;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\Weapon;
use App\Models\Wormix\CharData;
use Illuminate\Support\Facades\Log;

class WormixTrashHelper
{
    private const RACE_BASE = 500;
    private const RACE_LIMIT = 50;

    public const WEAPON_MIN_INDEX = 0;
    public const WEAPON_MAX_INDEX = 1000;
    // There are also some weapons in that range, however they can be only obtained in battle
    public const WEAPON_EXCEPT_INDEX = 10000;

    public const STUFF_MIN_INDEX = 1000;
    public const STUFF_MAX_INDEX = 3000;

    public const HATS_MIN_INDEX = 1000;
    public const HATS_MAX_INDEX = 2000;

    public const ARTIFACTS_MIN_INDEX = 2000;
    public const ARTIFACTS_MAX_INDEX = 3000;

    public static function generateRacesBitfield(array $races) : int
    {
        $bits = 0;
        foreach ($races as $race)
        {
            $bits |= 1 << $race;
        }

        return $bits;
    }

    /**
     * @param int $userId
     * @return int
     */
    public static function getSearchKeys(int $userId) : int
    {
        return max(
            0,
            (config('wormix.game.search_keys_per_day') -
                HouseAction::query()
                    ->where('action_type', 1)
                    ->where('user_id', $userId)
                    ->where('created_at', '>=', now()->subDay())
                    ->count()
            )
        );
    }

    /**
     * @param int $userId
     * @param int $toUserId
     * @return bool
     */
    public static function isSearchedToday(int $userId, int $toUserId) : bool
    {
        return HouseAction::query()
                ->where('user_id', $userId)
                ->where('to_user_id', $toUserId)
                ->where('action_type', 1)
                ->where('created_at', '>=', now()->subDay())
                ->count() > 0;
    }

    public static function addReagents(UserProfile $profile, array $reagents) : void
    {
        if (count($reagents) === 0)
        {
            return;
        }

        //Log::debug("Add reagents...");
        $userReagents = $profile->reagents;
        $maxReagent = max($reagents);
        if ($maxReagent + 1 > count($userReagents))
        {
            $oldReagents = $userReagents;
            $userReagents = array_fill(0, $maxReagent + 1, 0);
            for ($i = 0; $i < count($oldReagents); $i++)
            {
                $userReagents[$i] = $oldReagents[$i];
            }
        }

        for ($i = 0; $i < count($reagents); $i++)
        {
            $userReagents[$reagents[$i]] += 1;
        }

        $profile->reagents = $userReagents;
        $profile->save();
    }

    public static function addWeaponsAwards(array $awards, CharData $wormData) : void
    {
        if (count($awards) === 0)
        {
            return;
        }

        $awardIds = array_map(fn ($award) => $award[0], $awards);

        // Item ids the user already has a consumable copy of
        $ownedConsumables = UserItem::query()
            ->where('owner_id', $wormData->owner_id)
            ->whereIn('item_id', $awardIds)
            ->where('count', '!=', '-1')
            ->pluck('item_id')
            ->toArray();

        // Awarded ids not owned as consumables yet (weapons and equipment)
        $awardItemIds = array_merge(
            Weapon::query()
                ->whereIn('id', $awardIds)
                ->whereNotIn('id', $ownedConsumables)
                ->pluck('id')
                ->toArray(),
            Equipment::query()
                ->whereIn('id', $awardIds)
                ->whereNotIn('id', $ownedConsumables)
                ->pluck('id')
                ->toArray()
        );

        $newAwards = array_values(
            array_filter(
                $awards,
                fn ($award) => in_array($award[0], $awardItemIds)
            )
        );

        foreach ($newAwards as $award)
        {
            $oldItem = UserItem::query()
                ->where('owner_id', $wormData->owner_id)
                ->where('item_id', $award[0])
                ->first();

            $userItem = $oldItem ?? new UserItem();
            $userItem->owner_id = $wormData->owner_id;
            $userItem->item_id = $award[0];

            if ($userItem->item_type == "hat")
            {
                $wormData->hat = $userItem->item_id;
                $wormData->save();
            }

            $userItem->count = $award[1];

            if ($award[0] >= self::STUFF_START_INDEX)
            {
                // Add a day, instead of overwriting expire_at value
                $userItem->expire_at = max($userItem->expire_at, time()) + 24 * 60 * 60;
                // todo: worth checking duration of the item, instead of giving a day each time
            }

            $userItem->save();
        }
    }
}
