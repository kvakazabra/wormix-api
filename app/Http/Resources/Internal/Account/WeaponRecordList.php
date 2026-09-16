<?php

namespace App\Http\Resources\Internal\Account;

use App\Models\Wormix\UserItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserItem $resource
 */
class WeaponRecordList extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'Id' => $this->resource->item_id,
            'Count' => $this->resource->count
        ];
    }
}
