<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\VerificationRequest;

use App\Http\Requests\ResendVerificationRequest;

use App\Http\Requests\VerificationCallRequest;

use App\Services\UserService;

use Twilio\TwiML\VoiceResponse;

use Twilio\Rest\Client;

use JWTAuth;
    
use Tymon\JWTAuth\Exceptions\JWTException;

class AccountVerificationController extends BaseController
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function verify(VerificationRequest $request)
    {
        if(isset($request->phone)){
            $user = $this->userService->findByPhonenumber($request->phone);

            if ($user->hasVerifiedAccount()) {
                return $this->response->array([
                        'message' => 'Account verified successfully.',
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        //'expires_in' => Auth::guard()->factory()->getTTL() * 60,
                    ], 200);
            }

            if ($user->checkSMSVerify($request->code)->status == 'approved') {
                $user->markAccountAsVerified();
            }else{
                return $this->response->error('The verification code your provided is wrong. Please try again or request another code.', 403);
            }
        }else{
            $user = $this->userService->findByEmail($request->email);
            if ($user->hasVerifiedAccount()) {
                return $this->response->array([
                        'message' => 'Account verified successfully.',
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        //'expires_in' => Auth::guard()->factory()->getTTL() * 60,
                    ], 200);
            }

            if($request->code === $user->verification_code){
                $user->markAccountAsVerified();
            }else{
                return $this->response->error('The verification code your provided is wrong. Please try again or request another code.', 403);
            }
        }

        $token = JWTAuth::fromUser($user);

        

        return $this->response->array([
                    'message' => 'Account verified successfully.',
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    //'expires_in' => auth()->factory()->getTTL() * 60,
                ], 200);
    }

    public function reVerify(ResendVerificationRequest $request)
    {
        $user = $this->userService->findByPhonenumber($request->phone);

        $token = JWTAuth::fromUser($user);

        if ($user->hasVerifiedAccount()) {
            return $this->response->array([
                    'message' => 'Account has been previously verified successfully.',
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    //'expires_in' => Auth::guard()->factory()->getTTL() * 60,
                ], 200);
        }else{
            $user->sendSMSToVerify();
            return $this->response->array([
                    'message' => 'Please confirm your phone number with the verification code sent to '.$request->phone,
                    'userid' => $user->id,
                    'verification_destination' => $request->phone,
                ], 200);
        }
    }

    /**
     * Send a voice call and code to phone or email address
     */

     public function makeCall(VerificationCallRequest $request)
     {
        $user = $this->userService->findByPhonenumber($request->phone);
        if(!$user){
            return $this->response->error('Access denied.', 403);
        }
        $code = random_int(100000, 999999);
        $user->verification_code = $code;
        if($user->save()){
            $client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));

            $client->calls->create(
                $user->phone,
                "+12058606568",
                ["url" => "http://api.spatchng.com/api/call/{$code}"]
            );
            return $this->response->array([
                'status' => true,
                'message' => 'Call initialized.',
                //'expires_in' => auth()->factory()->getTTL() * 60,
            ], 200);
        }else{
            return $this->response->error('Call initialized failed.', 403);
        }
        
     }

     public function voiceML($code)
    {
        $code = $this->formatCode($code);
        $response = new VoiceResponse();
        $response->say("Hi, welcome to spatch logistics. This is your verification code. {$code}. I repeat, {$code}.");
        return $response;
    }

    public function formatCode($code)
    {
        $collection = collect(str_split($code));
        return $collection->reduce(
            function ($carry, $item) {
                return "{$carry}. {$item}";
            }
        );
    }
}
