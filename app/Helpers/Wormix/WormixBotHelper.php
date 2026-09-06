<?php

namespace App\Helpers\Wormix;

use App\Models\User;
use App\Models\Wormix\Equipment;
use App\Models\Wormix\Level;
use App\Models\Wormix\Race;
use App\Models\Wormix\WormData;
use Illuminate\Database\Eloquent\Collection;

class WormixBotHelper
{
    public const BOT_BASE = -1000;

    public static function GenerateBots(WormData $userWorm)
    {
        $botBaseId = User::query()->select('id')->count();

        $userProfile = $userWorm->owner->user_profile;

        // todo: change magic value to hat crafts id
        $randomStuff = Equipment::query()
            ->where('id', '<', 1500)
            ->where('required_level', '<=', $userWorm->level)
            ->where('required_rating', '<=', $userProfile->rating)
            ->where('hide_in_shop', 0)
            ->where('price', '!=', 0)
            ->pluck('id')
            ->toArray();

        $randomRace = Race::query()
            ->where('required_level', '<=', $userWorm->level)
            ->pluck('race_id')
            ->toArray();

        $bots = Collection::empty();

        $botsCount = (
            $userWorm->level >= 5 ||
            $userWorm->owner->battle_info->mission_id < -2 ||
            $userWorm->owner->battle_info->mission_id >= 0
        ) ? 6 : 4;

        for ($i = 0; $i < $botsCount; $i++)
        {
            $wormGroup = [];
            $randomLevel = rand(
                max(1, $userWorm->level - 1),
                min($userWorm->level + 1, 30)
            );

            $level = Level::query()->where('id', $randomLevel)->first();
            $randomWormsCount = rand(1, $level->max_worms_count);

            for ($j = 0; $j < $randomWormsCount; $j++)
            {
                $randomArmor = rand(0, $randomLevel * 2);
                $randomAttack = $randomLevel * 2 - $randomArmor;
                $hat = count($randomStuff) === 0 ? 0 : $randomStuff[array_rand($randomStuff)];
                if (rand(0, 10) % 5 === 0)
                {
                    $hat = 0;
                }

                $race = count($randomRace) === 0 ? 0 : $randomRace[array_rand($randomRace)];

                $wormGroup[] = [
                    'Armor' => $randomArmor,
                    'Attack' => $randomAttack,
                    'Experience' => 0,
                    'Level' => $randomLevel,
                    'Hat' => WormixTrashHelper::mergeHatRaceIds($hat, $race),
                    'OwnerId' => $botBaseId + ($i+$j+1) * 10,
                    'SocialOwnerId' => (string)(0 - $botBaseId + self::BOT_BASE - ($i+1+$j)*10)
                ];
            }

            $profile = [
                'Id' => $botBaseId + ($i+1) * 10,
                'Rating' => rand(0, $userProfile->rating),
                'Money' => rand(0, $userProfile->money),
                'RealMoney' => rand(0, $userProfile->real_money),
                'Recipes' => [],
                'SocialId' => (string)(0 - $botBaseId + self::BOT_BASE - ($i+1)*10),
                'Stuff' => $hat == 0 ? [] : [$hat],
                'WeaponRecordList' => [
                    ['Id' => 1, 'Count' => -1]
                ],
                'WormsGroup' => $wormGroup
            ];
            $bots->add($profile);
        }
        return $bots;
    }

    public static function stripData(
        int $level,
        int $toLevel,
        int $armor,
        int $attack
    )
    {
        $levelPoints = $level * 2;
        $toLevelPoints = $toLevel * 2;

        if ($armor + $attack < $levelPoints)
        {
            $armor += $levelPoints - ($armor + $attack);
        }

        $armorPoints = (int)(($armor / $levelPoints) * $toLevelPoints);
        $attackPoints = $toLevelPoints - $armorPoints;

        return [
            'armor' => $armorPoints,
            'attack' => $attackPoints
        ];
    }
}