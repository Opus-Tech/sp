<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistanceCostRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'delivery_mode' => 'required|in:today,express,dedicated,interstate',
            'distance' => 'required|numeric|between:0,99999.99',
        ];
    }
}
