<?php

namespace App\Models\Wormix;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int family_id
 * @property string name
 *
 * @property boolean hide_in_shop
 * @property boolean hide_in_craft
 * @property int duration
 *
 * @property array craft_cost
 * @property array remake_cost
 */
class CraftedEquipment extends Model
{
    protected $table = 'wormix_crafted_equipment';

    protected $fillable = [
        'family_id',
        'name',

        'hide_in_shop',
        'hide_in_craft',
        'duration',

        'craft_cost',
        'remake_cost'
    ];

    protected $casts = [
        'craft_cost' => 'json',
        'remake_cost' => 'json'
    ];
}
