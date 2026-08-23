<?php

namespace App\Http\Requests\Internal\Account;

use Illuminate\Foundation\Http\FormRequest;

class AchieveLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tcp_session' => 'required|uuid',
            'Id' => 'required|string',
            'AuthKey' => 'required|string'
        ];
    }

    public function messages(): array{
        return [
            'tcp_session.required' => 'TCP session is required.',
            'tcp_session.uuid' => 'TCP session must be is GUID.',

            'Id.required' => 'id is required',
            'Id.string' => 'id must be a string',

            'AuthKey.required' => 'auth_key is required',
            'AuthKey.string' => 'auth_key must be a string',
        ];
    }
}
