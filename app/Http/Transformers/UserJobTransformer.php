<?php

namespace App\Http\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserJobTransformer extends TransformerAbstract
{

    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'phonenumber' => $user->phone,
            'avatar' => $user->profile_pic,
            'account_verified' => ($user->hasVerifiedAccount())? true : false,
            'jobs' => [
                'details' => $user->jobs->makeHidden(['reference_no', 'delivery_note', 'reciever_email', 'reciever_phone', 'updated_at']),
                'total_jobs' => count($user->jobs),
            ]
        ];
    }
}
