<?php

namespace App\Services;

use App\Models\User;
use App\Models\Password;
use Illuminate\Support\Facades\Hash;
use App\Services\PartialService;

class UserService
{
    /**
     * Get the user by ID
     * 
     * @var User
     */
    public function find($id) : User
    {
        return User::findOrFail($id);
    }

    /**
     * Create a new user account
     * 
     * @var boolean
     */
    public function create(array $data) : User
    {
        $user = new User();

        return $this->update($user, $data);
    } 

    /**
     * Update a User profile
     * 
     * @var boolean
     */
    public function update(User $user, array $data) : User
    { 
        $user->firstname = array_key_exists("firstname", $data )? $data['firstname'] : $user->firstname;
        $user->lastname = array_key_exists("lastname", $data )? $data['lastname'] : $user->lastname;
        $user->email = array_key_exists("email",$data)?$data['email']: $user->email;
        $user->phone = array_key_exists("phone", $data )? $data['phone'] : $user->phone;
        $user->profile_pic = array_key_exists("avatar", $data )? $data['avatar'] : $user->profile_pic;
        if(isset($data['password'])) { 
            $user->password = bcrypt($data['password']);
        }

        $user->save();

        return $user;
    }

    /**
     * Delete a user account
     * 
     * @var boolean
     */
    public function delete(User $user)
    {
        return $user->delete();
    } 

    /**
     * Get User object by phone
     * 
     * @var User
     */
     public function findByPhonenumber(String $phonenumber)
     {
        $user = User::where('phone', $phonenumber)->first();

        return $user;
     }

     public function findByEmail(string $email) : User
     {
         $user = User::where('email', $email)->first();
         return $user;
     }

     public function sendOtpCode(User $user, $otp)
     {
        $password = new Password();
        $password->otp = $otp;
        $password->phone = $user->phone;
        $password->token = $user->password;
        $password->save();
        return $password;
     }

     public function resetPassword(User $user, array $data)
     {
        $checkotp = $this->checkOtpCode($data['code']);
        if($checkotp){
            $user->password = Hash::make($data['password']);
            return $user->save();
        }else{
            return false;
        }
     }

     public function checkOtpCode($otp)
     {
         $password = Password::where('otp', $otp)->first();
         return $password;
     }
    
}