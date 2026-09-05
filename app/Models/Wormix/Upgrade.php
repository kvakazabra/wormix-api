<?php

namespace App\Models\Wormix;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int id
 * @property int upgrade_id
 * @property int prev_upgrade_id
 *
 * @property string description
 *
 * @property int level
 * @property int required_level
 * @property array reagents
 *
 * @property Upgrade prev_upgrade
 */
class Upgrade extends Model
{
    protected $table = 'wormix_upgrades';

    protected $casts = [
        'reagents' => 'array'
    ];

    public function prev_upgrade() : BelongsTo
    {
        return $this->belongsTo(Upgrade::class, 'prev_upgrade_id', 'upgrade_id');
    }
}
