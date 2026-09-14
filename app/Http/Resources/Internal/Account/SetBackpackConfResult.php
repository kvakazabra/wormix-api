<?php

namespace App\Http\Resources\Internal\Account;

use App\Models\Wormix\UserBackpack;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserBackpack $resource
 */
class SetBackpackConfResult extends JsonResource
{

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
            'Configs' => $this->resource->configurations,
            'ActiveConfig' => $this->resource->current_configuration,
        ];
    }
}
