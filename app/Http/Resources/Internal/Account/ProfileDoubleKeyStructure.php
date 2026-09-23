<?php

namespace App\Http\Resources\Internal\Account;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
class ProfileDoubleKeyStructure extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'LongId' => $this->resource->profile->user_id,
            'StringId' => (string)$this->resource->profile->user_id,
        ];
    }
}
