<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobPaymentRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'fullname' => 'required|string|min:6',
            'email' => 'required|email|max:191',
            'reference' => 'required|min:6',
            'job_id' => 'required|numeric',
        ];
    }
}
