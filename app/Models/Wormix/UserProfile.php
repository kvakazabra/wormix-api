<?php

namespace App\Models\Wormix;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int user_id
 * @property int money
 * @property int real_money
 * @property int rank
 * @property int rank_points
 * @property int rating
 * @property int reaction_rate
 * @property int extra_group_slots
 * @property int race_change_timestamp
 *
 * @property array reagents
 * @property array recipes
 *
 * @property Collection|UserItem[] items
 * @property Collection|UserItem[] weapons
 * @property Collection|UserItem[] hats
 * @property Collection|UserItem[] artifacts
 * @property Collection|UserItem[] equipments
 * @property User user
 * @property HasMany teammates
 */
class UserProfile extends Model
{
    protected $table = 'wormix_user_profiles';

    protected $primaryKey = 'user_id';

    protected $casts = [
        'reagents' => 'array',
        'recipes' => 'array'
    ];

    protected $fillable = [
        'money',
        'real_money',
        'rating',
        'reaction_rate'
    ];

    public function items() : HasMany
    {
        return $this->hasMany(UserItem::class, 'owner_id', 'user_id');
    }

    protected function itemsOfType(array $types) : HasMany
    {
        return $this->hasMany(UserItem::class, 'owner_id', 'user_id')
            ->whereIn('item_type', $types);
    }

    public function weapons() : HasMany
    {
        return $this->itemsOfType([UserItem::WEAPON_TYPE]);
    }

    public function hats() : HasMany
    {
        return $this->itemsOfType([UserItem::HAT_TYPE]);
    }

    public function artifacts() : HasMany
    {
        return $this->itemsOfType([UserItem::ARTIFACT_TYPE]);
    }

    public function equipments() : HasMany
    {
        return $this->itemsOfType([UserItem::ARTIFACT_TYPE, UserItem::HAT_TYPE]);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function teammates() : HasMany
    {
        return $this->hasMany(UserTeam::class, 'user_id', 'user_id');
    }
}
