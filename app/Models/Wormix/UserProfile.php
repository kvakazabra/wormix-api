<?php

namespace App\Models\Wormix;

use App\Exceptions\Wormix\AlreadyBoughtException;
use App\Exceptions\Wormix\InternalServerException;
use App\Exceptions\Wormix\InvalidUsedItemException;
use App\Exceptions\Wormix\NotEnoughMoneyException;
use App\Exceptions\Wormix\NotEnoughReagentsException;
use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Exception;

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
     * @throws Exception
     */
    public function consumeReagents(array $reagents, bool $consumeReal) : void
    {
        $userReagents = $this->reagents;
        $totalPrice = 0;

        foreach ($reagents as $id => $count)
        {
            if ($count <= 0)
            {
                throw new InternalServerException('Consuming reagent with count <= 0');
            }

            $userCount = ($userReagents[$id] ?? 0);
            if ($count > $userCount)
            {
                if (!$consumeReal)
                {
                    throw new NotEnoughReagentsException();
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
            $totalPrice / config('wormix.game.buy.real_rate')
        );
        if ($totalRealPrice > $this->real_money)
        {
            throw new NotEnoughMoneyException();
        }

        $this->real_money -= $totalRealPrice;
        $this->reagents = $userReagents;
        $this->save();
    }

    /**
     * @param array $reagents Associative array like [52 => 5, ...]
     * @throws Exception
     */
    public function grantReagents(array $reagents, bool $ignoreErrors) : void
    {
        $userReagents = $this->reagents;
        foreach ($reagents as $id => $count)
        {
            if ($count <= 0)
            {
                if (!$ignoreErrors)
                {
                    continue;
                }

                throw new InternalServerException('Granting reagent with count <= 0');
            }

            $userReagents[$id] = ($userReagents[$id] ?? 0) + $count;
        }

        $this->reagents = $userReagents;
        $this->save();
    }

    /**
     * @param array $items Json array of pairs of 'Id' and 'Count'
     * @throws Exception
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

    /**
     * @param array $items Associative array [[id => count], ...]
     * @param bool $equipStuff If set, stuff will get automatically equipped afterward
     * @param bool $ignoreErrors If set, exception won't get thrown and $items will get fully processed
     * @throws Exception
     */
    public function grantItems(array $items, bool $equipStuff = true, bool $ignoreErrors = false) : void
    {
        $user = $this->user;

        foreach ($items as $itemId => $count)
        {
            $oldItem = UserItem::query()
                ->where('owner_id', $this->user_id)
                ->where('item_id', $itemId)
                ->first();

            if (WormixTrashHelper::isStuffType($itemId))
            {
                if ($oldItem)
                {
                    if ($ignoreErrors)
                    {
                        continue;
                    }

                    throw new AlreadyBoughtException("Stuff already bought");
                }

                $newItem = new UserItem();
                $newItem->item_id = $itemId;
                $newItem->owner_id = $this->user_id;
                $newItem->count = -1;
                $newItem->save();

                if ($equipStuff)
                {
                    $char = $user->char_data;
                    match (true)
                    {
                        WormixTrashHelper::isArtifactType($itemId)
                            => $char->artifact = $itemId,
                        WormixTrashHelper::isHatType($itemId)
                            => $char->hat = $itemId
                    };
                    $char->save();
                }

                continue;
            }

            if ($oldItem?->count === -1)
            {
                if ($ignoreErrors)
                {
                    continue;
                }

                throw new AlreadyBoughtException();
            }

            if (WormixTrashHelper::isWeaponType($itemId))
            {
                $weapon = Weapon::query()
                    ->where('id', $itemId)
                    ->firstOrFail();

                $item = $oldItem ?? new UserItem();
                $item->item_id = $itemId;
                $item->owner_id = $this->user_id;

                // If finite - add count
                if (!$weapon->infinite)
                {
                    $item->count = ($oldItem?->count ?? 0) + $count;
                }
                // Make item infinite if weapon is not complex
                else if (!$weapon->is_complex)
                {
                    $item->count = $count < 0 ?
                        -1 : ($oldItem?->count ?? 0) + $count;
                }
                // Set current level for complex weapons
                else
                {
                    $item->count = max(
                        ($oldItem?->count ?? config('wormix.ids.weapons.level_base')) - abs($count),
                        $weapon->maxLevel()
                    );
                }

                $item->save();
            }
        }
    }
}
