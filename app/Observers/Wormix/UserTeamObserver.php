<?php

namespace App\Observers\Wormix;

use App\Models\User;
use App\Models\Wormix\UserTeam;

class UserTeamObserver
{
    public function deleted(UserTeam $teammate) : void
    {
        $user = User::query()
            ->where('id', $teammate->user_id)
            ->first();

        // Reorder here to fill gaps in order indexes
        // Kinda useless here right now because the order is used via orderBy Eloquent method
        // The only thing that really matters is the order in the array, not the index that was set in DB
        $user?->profile->reorderTeammates();
    }
}
