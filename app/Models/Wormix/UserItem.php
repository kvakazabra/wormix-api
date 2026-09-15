<?php

namespace App\Models\Wormix;

use RuntimeException;
use App\Observers\Wormix\UserItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Helpers\Wormix\WormixTrashHelper;

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
#[ObservedBy(UserItemObserver::class)]
class UserItem extends Model
{
    protected $table = 'wormix_users_items';

    public const WEAPON_TYPE = "weapon";

    public const HAT_TYPE = "hat";

    public const ARTIFACT_TYPE = "artifact";

    public static function insert(array $values) : bool
    {
        // Due to observers, use new instead
        throw new RuntimeException("Insert is not allowed on this type of object");
    }

    public static function upsert(array $values, $uniqueBy, $update = null) : int
    {
        // Due to observers, use new instead
        throw new RuntimeException("Upsert is not allowed on this type of object");
    }

    public static function itemTypeForId(int $id) : string
    {
        if (($id > WormixTrashHelper::WEAPON_MIN_INDEX &&
                $id < WormixTrashHelper::WEAPON_MAX_INDEX) ||
            $id > WormixTrashHelper::WEAPON_EXCEPT_INDEX)
        {
            return self::WEAPON_TYPE;
        }

        if ($id > WormixTrashHelper::ARTIFACTS_MIN_INDEX &&
            $id < WormixTrashHelper::ARTIFACTS_MAX_INDEX)
        {
            return self::ARTIFACT_TYPE;
        }

        if ($id > WormixTrashHelper::HATS_MIN_INDEX &&
            $id < WormixTrashHelper::HATS_MAX_INDEX)
        {
            return self::HAT_TYPE;
        }

        return "none";
    }

    public function equipment() : BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'item_id', 'id');
    }

    public function weapon() : BelongsTo
    {
        return $this->belongsTo(Weapon::class, 'item_id', 'id');
    }
}
