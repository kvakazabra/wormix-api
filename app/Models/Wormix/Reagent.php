<?php

namespace App\Models\Wormix;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string name
 * @property int price
 */
class Reagent extends Model
{
    protected $table = 'wormix_reagents';

    protected $fillable = [
        'id',
        'name',
        'price'
    ];
}
