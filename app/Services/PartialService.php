<?php

namespace App\Services;

use App\Models\User; 
use Illuminate\Support\Facades\Mail; 
use App\Mail\UserSignup;
use App\Mail\LenderSignup;
use App\Mail\ResetsPassword;
use App\Models\Verification;
use App\Models\UserLending;
use Illuminate\Support\Facades\Hash;

class PartialService
{ 

    public function generatePIN($digits = 6){
        $i = 0; //counter
        $pin = ""; //our default pin is blank.
        while($i < $digits){
            //generate a random number between 0 and 9.
            $pin .= mt_rand(0, 9);
            $i++;
        }
        return $pin;
    }

    public function sendOtp($data)
    {
        $data['otpCode'] = $this->generatePIN(6);
        $data['signature'] = base64_encode($_ENV['APP_SALT'].$data['id']);


        $saveDB = Verification::updateOrCreate(
            ['user_id' => $data['id']],
            ['token' => $data['otpCode'], 'status' => 0]
        );
 
        $sendMail = $this->sendMail($data, 'UserSignup');
    }

    public function resetMail($data)
    { 
        Mail::to($data['email'])->send(new ResetsPassword($data));
    }

    public function sendMail($data, $template)
    {  
        Mail::to($data['email'])->send(new UserSignup($data));
    }

}