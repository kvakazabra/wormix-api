<?php

namespace App\Helpers\Wormix;

use App\Models\Wormix\Equipment;
use App\Models\Wormix\HouseAction;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\Weapon;
use App\Models\Wormix\WormData;
use Illuminate\Support\Facades\Log;

class WormixTrashHelper
{
    private const RACE_BASE = 500;
    private const RACE_LIMIT = 50;

    public const STUFF_START_INDEX = 1000;


    /**
     * @param int $hatId
     * @param int $raceId
     * @return int worm_structure.hat
     */
    public static function mergeHatRaceIds(int $hatId, int $raceId) : int
    {
        return $hatId === 0 ?
            $raceId :
            $raceId * self::RACE_BASE + $hatId;
    }

    /**
     * @param int $merged worm_structure.hat
     * @return int[] 0-race id, 1-hat id
     */
    public static function extractHatAndRaceIds(int $merged) : array
    {
        $hatId = 0;
        if ($merged < self::RACE_LIMIT)
        {
            $raceId = $merged;
        }
        else
        {
            $hatId = self::STUFF_START_INDEX + $merged % self::RACE_BASE;
            $raceId = (int)(($merged - self::STUFF_START_INDEX) / self::RACE_BASE);
        }

        return [ $raceId, $hatId ];
    }

    /**
     * @param int $user_id
     * @return int
     */
    public static function getSearchKeys(int $user_id) : int
    {
        return max(
            0,
            (config('wormix.game.search_keys_per_day') -
                HouseAction::query()
                    ->where('action_type', 1)
                    ->where('user_id', $user_id)
                    ->where('created_at', '>=', now()->subDay())
                    ->count()
            )
        );
    }

    /**
     * @param int $user_id
     * @param int $to_user_id
     * @return bool
     */
    public static function isSearchedToday(int $user_id, int $to_user_id):bool
    {
        return HouseAction::query()
                ->where('user_id', $user_id)
                ->where('to_user_id', $to_user_id)
                ->where('action_type', 1)
                ->where('created_at', '>=', now()->subDay())->count() > 0;
    }

    public static function addReagents(UserProfile $profile, array $reagents):void
    {
        if(count($reagents) === 0)
            return;
        //Log::debug("Add reagents...");
        $userReagents = $profile->reagents;
        $maxReagent = max($reagents);
        if($maxReagent + 1 > count($userReagents)){
           $old_reagents  = $userReagents;
            $userReagents = array_fill(0, $maxReagent + 1, 0);
           for($i = 0; $i < count($old_reagents); $i++){
               $userReagents[$i] = $old_reagents[$i];
           }
        }

        for($i = 0; $i < count($reagents); $i++){
            $userReagents[$reagents[$i]] += 1;
        }

        $profile->reagents = $userReagents;
        $profile->save();
    }

    public static function addWeaponsAwards(array $awards, WormData $wormData): void
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
            $userItem->item_type = UserItem::itemTypeForId($userItem->item_id);
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
