<?php

namespace App\Observers\Wormix;

use App\Models\Wormix\UserArena;

class UserArenaObserver
{
    public function saving(UserArena $arena) : void
    {
        if($arena->battle_tokens >= config('wormix.game.missions.max'))
        {
            $arena->first_used_token_time = 0;
        }
        else
        {
            // If tokens < max then set the time if it wasnt already
            if ($arena->first_used_token_time === 0)
            {
                $arena->first_used_token_time = time();
            }
        }
    }
}
