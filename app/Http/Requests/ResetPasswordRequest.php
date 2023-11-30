<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required|numeric|digits:6',
            'phone' => 'required|string|min:7|max:19',
            'password' => 'required|string|min:3',
            'confirm_password' => 'required|same:password|string'
        ];
    }
}
