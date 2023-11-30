<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RiderLoginRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'phone' => 'string|min:7|max:19|required',
            'password' => 'required|string|min:4',
        ];
    }
}
