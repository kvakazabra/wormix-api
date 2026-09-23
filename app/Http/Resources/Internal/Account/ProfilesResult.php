<?php

namespace App\Http\Resources\Internal\Account;

use App\Models\User;
use App\Models\Wormix\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
class ProfilesResult extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'UserProfileStructures' => UserProfileStructure::collection(UserProfile::query()
                ->whereIn('user_id', $this->resource)
                ->get()
            ),
        ];
    }
}
