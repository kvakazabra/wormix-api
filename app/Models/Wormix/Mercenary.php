<?php

namespace App\Models\Wormix;

use App\Exceptions\Wormix\NotEnoughMoneyException;
use Exception;
use App\Exceptions\Wormix\RequirementsNotMetException;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property int level
 * @property int required_level
 *
 * @property string name
 * @property int attack
 * @property int armor
 * @property int race
 * @property int skin
 * @property int hat
 * @property int artifact
 *
 * @property int price
 * @property int real_price
 */
class Mercenary extends Model
{
    protected $table = 'wormix_mercenaries';

    protected $guarded = [];

    /**
     * Hires mercenary to specified user and deducting price from the profile
     * @param User $user
     * @param bool $useRealMoney
     * @return CharData Returns newly created mercenary's CharData entry
     * @throws NotEnoughMoneyException
     * @throws RequirementsNotMetException
     */
    public function assignTo(User $user, bool $useRealMoney) : CharData
    {
        $char = $user->char_data;
        $profile = $user->user_profile;

        if ($this->required_level > $char->level)
        {
            throw new RequirementsNotMetException('Required level is higher');
        }

        if ($useRealMoney)
        {
            if($this->real_price > $profile->real_money)
            {
                throw new NotEnoughMoneyException('Not enough real money');
            }

            $profile->real_money -= $this->real_price;
        }
        else
        {
            if ($this->price > $profile->money)
            {
                throw new NotEnoughMoneyException('Not enough money');
            }

            $profile->money -= $this->price;
        }

        $profile->save();

        $newChar = new CharData();
        $newChar->owner_id = $user->id;
        $newChar->profile_id = $this->id;
        $newChar->type = CharData::TEAM_MEMBER_MERCENARY;
        $newChar->name = $this->name;
        $newChar->level = $this->level;
        $newChar->armor = $this->armor;
        $newChar->attack = $this->attack;
        $newChar->race = $this->race;
        $newChar->skin = $this->skin;
        $newChar->hat = $this->hat;
        $newChar->artifact = $this->artifact;
        $newChar->save();

        return $newChar;
    }
}
