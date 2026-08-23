<?php

namespace App\Http\Resources\Internal\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchieveLoginError extends JsonResource
{
    private int $code;

    public function __construct($request, $code)
    {
        $this->code = $code;
        parent::__construct($request);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'Code' => $this->code
        ];
    }
}
