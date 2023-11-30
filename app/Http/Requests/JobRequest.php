<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'pickup'  => 'required|min:6', 
            'dropoff' => 'required|min:6',
            // 'email' => 'email|max:191|required_without:phone',
            // 'phone' => 'required_without:email|string|min:7|max:19',
            'delivery_note' => 'min:10',
            'pickup_latitude' => ['required','regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'pickup_longitude' => ['required','regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
            'dropoff_latitude' => ['required','regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'dropoff_longitude' => ['required','regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
            'quantity' => 'required|numeric',
            'item' => 'required|min:3',
            'vehicle_type' => 'required|in:bike,van,bicycle',
            'is_requester' => 'required|in:sender,receiver,third_party',
            'service' => 'required|in:today,express,dedicated,interstate',
            'distance' => 'required|numeric|between:0,99999.99',
            'price' => 'required|numeric|between:0,999999.99',
            'receiver_phone' => 'required|min:7',
            'note' => 'min:8',
        ];
    }
}
