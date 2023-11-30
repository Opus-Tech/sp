<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentityCardRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'identity_card' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
