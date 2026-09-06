<?php

namespace App\Observers\Wormix;

use App\Models\Wormix\DailyBonus;
use App\Models\Wormix\LoginSequence;
use App\Models\Wormix\UserBattleInfo;
use App\Models\Wormix\UserProfile;
use Illuminate\Support\Facades\Log;

class LoginSequenceObserver
{
    /**
     * Handle the LoginSequence "created" event.
     */
    public function created(LoginSequence $loginSequence) : void
    {
        //
    }

    /**
     * Handle the LoginSequence "updated" event.
     */
    public function updated(LoginSequence $loginSequence) : void
    {
        $userProfile = UserProfile::query()->where('user_id', $loginSequence->user_id)->first();
        $bonus = DailyBonus::query()->where('login_sequence', $loginSequence->login_sequence)->first();
        $battleInfo = UserBattleInfo::query()->where('user_id', $loginSequence->user_id)->first();
        if ($userProfile === null || $bonus === null || $battleInfo == null)
        {
            return;
        }

        srand(time());

        LoginSequence::withoutEvents(function () use ($loginSequence, $userProfile, $bonus, $battleInfo)
        {
            $amount = $bonus->random_gift ? rand($bonus->rand_min, $bonus->rand_max) : $bonus->bonus_value;
            $type = $bonus->random_gift ? rand(1, 4) : $bonus->bonus_type;
            switch ($type)
            {
                case 1: //fuzzes
                    $userProfile->money += $amount;
                    $userProfile->save();
                    break;
                case 2: //rubies
                    $userProfile->real_money += $amount;
                    $userProfile->save();
                    break;

                case 3: //missions
                    $battleInfo->battles_count += $amount;
                    $battleInfo->save();
                    break;

                case 4: // reaction rate
                    $userProfile->reaction_rate += $amount;
                    $userProfile->save();
                    break;
            }
            $loginSequence->bonus_count = $amount;
            $loginSequence->bonus_type = $type;
            $loginSequence->save();
        });
    }

    /**
     * Handle the LoginSequence "deleted" event.
     */
    public function deleted(LoginSequence $loginSequence) : void
    {
        //
    }

    /**
     * Handle the LoginSequence "restored" event.
     */
    public function restored(LoginSequence $loginSequence) : void
    {
        //
    }

    /**
     * Handle the LoginSequence "force deleted" event.
     */
    public function forceDeleted(LoginSequence $loginSequence) : void
    {
        //
    }
}