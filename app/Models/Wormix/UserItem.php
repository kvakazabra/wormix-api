<?php

namespace App\Models\Wormix;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Controllers\Internal\UpgradeController;

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

    public const WEAPON_TYPE = "weapon";

    public const WEAPON_UPGRADE_TYPE = "weapon_upgrade";

    public const HAT_TYPE = "hat";

    public static function itemTypeForId(int $id) : string
    {
        if ($id < UpgradeController::UPGRADE_BASE)
        {
            return self::WEAPON_TYPE;
        }

        // Upgrades cannot be assigned in this table, but keep it here for now
        if ($id > UpgradeController::UPGRADE_BASE && $id < WormixTrashHelper::STUFF_START_INDEX)
        {
            return self::WEAPON_UPGRADE_TYPE;
        }

        if ($id > WormixTrashHelper::STUFF_START_INDEX)
        {
            return self::HAT_TYPE;
        }

        return "null";
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
