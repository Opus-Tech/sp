<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRegistrationRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'email|max:191|required_without:phone',
            'phone' => 'string|min:7|max:19|required_without:email',
            //'phone' => 'required|regex:/(+234)[0-9]/|not_regex:/[a-z]/|min:9',
        ];
    }
}
