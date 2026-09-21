<?php

namespace App\Observers\Wormix;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\User;
use App\Models\Wormix\Level;
use App\Models\Wormix\CharData;

class CharDataObserver
{
    private function checkLevelUp(CharData $char) : void
    {
        $level = $char->level_model;
        if ($char->experience < $level->required_experience)
        {
            return;
        }

        // Just clamp experience when max level is reached
        if ($char->level >= config('wormix.game.max_level'))
        {
            $char->experience = min($char->experience, $level->required_experience);
            return;
        }

        // Level up
        $char->level += 1;
        $char->experience = $char->experience - $level->required_experience;

        // Award the user (->level_model is cached here, so query manually)
        $newLevel = Level::query()
            ->where('id', $char->level)
            ->firstOrFail();
        $newLevel->award($char->owner);
    }

    /**
     * Handle the CharData "created" event.
     */
    public function created(CharData $charData) : void
    {

    }

    public function saving(CharData $charData) : void
    {
        // todo: change to type
        if ($charData->is_main &&
            $charData->isDirty('experience'))
        {
            $this->checkLevelUp($charData);
        }
    }

    /**
     * Handle the CharData "updated" event.
     */
    public function updated(CharData $charData) : void
    {

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
