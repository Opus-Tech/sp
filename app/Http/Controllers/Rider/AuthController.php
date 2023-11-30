<?php

namespace App\Http\Controllers\Rider;

use JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuthuser;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use App\Services\RiderService;
use App\Http\Controllers\Controller;
use App\Models\RidergpsLocators;
use App\Http\Transformers\RiderTransformer;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\RiderRegistrationRequest;
use App\Http\Requests\DeleteRiderRegistrationRequest;
use App\Http\Requests\UpdateRiderRequest;
use App\Http\Requests\RiderLoginRequest;
use App\Http\Requests\UpdateRiderProfileRequest;
use App\Http\Requests\VerificationRequest;
use App\Http\Requests\RiderResendCodeRequest;
use App\Http\Requests\VerificationCallRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\IdentityCardRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LogGpsRequest;
use Twilio\TwiML\VoiceResponse;
use Twilio\Rest\Client;
use Image;
use File;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use Helpers;
    private $riderService;
    private $authService;

    public function __construct(RiderService $riderService, JWTAuth $auth)
    {
        $this->riderService = $riderService;
        $this->authService = $auth;
    }

    public function register(RiderRegistrationRequest $request)
    {  

        $rider = $this->riderService->create($request->all());
        if(!is_null($rider->phone)) {
            $rider->sendSMSToVerify();
            $address = $rider->phone;
            $message = 'Please confirm your phone number with the verification code sent to '.$address;
        }
        return $this->response->array([
                    'message' => $message,
                    'userid' => $rider->id,
                    'verification_destination' => $address,
                ], 200);
    }

    public function resendCode(RiderResendCodeRequest $request)
    {
        $rider = $this->riderService->findByPhonenumber($request->phone);
        if(!is_null($rider->phone)) {
            $rider->sendSMSToVerify();
            $address = $rider->phone;
            $message = 'Please confirm your phone number with the verification code sent to '.$address;
        }
        return $this->response->array([
                    'message' => $message,
                    'userid' => $rider->id,
                    'verification_destination' => $address,
                ], 200);
    }

    public function update(UpdateRiderRequest $request)
    {
        $rider = JWTAuthuser::user();
        $update= $this->riderService->update($rider, $request->all());
        if($update){
            return $this->response->array([
                'success' => true,
                'message' => 'Rider Account Updated successfully',
                'data' => [
                    'rider' => array(
                            'id' => $rider->id,
                            'firstname' =>$rider->firstname,
                            'lastname' =>$rider->lastname,
                            'displayname' => $rider->username,
                            'phone' =>$rider->phone,
                            'avatar' => $rider->profile_pic,
                            'plat_no' => $rider->vehicle_reg_numb,
                            'spatch_type' => $rider->vehicle_type,
                            'verified' => ($rider->verified)? true : false,
                            'activation' => [
                                'status' => ($rider->active)? true : false,
                            ]
                        ),
                ]
            ], 200);
        }else{
            return $this->response->array([
                'message' => 'Rider Account Update Failed',
            ], 500);
        }
        
    }

    public function verify(VerificationRequest $request)
    {

        $rider = $this->riderService->findByPhonenumber($request->phone);

        $token = JWTAuth::fromUser($rider);

        if ($rider->hasVerifiedAccount()) {
            return $this->response->array([
                    'message' => 'Account verified successfully.',
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    //'expires_in' => Auth::guard()->factory()->getTTL() * 60,
                ], 200);
        }

        if ($rider->checkSMSVerify($request->code)->status == 'approved') {
            $rider->markAccountAsVerified();
        }else{
            return $this->response->error('The verification code your provided is wrong. Please try again or request another code.', 403);
        }

        return $this->response->array([
                    'message' => 'Account verified successfully.',
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    //'expires_in' => auth()->factory()->getTTL() * 60,
                ], 200);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $rider = JWTAuthuser::user();
        $updatepassword = $this->riderService->find($rider->id);
        if($updatepassword->update(['password'=> Hash::make($request->new_password)])){
            return $this->response->array([
                    'status' => true,
                    'message' => 'Password successfully changed.',
                ], 200);
        }else{
            return $this->response->error('Password could not be changed at the moment.', 403);
        }
    }

    public function makeCall(VerificationCallRequest $request)
     {
        $user = $this->riderService->findByPhonenumber($request->phone);
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
                ["url" => "http://api.spatchng.com/api/rider/call/{$code}"]
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
        $response->say("Hi, welcome to the spatch logistic's rider app. This is your verification code. {$code}. I repeat, {$code}.");
        return $response;
    }

    public function profileupdate(UpdateRiderProfileRequest $request)
    {
        $rider = JWTAuthuser::user();
        $data = $request->all();
        if ($request->hasfile('avatar')) {
            $avatar = $request->file('avatar');
            $filename = time() . '.' . $avatar->getClientOriginalExtension();
            $data['avatar'] = $filename;
            File::delete(public_path() . '/uploads/riders/avatars/', $rider->profile_pic);
            Image::make($avatar)->resize(300, 300)->save( public_path('/uploads/riders/avatars/' . $filename) );
            //$data['avatar'] = Image::make(storage_path('public/uploads/avatars/' . $avatar))->resize(300, 300)->response();
        }
        $update= $this->riderService->update($rider, $data);
        if($update){
            return $this->response->array([
                'success' => true,
                'message' => 'Rider Account Updated successfully',
                'data' => [
                    'user' => array(
                            'firstname' =>$rider->firstname,
                            'lastname' =>$rider->lastname,
                            'displayname' => $rider->username,
                            'phone' =>$rider->phone,
                            'avatar' => env('APP_URL').'/uploads/riders/avatars/'.$rider->profile_pic,
                            'plat_no' => $rider->vehicle_reg_numb,
                            'spatch_type' => $rider->vehicle_type,
                            'verified' => ($rider->verified)? true : false,
                            'activation' => [
                                'status' => ($rider->active)? true : false,
                            ], 
                            'job_details' => [
                                'statistics' => [
                                    'today' => [
                                        'pending' => 0,
                                        'active' => 0,
                                        'completed' => 0,
                                    ],
                                    'total' => [
                                        'pending' => 0,
                                        'active' => 0,
                                        'completed' => 0,
                                    ],
                                ], 
                            ] 
                        ),
                ]
            ], 200);
        }else{
            return $this->response->array([
                'status' => false,
                'message' => 'Rider Account Update Failed',
            ])->setStatusCode(500);
        }
    }

    public function newPassword(ResetPasswordRequest $request){
        $data = $request->all();
        $rider = $this->riderService->findByPhonenumber($data['phone']);
        if (!$rider) {
            return $this->response->array([
                    'success' => false,
                    'message' => 'Invalid phonenumber supplied.',
            ])->setStatusCode(500);
        }
        $change = $this->riderService->resetPassword($rider, $data);
        if($change){
            return $this->response->array([
                'status' => true,
                'message' => 'Password reset successfully.',
            ], 200);
        }else{
            return $this->response->array([
                'status' => false,
                'message' => 'Password reset failed.',
            ])->setStatusCode(403);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $data = $request->all();
        $rider = $this->riderService->findByPhonenumber($data['phone']);
        if (!$rider) {
            return $this->response->array([
                    'success' => false,
                    'message' => 'Invalid phonenumber supplied.',
            ])->setStatusCode(500);
        }
        $code = random_int(100000, 999999);
        $send = $this->riderService->sendOtpCode($rider, $code);
        if ($send) {
            $client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $client->messages->create(
                // Where to send a text message (your cell phone?)
                $rider->phone,
                array(
                    'from' => '+12058606568',
                    'body' => 'Your password reset code is: '.$code,
                )
            );
            return $this->response->array([
                    'success' => true,
                    'message' => 'Password reset code sent to your mobile number',
            ], 200);
        }else{
            return $this->response->array([
                    'success' => false,
                    'message' => 'Password reset failed',
            ])->setStatusCode(500);
        }
    }

    public function identity(IdentityCardRequest $request)
    {
        $rider = JWTAuthuser::user();
        $data = $request->all();
        if ($request->hasfile('identity_card')) {
            $card = $request->file('identity_card');
            $filename = time() . '.' . $card->getClientOriginalExtension();
            $data['identity_card'] = $filename;
            File::delete(public_path() . '/uploads/riders/id_card/', $rider->profile_pic);
            Image::make($card)->resize(300, 300)->save( public_path('/uploads/riders/id_card/' . $filename) );
            //$data['avatar'] = Image::make(storage_path('public/uploads/avatars/' . $avatar))->resize(300, 300)->response();
            $update= $this->riderService->update($rider, $data);
            if($update){
                return $this->response->array([
                    'success' => true,
                    'message' => 'Rider Identity Updated successfully',
                    'data' => [
                        'user' => array(
                                'firstname' =>$rider->firstname,
                                'lastname' =>$rider->lastname,
                                'displayname' => $rider->username,
                                'phone' =>$rider->phone,
                                'avatar' => (!is_null($rider->profile_pic))? env('APP_URL').'/uploads/riders/id_card/'.$rider->profile_pic : $rider->profile_pic,
                                'plat_no' => $rider->vehicle_reg_numb,
                                'spatch_type' => $rider->vehicle_type,
                                'verified' => ($rider->verified)? true : false,
                                'activation' => [
                                    'status' => ($rider->active)? true : false,
                                ], 
                            ),
                    ]
                ], 200);
            }else{
                return $this->response->array([
                    'message' => 'Rider Identity Update Failed',
                ], 500);
            }
        }else{
            return $this->response->array([
                'message' => 'Identity card not passed',
            ])->setStatusCode(500);
        }
    }

    public function logGPS(LogGpsRequest $request)
    {
        $data = $request->all();
        $rider = JWTAuthuser::user();
        if($rider->find($rider->id)){
            $rider->current_lng = $data['longitude'];
            $rider->current_lat = $data['latitude'];
            if($rider->save()){
                return $this->response->array([
                    'message' => 'Rider location logged successfully',
                ], 200);
            }else{
                return $this->response->array([
                    'message' => 'Rider location could not be updated',
                ])->setStatusCode(500);
            }
        }else{
            return $this->response->array([
                'message' => 'Failed to log rider location',
            ])->setStatusCode(500);
        }
    }

    public function delete(DeleteRiderRegistrationRequest $request)
    {
        $rider = $this->riderService->findByPhonenumber($request->phone);
        if($rider){
            $delete = $this->riderService->delete($rider);
            $message = "account deleted successfully";
            $code = 200;
        }else{
            $message = "failed to delete";
            $code = 404;
        }
        return $this->response->array([
                    'message' => $message,
                ], $code);
    }

    public function getRider(Request $request)
    {
        return $this->response->item($request->user(), new RiderTransformer());
    }

    public function login(RiderLoginRequest $request)
    {
        $credentials = [
            'phone' => $request->phone,
            'password' => $request->password
        ];

        try {
            // attempt to verify the credentials and create a token for the user
            \Config::set('jwt.user', 'App\Models\Riders'); 
            \Config::set('auth.providers.users.model', \App\Models\Riders::class);
            $token = JWTAuthuser::attempt($credentials);
            if (!$token) {
                return $this->response->error('Invalid login credentials provided.', 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return $this->response->error('Unable to create token.', 500);
        }

        $rider = JWTAuthuser::user();
        if ((!$rider->verified)) {
            return $this->response->error('You have to verify your account before you can proceed.', 401);
        }else{
            return $this->response->array([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'token_type'   => 'bearer',
                        'rider' => array(
                            'id' => $rider->id,
                            'firstname' =>$rider->firstname,
                            'lastname' =>$rider->lastname,
                            'phone' =>$rider->phone,
                            'avatar' => $rider->profile_pic,
                            'plat_no' => $rider->vehicle_reg_numb,
                            'spatch_type' => $rider->vehicle_type,
                            'verified' => ($rider->verified)? true : false,
                            'activation' => [
                                'status' => ($rider->active)? true : false,
                            ]
                        ),
                    ]
                ], 200);
        }

    }

    public function logout()
    {
        $token = JWTAuthuser::parseToken();
        JWTAuthuser::invalidate($token);
        return $this->response->array([
            'status' => true,
            'message' => 'Logout successfully.',
        ], 200);
    }
    
}
