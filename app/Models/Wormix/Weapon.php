<?php

namespace App\Models\Wormix;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int id
 *
 * @property string name
 * @property string description
 * @property string note
 * @property string hint
 *
 * @property boolean is_starter
 * @property boolean hide_in_shop
 * @property boolean boss_weapon
 * @property boolean temporal
 *
 * @property int price
 * @property int real_price
 * @property int sell_price
 *
 * @property boolean infinite
 * @property int max_shots
 *
 * @property int required_friends
 * @property int required_level
 */

class Weapon extends Model
{
    protected $table = 'wormix_weapons';

    protected $guarded = [];
}
