<?php

namespace App\Helpers\Wormix;

use App\Models\Wormix\Equipment;
use App\Models\Wormix\HouseAction;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\Weapon;
use App\Models\Wormix\CharData;

class WormixTrashHelper
{
    public static function generateBattleReagents() : array
    {
        // todo: generate random ones
        $array = array_fill(0, 4, -1);
        $array[0] = 50;
        $array[1] = 51;
        $array[2] = 0;
        $array[3] = 6;

        return $array;
    }

    /**
     * @param array $reagents Server-side generated reagents
     * @param array $collected Client-side collected reagents
     * @return bool Returns true if all of the client-side reagents are present in $reagents
     */
    public static function validateBattleReagents(array $reagents, array $collected) : bool
    {
        $reagentsMap = array_count_values($reagents);
        // Converts [5, 5, 1] to [5 => 2, 1 => 1]

        foreach ($collected as $id)
        {
            // Id is not present in generated reagents
            if (!isset($reagentsMap[$id]))
            {
                return false;
            }

            // All of the reagents of this type has been already collected
            if ($reagentsMap[$id] <= 0)
            {
                return false;
            }

            // Decrease the count
            $reagentsMap[$id]--;
        }

        return true;
    }

    public static function isWeaponType(int $id) : bool
    {
        return ($id >= config('wormix.ids.weapons.min') && $id <= config('wormix.ids.weapons.max'))
            || $id >= config('wormix.ids.weapons.droppable_min');
    }

    public static function isUpgradeId(int $id) : bool
    {
        return $id >= config('wormix.ids.upgrades.min') && $id <= config('wormix.ids.upgrades.max');
    }

    public static function isStuffType(int $id) : bool
    {
        return $id >= config('wormix.ids.stuff.min') && $id <= config('wormix.ids.stuff.max');
    }

    public static function isHatType(int $id) : bool
    {
        return $id >= config('wormix.ids.hats.min') && $id <= config('wormix.ids.hats.max');
    }

    public static function isArtifactType(int $id) : bool
    {
        return $id >= config('wormix.ids.artifacts.min') && $id <= config('wormix.ids.artifacts.max');
    }

    public static function isTutorialMissionId(int $id) : bool
    {
        return $id < 0;
    }

    public static function isSoloMissionId(int $id) : bool
    {
        return $id >= config('wormix.ids.solo_missions.min') && $id <= config('wormix.ids.solo_missions.max');
    }

    public static function isCoopMissionId(int $id) : bool
    {
        return $id >= config('wormix.ids.coop_missions.min') && $id <= config('wormix.ids.coop_missions.max');
    }

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

    public static function toIndexedReagentsArray(array $map) : array
    {
        if (empty($map))
        {
            return [];
        }

        $size = max(array_keys($map)) + 1;
        $result = array_fill(0, $size, 0);

        foreach ($map as $id => $count)
        {
            $result[$id] = $count;
        }

        return $result;
    }

    /**
     * @param array $array Array in pairs like [[id, count], ...]
     * @return array Associative array like [[id => count], ...]
     */
    public static function pairsToAssociativeArray(array $array) : array
    {
        return array_filter(array_column($array, 1, 0),
            fn ($count) => $count !== 0
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

            if (self::isStuffType($award[0]))
            {
                // Add a day, instead of overwriting expire_at value
                $userItem->expire_at = max($userItem->expire_at, time()) + 24 * 60 * 60;
                // todo: worth checking duration of the item, instead of giving a day each time
            }

            $userItem->save();
        }
    }
}
