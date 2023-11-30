<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Http\Requests\Rules\MatchOldPassword;

class ChangePasswordRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'current_password' => ['required', 'string', 'min:3', new MatchOldPassword],
            'new_password' => 'required|string|min:3',
            'confirm_password' => 'required|same:new_password|string|min:3',
        ];
    }
}
