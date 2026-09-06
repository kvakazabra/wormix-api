<?php

namespace App\Models\Wormix;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int id
 * @property int owner_id
 * @property int item_id
 * @property string item_type
 * @property int count
 * @property int expire_at
 *
 * @property Weapon weapon
 */
class UserItem extends Model
{
    protected $table = 'wormix_users_items';

    public function weapon() : BelongsTo
    {
        return $this->belongsTo(Weapon::class, 'item_id', 'id');
    }
}
