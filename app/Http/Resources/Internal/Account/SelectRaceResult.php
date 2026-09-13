<?php

namespace App\Http\Resources\Internal\Account;

use App\Models\Wormix\CharData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read CharData $resource
 */
class SelectRaceResult extends JsonResource
{
    public const SUCCESS = 0;
    public const ERROR = 1;

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
            'Result' => $this->result,
            'Race' => $this->resource->race,
            'Skin' => $this->resource->skin,
        ];
    }
}
