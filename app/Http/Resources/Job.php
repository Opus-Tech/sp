<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Job extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'pickup' => $this->pickup,
            'destination' => $this->dropoff,
            'coordinates' => [
                    'pickup' => [
                        'latitude' => $this->pickup_lat,
                        'longitude' => $this->pickup_lng,
                    ], 
                    'destination' => [
                        'latitude' => $this->dropoff_lat,
                        'longitude' => $this->dropoff_lng,
                    ]
                ],
            'payment_method' => ($this->payment_method == 'credit_card')? 'card payment' : $this->payment_method,
            'item' => $this->item,
            'quantity' => $this->quantity,
            'reference_no' => $this->reference_no,
            'job_status' => $this->status,
            'price' => $this->price,
            'priority' => $this->delivery_mode,
            'note' => $this->delivery_note,
            'sender_details' => new Sender($this->sender),
            'receiver_details' => new Receiver($this->receiver),
        ];
    }
}
