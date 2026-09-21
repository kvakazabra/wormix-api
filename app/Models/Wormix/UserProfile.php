<?php

namespace App\Models\Wormix;

use App\Exceptions\Wormix\InvalidUsedItemException;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

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
 * @property array races
 * @property array skins
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
        'recipes' => 'array',
        'races' => 'array',
        'skins' => 'array',
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

    /**
     * @return bool Returns false if there are not enough reagents/realmoney
     */
    public function consumeReagents(array $reagents, bool $consumeReal) : bool
    {
        // todo: use exceptions here instead of bool

        $userReagents = $this->reagents;
        $totalPrice = 0;

        foreach ($reagents as $id => $count)
        {
            if ($count <= 0)
            {
                Log::warning("consumeReagents: count is <= 0");
                continue;
            }

            $userCount = ($userReagents[$id] ?? 0);
            if ($count > $userCount)
            {
                if (!$consumeReal)
                {
                    return false;
                }

                $reagent = Reagent::query()
                    ->where('id', $id)
                    ->firstOrFail();

                $missingCount = $count - $userCount;
                $totalPrice += $reagent->price * $missingCount;
            }

            // Either decreases by a count or sets to 0
            $userReagents[$id] = max($userCount - $count, 0);
        }

        // Use a default currency rate, buying reagents is available only for real_money
        $totalRealPrice = (int)ceil(
            $totalPrice / config('wormix.game.missions.buy.money')
        );
        if ($totalRealPrice > $this->real_money)
        {
            return false;
        }

        $this->real_money -= $totalRealPrice;
        $this->reagents = $userReagents;
        $this->save();
        return true;
    }

    public function grantReagents(array $reagents) : void
    {
        $userReagents = $this->reagents;
        foreach ($reagents as $id => $count)
        {
            if ($count <= 0)
            {
                Log::warning("grantReagents: count is <= 0");
                continue;
            }

            $userReagents[$id] = ($userReagents[$id] ?? 0) + $count;
        }

        $this->reagents = $userReagents;
        $this->save();
    }

    /**
     * @param array $items Json array of pairs of 'Id' and 'Count'
     * @throws \Exception
     */
    public function consumeItems(array $items) : void
    {
        foreach ($items as $item)
        {
            $id = $item['Id'];
            $count = $item['Count'];

            $userItem = UserItem::query()
                ->where('owner_id', $this->user_id)
                ->where('item_id', $id)
                ->first();
            if (!$userItem)
            {
                throw new InvalidUsedItemException('User ' . $this->user_id . ' does not own the item ' . $id);
            }

            if ($count > $userItem->count)
            {
                throw new InvalidUsedItemException('Item ' . $id . ' of user ' . $this->user_id . ' has less count than used');
            }

            $userItem->count = max($userItem->count - $count, 0);
            $userItem->save();
        }

    }
}
