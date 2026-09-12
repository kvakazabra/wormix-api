<?php

namespace App\Models\Wormix;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int owner_id
 * @property int current_configuration
 * @property array configurations
 * @property array hotkeys
 */
class UserBackpack extends Model
{
    protected $table = 'wormix_users_backpacks';

    protected $primaryKey = 'owner_id';

    protected $casts = [
        'configurations' => 'array',
        'hotkeys' => 'array',
    ];

    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'owner_id');
    }
}
