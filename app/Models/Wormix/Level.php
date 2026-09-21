<?php

namespace App\Models\Wormix;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property int required_experience
 * @property int team_size
 * @property array awards
 */
class Level extends Model
{
    protected $table = 'wormix_levels';

    protected $fillable = [
        'id',
        'required_experience',
        'team_size',
        'awards'
    ];

    protected $casts = [
        'awards' => 'json'
    ];
}
