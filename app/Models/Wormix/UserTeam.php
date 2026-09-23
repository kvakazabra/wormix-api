<?php

namespace App\Models\Wormix;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int id
 * @property int user_id
 * @property int teammate_id
 * @property int order
 * @property bool active
 *
 * @property User owner
 * @property CharData char
 */
class UserTeam extends Model
{
    protected $table = 'wormix_users_teams';

    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function char() : BelongsTo
    {
        return $this->belongsTo(CharData::class, 'teammate_id', 'id');
    }
}
