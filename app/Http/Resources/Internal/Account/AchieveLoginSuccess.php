<?php

namespace App\Http\Resources\Internal\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchieveLoginSuccess extends JsonResource
{
    private string $session_key;

    public function __construct($request, $session_key)
    {
        $this->session_key = $session_key;
        parent::__construct($request);
    }

    public function toArray(Request $request): array
    {
        return [
            "SessionId" => $this->session_key,
            "LoginTime" => time()
        ];
    }
}
