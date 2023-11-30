<?php

namespace App\Http\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{

    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'phonenumber' => $user->phone,
            'avatar' => (!is_null($user->profile_pic))? env('APP_URL').'/uploads/avatars/'.$user->profile_pic : null,
            'account_verified' => ($user->hasVerifiedAccount())? true : false,
        ];
    }
}
