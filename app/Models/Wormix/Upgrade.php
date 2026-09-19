<?php

namespace App\Models\Wormix;

use App\Helpers\Wormix\WormixTrashHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int id
 * @property int upgrade_id
 * @property int prev_upgrade_id
 *
 * @property string description
 *
 * @property int level
 * @property int required_level
 * @property array reagents
 *
 * @property Upgrade prev_upgrade
 */
class Upgrade extends Model
{
    protected $table = 'wormix_upgrades';

    protected $casts = [
        'reagents' => 'array'
    ];

    public function prev_upgrade() : BelongsTo
    {
        return $this->belongsTo(Upgrade::class, 'prev_upgrade_id', 'upgrade_id');
    }

    /**
     * @return Attribute Returns an associative array of reagents [id => count] instead of pairs of [id, count]
     */
    public function reagents() : Attribute
    {
        return Attribute::make(
            get: fn ($value) => array_column(json_decode($value, true) ?? [], 1, 0)
        );
    }

    private const MAX_UPGRADE_DEPTH = 10;

    /**
     * @return ?Weapon Returns null on fail
     */
    public function baseWeapon() : ?Weapon
    {
        $weaponId = -1;
        $prevUpgrade = $this;
        for ($i = 0; $i < self::MAX_UPGRADE_DEPTH; ++$i)
        {
            if (WormixTrashHelper::isUpgradeId($prevUpgrade->prev_upgrade_id))
            {
                $prevUpgrade = $prevUpgrade->prev_upgrade;
                continue;
            }

            $weaponId = $prevUpgrade->prev_upgrade_id;
            break;
        }

        if ($weaponId === -1 || !WormixTrashHelper::isWeaponType($weaponId))
        {
            return null;
        }

        return Weapon::query()
            ->where('id', $weaponId)
            ->first();
    }

    public static function upgradeIdToRecipeId(int $upgradeId) : int
    {
        return Upgrade::query()
            ->where('upgrade_id', $upgradeId)
            ->first()?->id ?? -1;
    }
}
