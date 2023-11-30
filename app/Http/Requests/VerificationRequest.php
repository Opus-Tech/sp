<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerificationRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code'  => 'required|numeric', 
            'phone' => 'required_without:email|string|min:7|max:19',
            'email' => 'required_without:phone|string|min:6'
        ];
    }
}
