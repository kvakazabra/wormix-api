<?php

namespace App\Models\Wormix;

use App\Helpers\Wormix\WormixTrashHelper;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Log;

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
 * @property boolean is_complex
 * @property int max_shots
 *
 * @property int required_friends
 * @property int required_level
 */

class Weapon extends Model
{
    protected $table = 'wormix_weapons';

    protected $guarded = [];

    // Max level is a negative value, starts with -11 and goes below that base: {-11..-11-max_shots}
    public function maxLevel() : int
    {
        if (!$this->is_complex)
        {
            return 0;
        }

        if ($this->max_shots < 0)
        {
            Log::error("Weapon: is_complex=true but max_shots < 0!");
            return 0;
        }

        return config('wormix.ids.weapons.level_base') - $this->max_shots;
    }
}
