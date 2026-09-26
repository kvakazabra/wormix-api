<?php

namespace App\Http\Resources\Internal\Team;

use App\Models\Wormix\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserProfile $resource
 */
class SelectStuffResult extends JsonResource
{
    public const Success = 0;

    public const Error = 1;

    private int $result;

    private array $profileIds;

    public function __construct($resource, int $result, array $profileIds)
    {
        $this->result = $result;
        $this->profileIds = $profileIds;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        $teammates = $this->resource->teammates
            ->filter(function ($teammate) {
                return in_array($teammate->char->profile_id, $this->profileIds);
            });

        return [
            'SelectStuffResults' => $teammates->map(
                fn ($teammate) => new SelectStuffResultStructure($teammate->char, $this->result)
            )->values()->all()
        ];
    }
}
