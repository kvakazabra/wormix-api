<?php

namespace App\Models\Wormix;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int user_id
 * @property int battle_tokens
 * @property int wager_tokens
 * @property int boss_tokens
 * @property int heroic_tokens
 * @property int solo_mission_id
 * @property int coop_mission_id
 * @property int current_battle_id
 *
 * @property UserBattle current_battle
 */
class UserArena extends Model
{
    protected $table = 'wormix_users_arenas';

    protected $primaryKey = 'user_id';

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    public function current_battle() : BelongsTo
    {
        return $this->belongsTo(UserBattle::class, 'id', 'current_battle_id');
    }
}
