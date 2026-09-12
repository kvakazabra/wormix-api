<?php

namespace App\Models\Wormix;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int user_id
 * @property array cookies
 */
class UserCookies extends Model
{
    protected $table = 'wormix_users_cookies';

    protected $primaryKey = 'user_id';

    protected $casts = [
        'cookies' => 'array',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
