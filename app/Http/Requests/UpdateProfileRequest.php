<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'firstname' => 'string|min:3',
            'lastname' => 'string|min:3',
            'phone' => 'string|min:7|max:19',
            'avatar' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
