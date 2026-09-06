<?php

namespace App\Listeners;

use App\Events\InternalLoginEvent;
use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\Wormix\LoginSequence;
use App\Models\Wormix\UserBattleInfo;
use App\Models\Wormix\UserItem;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LoginEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event) : void
    {
        if ($event instanceof InternalLoginEvent)
        {
            $this->handleInternalLoginEvent($event);
        }
    }

    private function handleInternalLoginEvent(InternalLoginEvent $event) : void
    {
        $user = $event->getUser();

        // Update login sequence
        $isNewAccount = $user->login_sequence->last_login == null;
        $oldLoginDate = $isNewAccount ?
            now() :
            Carbon::createFromTimeString($user->login_sequence->last_login);
        $loginSequence = $user->login_sequence;

        $days = max(0, (int)$oldLoginDate->diff(now())->totalDays);

        if ($days === 1)
        {
            $loginSequence->login_sequence += 1;
        }
        elseif ($days > 1)
        {
            $loginSequence->login_sequence = 1;
        }

        if ($loginSequence->login_sequence > 5)
        {
            $loginSequence->login_sequence = 1;
        }

        $user->login_sequence->last_login = date("Y-m-d");
        $loginSequence->gift_accepted = false;

        //Not add gifts if already taken
        if ($days === 0 && !$isNewAccount)
        {
            LoginSequence::withoutEvents(function () use ($loginSequence)
            {
                $loginSequence->gift_accepted = true;
                $loginSequence->save();
            });
        }
        else
        {
            $loginSequence->save();
        }

        // Check current hat & reset if it has expired
        $currentHat = UserItem::query()
            ->where('owner_id', $user->id)
            ->where('item_id', $user->worm_data->hat)
            ->first();
        if ($currentHat != null &&
            $currentHat->expire_at < time() &&
            $currentHat->expire_at !== -1)
        {
            $user->worm_data->hat = 0;
            $user->worm_data->save();
        }

        $battleInfo = UserBattleInfo::query()
            ->where('user_id', $user->id)
            ->first();

        //Clear mission id before boss fights
        if ($user->worm_data->level > 5 && $battleInfo->last_mission_id < 0)
        {
            $battleInfo->last_mission_id = 0;
            $battleInfo->save();
        }

        //Destroy expired items
        UserItem::destroy(
            UserItem::query()
                ->select('id')
                ->where('expire_at', '!=', -1)
                ->where('expire_at', '<', time())
                ->get()
        );
    }
}