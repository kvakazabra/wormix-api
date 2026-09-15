<?php

namespace App\Models\Wormix;

use App\Models\User;
use App\Observers\Wormix\WormDataObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int owner_id
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
#[ObservedBy(WormDataObserver::class)]
class CharData extends Model
{
    protected $table = 'wormix_characters_data';

    protected $primaryKey = 'owner_id';

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
}
