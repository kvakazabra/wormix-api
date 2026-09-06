<?php

namespace App\Models\Wormix;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string name
 *
 * @property boolean hide_in_shop
 * @property int price
 * @property int real_price
 * @property int duration
 *
 * @property int required_scenario
 * @property int required_level
 * @property int required_rating
 */
class Equipment /* Hat/Artifact */ extends Model
{
    protected $table = 'wormix_equipment';

    protected $guarded = [];

    protected $casts = [
        'hide_in_shop' => 'boolean'
    ];
}
