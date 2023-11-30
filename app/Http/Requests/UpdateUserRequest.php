<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'firstname' => 'required|string|min:3',
            'lastname' => 'required|string|min:3',
            'email' => 'email|max:191|required_without:phone',
            'phone' => 'required_without:email|string|min:7|max:19',
            'password' => 'required|string|min:6|max:191',
            'confirm_password' => 'required|same:password|min:6',
        ];
    }
}
