<?php

namespace App\Models;

use App\Models\Wormix\HouseAction;
use App\Models\Wormix\LoginSequence;
use App\Models\Wormix\UserArena;
use App\Models\Wormix\UserBackpack;
use App\Models\Wormix\UserCookies;
use App\Models\Wormix\UserProfile;
use App\Models\Wormix\CharData;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int id
 * @property string login
 * @property string password
 *
 * @property UserProfile user_profile
 * @property CharData char_data
 * @property UserSocialData social_data
 * @property UserArena arena
 * @property UserCookies cookies
 * @property UserBackpack backpack
 * @property HouseAction house_actions
 * @property LoginSequence login_sequence
 */
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $fillable = [
        'login',
        'password'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function user_profile() : HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function char_data() : HasOne
    {
        // todo check is_main here
        return $this->hasOne(CharData::class, 'owner_id', 'id');
    }

    public function social_data() : HasOne
    {
        return $this->hasOne(UserSocialData::class, 'user_id', 'id');
    }

    public function login_sequence() : HasOne
    {
        return $this->hasOne(LoginSequence::class, 'user_id', 'id');
    }

    public function house_actions() : HasMany
    {
        return $this->hasMany(HouseAction::class, 'to_user_id', 'id');
    }

    public function cookies() : HasOne
    {
        return $this->hasOne(UserCookies::class, 'user_id', 'id');
    }

    public function backpack() : HasOne
    {
        return $this->hasOne(UserBackpack::class, 'owner_id', 'id');
    }

    public function arena() : HasOne
    {
        return $this->hasOne(UserArena::class, 'user_id', 'id');
    }
}
