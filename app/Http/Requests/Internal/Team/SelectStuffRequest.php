<?php

namespace App\Http\Requests\Internal\Team;

use Illuminate\Foundation\Http\FormRequest;

class SelectStuffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize() : bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules() : array
    {
        return [
            'internal_user_id' => 'required|integer|exists:users,id',

            'SelectStuffs' => 'required|array|between:1,7',
            'SelectStuffs.*.ProfileId' => 'required|integer',
            'SelectStuffs.*.HatId' => 'required|integer',
            'SelectStuffs.*.ArtifactId' => 'required|integer',
        ];
    }
}
