<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Receiver extends JsonResource
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
            'name' => $this->fullname,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
