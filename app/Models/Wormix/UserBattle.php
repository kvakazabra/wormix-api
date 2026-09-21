<?php

namespace App\Models\Wormix;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int id
 * @property int user_id
 * @property int co_battle_id
 * @property int result
 * @property int type
 *
 * @property int mission_id
 *
 * @property array reagents
 * @property array collected_reagents
 * @property int exp_bonus
 * @property int random_seed
 * @property array awards
 *
 * @property int ban_type
 * @property string ban_note
 *
 * @property int total_turns
 * @property int total_damage_to_player
 * @property int total_damage_to_boss
 *
 * @property array used_items
 * @property array total_used_items
 *
 * @property UserBattle co_battle
 */
class UserBattle extends Model
{
    protected $table = 'wormix_users_battles';

    protected $casts = [
        'reagents' => 'array',
        'collected_reagents' => 'array',
        'awards' => 'array',
        'used_reagents' => 'array',
        'total_used_reagents' => 'array',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    public function co_battle() : BelongsTo
    {
        return $this->belongsTo(UserBattle::class, 'id', 'co_battle_id');
    }
}
