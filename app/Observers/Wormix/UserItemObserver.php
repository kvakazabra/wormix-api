<?php

namespace App\Observers\Wormix;

use App\Models\Wormix\UserItem;

class UserItemObserver
{
    public function saving(UserItem $item) : void
    {
        $item->item_type = UserItem::itemTypeForId($item->item_id);
    }
}
