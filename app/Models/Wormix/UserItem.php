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
 * @property Equipment equipment
 */
#[ObservedBy(UserItemObserver::class)]
class UserItem extends Model
{
    protected $table = 'wormix_users_items';

    public const WEAPON_TYPE = "weapon";

    public const HAT_TYPE = "hat";

    public const ARTIFACT_TYPE = "artifact";

    private const NONE_TYPE = "none";

    public static function itemTypeForId(int $id) : string
    {
        if (WormixTrashHelper::isWeaponType($id))
        {
            return self::WEAPON_TYPE;
        }

        if (WormixTrashHelper::isArtifactType($id))
        {
            return self::ARTIFACT_TYPE;
        }

        if (WormixTrashHelper::isHatType($id))
        {
            return self::HAT_TYPE;
        }

        return self::NONE_TYPE;
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
