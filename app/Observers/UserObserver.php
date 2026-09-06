<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserSocialData;
use App\Models\Wormix\LoginSequence;
use App\Models\Wormix\UserBattleInfo;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\UserTeam;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\Weapon;
use App\Models\Wormix\WormData;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user) : void
    {
        //Create user profile
        $userProfile = new UserProfile();
        $userProfile->user_id = $user->id;
        $userProfile->money = config('wormix.starter.money');
        $userProfile->real_money = config('wormix.starter.real_money');
        $userProfile->save();

        //Create user worm data
        $wormData = new WormData();
        $wormData->owner_id = $user->id;
        $wormData->race = config('wormix.starter.race');
        $wormData->save();

        //Create user default teammate
        $teammate = new UserTeam();
        $teammate->user_id = $user->id;
        $teammate->teammate_id = $user->id;
        $teammate->save();

        //Create user social data
        $socialData = new UserSocialData();
        $socialData->user_id = $user->id;
        $socialData->first_name = $user->login;
        $socialData->save();

        //Create user battle info
        $battleInfo = new UserBattleInfo();
        $battleInfo->user_id = $user->id;
        $battleInfo->battles_count = config('wormix.starter.missions');
        $battleInfo->save();

        //Create user login sequence info
        $loginSequence = new LoginSequence();
        $loginSequence->user_id = $user->id;
        $loginSequence->last_login = date("Y-m-d");
        $loginSequence->gift_accepted = 1;
        $loginSequence->save();

        //Add starter user weapons
        $starterWeapons = Weapon::query()->where('is_starter', 1)->get();
        foreach ($starterWeapons as $w)
        {
            $weapon = new UserItem();
            $weapon->owner_id = $user->id;
            $weapon->item_id = $w->id;
            $weapon->item_type = UserItem::itemTypeForId($w->id);
            $weapon->save();
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user) : void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user) : void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user) : void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user) : void
    {
        //
    }
}