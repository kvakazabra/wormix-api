<?php

namespace App\Observers\Wormix;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\Weapon;

class UserItemObserver
{
    //
    private function invalidateWeapon(UserItem $item) : void
    {
        $weapon = Weapon::query()
            ->where('id', $item->item_id)
            ->firstOrFail();

        if (!$weapon->infinite && $item->count <= 0)
        {
            $item->count = 1;
        }

        if (!$weapon->infinite || !$weapon->is_complex)
        {
            return;
        }

        // todo: this could go wrong if there are rewards with is_complex weapons
        // in such cases user will automatically get the first level of the weapon
        $isInLevelsRange =
            $item->count >= config('wormix.ids.weapons.level_base') ||
            $item->count <= $weapon->maxLevel();
        if (!$isInLevelsRange)
        {
            // Set 1st level
            $item->count = config('wormix.ids.weapons.level_base') - 1;
        }
    }

    public function saving(UserItem $item) : void
    {
        $item->item_type = UserItem::itemTypeForId($item->item_id);

        if (config('wormix.security.validate_users_items'))
        {
            if ($item->item_type == UserItem::WEAPON_TYPE)
            {
                $this->invalidateWeapon($item);
            }
        }
    }
}
