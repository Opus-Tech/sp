<?php

namespace App\Http\Controllers\Auth;

use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuthuser;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use App\Services\UserService;
use App\Services\PartialService;
use App\Http\Controllers\Controller;
use App\Http\Transformers\UserTransformer;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\UserRegistrationRequest;
use App\Http\Requests\UserRegistrationEmailRequest;
use App\Http\Requests\DeleteUserRegistrationRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ResendEmailVerificationRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Mail\UserSignup;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use Image;
use File;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Twilio\TwiML\VoiceResponse;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use Helpers;
    private $userService;
    private $authService;
    private $partialService;

    public function __construct(UserService $userService, PartialService $partialService, JWTAuth $auth)
    {
        $this->userService = $userService;
        $this->authService = $auth;
        $this->partialService = $partialService;
    }

    public function register(UserRegistrationRequest $request)
    {  

        $user = $this->userService->create($request->all());
        if(!is_null($user->email)) {
            $user->sendEmailToVerify();
            $address = $user->email;
            $message = 'Please confirm yourself with the verification code sent to '.$address;
        }elseif(!is_null($user->phone)) {
            $user->sendSMSToVerify();
            $address = $user->phone;
            $message = 'Please confirm your phone number with the verification code sent to '.$address;
        }
        return $this->response->array([
                    'message' => $message,
                    'userid' => $user->id,
                    'verification_destination' => $address,
                ], 200);
    }

    public function register_by_email(UserRegistrationEmailRequest $request)
    {
        $user = $this->userService->create($request->all());
        $send = $user->callEmailVerification();
        if($send){
            $user = $this->userService->findByEmail($request->email);
            Mail::to($request->email)->send(new UserSignup($user));
            return $this->response->array([
                'message' => 'Please confirm yourself with the verification code sent to: '.$user->email,
                'userid' => $user->id,
            ], 200);
        }else{
            return $this->response->array([
                'message' => 'User Account Creation Failed',
            ])->setStatusCode(500);
        }
        
    }

    public function resend_email_verification(ResendEmailVerificationRequest $request){
        $user = $this->userService->findByEmail($request->email);
        $send = $user->callEmailVerification();
        if($send){
            $user = $this->userService->findByEmail($user->email);
            Mail::to($request->email)->send(new UserSignup($user));
            return $this->response->array([
                'message' => 'Please confirm yourself with the verification code sent to: '.$user->email,
                'userid' => $user->id,
            ], 200);
        }else{
            return $this->response->array([
                'message' => 'User Email does not exist',
            ])->setStatusCode(500);
        }
    }

    public function update(UpdateUserRequest $request)
    {
        $user = JWTAuthuser::user();
        $update= $this->userService->update($user, $request->all());
        if($update){
            return $this->response->array([
                'success' => true,
                'message' => 'User Account Updated successfully',
                'data' => [
                    'user' => array(
                            'id' => $user->id,
                            'firstname' =>$user->firstname,
                            'lastname' =>$user->lastname,
                            'email' => $user->email,
                            'phone' =>$user->phone,
                            'avatar' => $user->profile_pic,
                            'verified' => ($user->verified)? true : false,
                        ),
                ]
            ], 200);
        }else{
            return $this->response->array([
                'message' => 'User Account Update Failed',
            ], 500);
        }
        
    }

    public function profileupdate(UpdateProfileRequest $request)
    {
        $user = JWTAuthuser::user();
        $data = $request->all();
        if ($request->hasfile('avatar')) {
            $avatar = $request->file('avatar');
            $filename = time() . '.' . $avatar->getClientOriginalExtension();
            $data['avatar'] = $filename;
            File::delete(public_path() . '/uploads/avatars/', $user->profile_pic);
            Image::make($avatar)->resize(300, 300)->save( public_path('/uploads/avatars/' . $filename) );
            //$data['avatar'] = Image::make(storage_path('public/uploads/avatars/' . $avatar))->resize(300, 300)->response();
        }
        $update= $this->userService->update($user, $data);
        if($update){
            return $this->response->array([
                'success' => true,
                'message' => 'User Account Updated successfully',
                'data' => [
                    'user' => array(
                            'id' => $user->id,
                            'firstname' =>$user->firstname,
                            'lastname' =>$user->lastname,
                            'email' => $user->email,
                            'phone' =>$user->phone,
                            'avatar' => (!is_null($user->profile_pic))? env('APP_URL').'/uploads/avatars/'.$user->profile_pic : null,
                            'verified' => ($user->verified)? true : false,
                        ),
                ]
            ], 200);
        }else{
            return $this->response->array([
                'message' => 'User Account Update Failed',
            ])->setStatusCode(500);
        }
    }

    public function delete(DeleteUserRegistrationRequest $request)
    {
        $user = $this->userService->findByPhonenumber($request->phone);
        if($user){
            $delete = $this->userService->delete($user);
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

    public function getUser(Request $request)
    {
        return $this->response->item($request->user(), new UserTransformer());
    }

    public function userLogin(UserLoginRequest $request)
    {
        if(isset($request->phone)){
            $credentials = [
                'phone' => $request->phone,
                'password' => $request->password
            ];
        }else{
            $credentials = [
                'email' => $request->email,
                'password' => $request->password
            ];
        }

        try {
            // attempt to verify the credentials and create a token for the user
            $token = $this->authService->attempt($credentials);
            if (!$token) {
                return $this->response->error('Invalid login credentials provided.', 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return $this->response->error('Unable to create token.', 500);
        }

        if ($this->authService->user()->account_verified_at == NULL && (!$this->authService->user()->verified)) {
            return $this->response->error('You have to verify your account before you can proceed.', 401);
        }else{
            $user = $this->authService->user();
            return $this->response->array([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'token_type'   => 'bearer',
                        'user' => array(
                            'id' => $user->id,
                            'firstname' =>$user->firstname,
                            'lastname' =>$user->lastname,
                            'email' => $user->email,
                            'phone' =>$user->phone,
                            'avatar' => (!is_null($user->profile_pic))? env('APP_URL').'/uploads/avatars/'.$user->profile_pic : $user->profile_pic,
                            'verified' => ($user->verified)? true : false,
                        ),
                    ]
                ], 200);
        }

    }

    public function newPassword(ResetPasswordRequest $request){
        $data = $request->all();
        $user = $this->userService->findByPhonenumber($data['phone']);
        if (!$user) {
            return $this->response->array([
                    'success' => false,
                    'message' => 'Invalid phonenumber supplied.',
            ])->setStatusCode(500);
        }
        $data = $request->all();
        $change = $this->userService->resetPassword($user, $data);
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
        $user = $this->userService->findByPhonenumber($data['phone']);
        if (!$user) {
            return $this->response->array([
                    'success' => false,
                    'message' => 'Invalid phonenumber supplied.',
            ])->setStatusCode(500);
        }

        $code = random_int(100000, 999999);
        $send = $this->userService->sendOtpCode($user, $code);
        if ($send) {
            $client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $client->messages->create(
                // Where to send a text message (your cell phone?)
                $user->phone,
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

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = JWTAuthuser::user();
        $updatepassword = $this->userService->find($user->id);
        if($updatepassword->update(['password'=> Hash::make($request->new_password)])){
            return $this->response->array([
                    'status' => true,
                    'message' => 'Password successfully changed.',
                ], 200);
        }else{
            return $this->response->error('Password could not be changed at the moment.', 403);
        }
    }

    public function logout()
    {
        $token = $this->authService->parseToken();
        $token->invalidate();
        return $this->response->array([
            'status' => true,
            'message' => 'Logout successfully.',
        ], 200);
    }
    
}
