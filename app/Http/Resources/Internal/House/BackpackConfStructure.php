<?php

namespace App\Http\Resources\Internal\House;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackpackConfStructure extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'Config' => $this->resource,
        ];
    }
}
