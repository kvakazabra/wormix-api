<?php

namespace App\Models\Wormix;

use App\Exceptions\Wormix\NotEnoughMoneyException;
use App\Exceptions\Wormix\RequirementsNotMetException;
use App\Models\User;
use App\Observers\Wormix\CharDataObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int id
 * @property int owner_id
 * @property int profile_id
 * @property int type
 *
 * @property string name
 * @property int armor
 * @property int attack
 *
 * @property int level
 * @property int experience
 *
 * @property int hat
 * @property int artifact
 * @property int race
 * @property int skin
 *
 * @property User owner
 * @property Level level_model
 */
#[ObservedBy(CharDataObserver::class)]
class CharData extends Model
{
    public const TEAM_MEMBER_SELF = 0;

    public const TEAM_MEMBER_FRIEND = 1;

    public const TEAM_MEMBER_CLANMATE = 2;

    public const TEAM_MEMBER_MERCENARY = 4;

    /*
- 3	OTHER_CLAN_MATE	Clan-mate, but disabled for teaming (gift-team slot / not fully active)
- 5	HILEVEL_CLAN_MATE	High-level clan-mate, also disabled
     */

    protected $table = 'wormix_characters_data';

    protected $fillable = [
        'level',
        'armor',
        'attack'
    ];

    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    public function level_model() : HasOne
    {
        return $this->hasOne(Level::class, 'id', 'level');
    }

    /**
     * Hires as a mercenary to specified user and deducting price from the profile
     * @param User $user
     * @param bool $active
     * @param bool $useRealMoney
     * @return UserTeam Returns newly created UserTeam entry
     * @throws NotEnoughMoneyException
     */
    public function assignTo(User $user, bool $active, bool $useRealMoney) : UserTeam
    {
        $profile = $user->profile;

        $price = config('wormix.game.buy.teammate.base_price') +
            $this->level * config('wormix.game.buy.teammate.price_per_level');
        $realPrice = (int)ceil($price / config('wormix.game.buy.real_rate'));

        if ($useRealMoney)
        {
            if($realPrice > $profile->real_money)
            {
                throw new NotEnoughMoneyException('Not enough real money');
            }

            $profile->real_money -= $realPrice;
        }
        else
        {
            if ($price > $profile->money)
            {
                throw new NotEnoughMoneyException('Not enough money');
            }

            $profile->money -= $price;
        }

        $profile->save();

        // todo: code repeats itself

        $newChar = new CharData();
        $newChar->owner_id = $user->id;
        $newChar->profile_id = $this->profile_id;
        $newChar->type = CharData::TEAM_MEMBER_FRIEND;
        $newChar->name = $this->name;
        $newChar->level = $this->level;
        $newChar->armor = $this->armor;
        $newChar->attack = $this->attack;
        if ($this->race == Race::query()
                ->where('race_name', 'alien')
                ->firstOrFail()
                ->race_id)
        {
            $newChar->race = config('wormix.security.allow_mercenary_alien')
                ? $this->race : config('wormix.game.race.alien_substitution');
        }
        else
        {
            $newChar->race = $this->race;
        }
        $newChar->skin = $this->skin;
        $newChar->save();

        $team = new UserTeam();
        $team->user_id = $user->id;
        $team->teammate_id = $newChar->id;
        $team->order = count($profile->teammates);
        $team->active = $active;
        $team->save();

        return $team;
    }
}
