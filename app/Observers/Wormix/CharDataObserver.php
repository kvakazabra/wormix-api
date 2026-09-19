<?php

namespace App\Observers\Wormix;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\Wormix\Level;
use App\Models\Wormix\CharData;

class CharDataObserver
{
    /**
     * Handle the CharData "created" event.
     */
    public function created(CharData $charData) : void
    {

    }

    /**
     * Handle the CharData "updated" event.
     */
    public function updated(CharData $charData) : void
    {
        if ($charData->experience < $charData->level_model->required_experience)
        {
            return;
        }

        //Max level
        if ($charData->level === 30)
        {
            $charData->experience = $charData->level_model->required_experience;
            CharData::withoutEvents(function () use ($charData)
            {
                $charData->save();
            });
            return;
        }

        //Save new level
        CharData::withoutEvents(function () use ($charData)
        {
            $charData->level += 1;
            $charData->experience = $charData->experience - $charData->level_model->required_experience;
            $charData->save();
        });

        $levelModel = Level::query()->where('id', $charData->level)->first();
        WormixTrashHelper::addWeaponsAwards($levelModel->awards, $charData);
        //Add money
        $userProfile = $charData->owner->user_profile;
        $userProfile->money += config('wormix.game.next_level_award.money');
        $userProfile->save();
    }

    /**
     * Handle the CharData "deleted" event.
     */
    public function deleted(CharData $charData) : void
    {
        //
    }

    /**
     * Handle the CharData "restored" event.
     */
    public function restored(CharData $charData) : void
    {
        //
    }

    /**
     * Handle the CharData "force deleted" event.
     */
    public function forceDeleted(CharData $charData) : void
    {
        //
    }
}
