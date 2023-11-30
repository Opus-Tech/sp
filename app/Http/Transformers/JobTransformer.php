<?php

namespace App\Http\Transformers;

use App\Models\Job;
use League\Fractal\TransformerAbstract;

class JobTransformer extends TransformerAbstract
{

    public function transform(Job $job)
    {
        return [
            'id' => $job->id,
            'pickup_addresss' => $job->pickup,
            'dropoff_address' => $job->dropoff,
            'item' => $job->item,
            'status' => $job->status,
            'total_distance' => $job->distance,
            'total_cost' => $job->price,
            'payment_method' => ($job->payment_method)? $job->payment_method : 'card',
            'payment_status' => ($job->payment_status == true) ? 'paid' : 'un-paid',
            'created_at' => $job->created_at,
            'rider' => [
                'name' => $job->pairedrider->rider->firstname.' '.$job->pairedrider->rider->lastname,
                'username' => $job->pairedrider->rider->username,
                'phone' => $job->pairedrider->rider->phone,
                'avatar' => $job->pairedrider->rider->profile_pic,
                'plate_no' => $job->pairedrider->rider->vehicle_reg_numb,
                'dispatch_type' => $job->pairedrider->rider->vehicle_type,
            ],
            'receiver' => [
                'name' => $job->receiver->fullname,
                'phone' => $job->receiver->phone,
                'email' => $job->receiver->email,
                'address' => $job->dropoff,
            ],
            'sender' => [
                'name' => $job->sender->fullname,
                'phone' => $job->sender->phone,
                'email' => $job->sender->email,
                'address' => $job->pickup,
            ],
        ];
    }
}
