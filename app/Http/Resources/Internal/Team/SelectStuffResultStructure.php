<?php

namespace App\Http\Resources\Internal\Team;

use App\Models\Wormix\CharData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read CharData $resource
 */
class SelectStuffResultStructure extends JsonResource
{
    private int $result;

    public function __construct($resource, int $result)
    {
        $this->result = $result;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'ProfileId' => $this->resource->profile_id,
            'ResultHat' => $this->result,
            'HatId' => $this->resource->hat,
            'ResultArtifact' => $this->result,
            'ArtifactId' => $this->resource->artifact,
        ];
    }
}
