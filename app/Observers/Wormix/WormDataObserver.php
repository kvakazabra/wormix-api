<?php

namespace App\Observers\Wormix;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\Wormix\Level;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\Weapon;
use App\Models\Wormix\WormData;

class WormDataObserver
{
    /**
     * Handle the WormData "created" event.
     */
    public function created(WormData $wormData) : void
    {

    }

    /**
     * Handle the WormData "updated" event.
     */
    public function updated(WormData $wormData) : void
    {
        if ($wormData->experience < $wormData->level_model->required_experience)
        {
            return;
        }

        //Max level
        if ($wormData->level === 30)
        {
            $wormData->experience = $wormData->level_model->required_experience;
            WormData::withoutEvents(function () use ($wormData)
            {
                $wormData->save();
            });
            return;
        }

        //Save new level
        WormData::withoutEvents(function () use ($wormData)
        {
            $wormData->level += 1;
            $wormData->experience = $wormData->experience - $wormData->level_model->required_experience;
            $wormData->save();
        });

        $levelModel = Level::query()->where('id', $wormData->level)->first();
        WormixTrashHelper::addWeaponsAwards($levelModel->awards, $wormData);
        //Add money
        $userProfile = $wormData->owner->user_profile;
        $userProfile->money += config('wormix.game.next_level_award.money');
        $userProfile->save();
    }

    /**
     * Handle the WormData "deleted" event.
     */
    public function deleted(WormData $wormData) : void
    {
        //
    }

    /**
     * Handle the WormData "restored" event.
     */
    public function restored(WormData $wormData) : void
    {
        //
    }

    /**
     * Handle the WormData "force deleted" event.
     */
    public function forceDeleted(WormData $wormData) : void
    {
        //
    }
}