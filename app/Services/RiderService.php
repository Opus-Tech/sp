<?php

namespace App\Services;

use App\Models\Riders;

use App\Models\PairRiders;

use App\Models\Password;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Hash;

class RiderService
{
    /**
     * Get the rider by ID
     * 
     * @var Riders
     */
    public function find($id) : Riders
    {
        return Riders::findOrFail($id);
    }

    /**
     * Create a new Riders rider
     * 
     * @var boolean
     */
    public function create(array $data) : Riders
    {
        $rider = new Riders();

        return $this->update($rider, $data);
    } 

    /**
     * Update a Riders rider
     * 
     * @var boolean
     */
    public function update(Riders $rider, array $data) : Riders
    { 
        $rider->firstname = array_key_exists("firstname", $data )? $data['firstname'] : $rider->firstname;
        $rider->lastname = array_key_exists("lastname", $data )? $data['lastname'] : $rider->lastname;
        $rider->username = array_key_exists("username", $data )? $data['username'] : $rider->username;
        $rider->phone = array_key_exists("phone", $data )? $data['phone'] : $rider->phone;
        $rider->profile_pic = array_key_exists("avatar", $data )? $data['avatar'] : $rider->profile_pic;
        $rider->identity_card = array_key_exists("identity_card", $data)? $data['identity_card'] : $rider->identity_card;
        $rider->vehicle_reg_numb = array_key_exists("bike_plate_no", $data )? $data['bike_plate_no'] : $rider->vehicle_reg_numb;
        if(isset($data['password'])) { 
            $rider->password = bcrypt($data['password']);
        }

        $rider->save();

        return $rider;
    }

    /**
     * Forgotten password otpCode
     **/

     public function sendOtpCode(Riders $rider, $otp)
     {
        $password = new Password();
        $password->otp = $otp;
        $password->phone = $rider->phone;
        $password->token = $rider->password;
        $password->save();
        return $password;
     }

     public function resetPassword(Riders $rider, array $data)
     {
        $checkotp = $this->checkOtpCode($data['code']);
        if($checkotp){
            $rider->password = Hash::make($data['password']);
            return $rider->save();
        }else{
            return false;
        }
     }

     public function checkOtpCode($otp)
     {
         $password = Password::where('otp', $otp)->first();
         return $password;
     }

    /**
     * Delete a Riders account
     * 
     * @var boolean
     */
    public function delete(Riders $rider)
    {
        return $rider->delete();
    } 

    /**
     * Get User object by phone
     * 
     * @var User
     */
     public function findByPhonenumber(String $phonenumber) : Riders
     {
        $rider = Riders::where('phone', $phonenumber)->first();

        return $rider;
     }

    public function getAvailableRider($longitude, $latitude)
    {
        $riders = Riders::status('available')->get();
        $max_km = 400;
        $compact_result = array();
        foreach ($riders as $rider){
            $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$rider->current_lat},{$rider->current_lng}&destination={$latitude},{$longitude}&sensor=false&key=AIzaSyBdOzv24d4MHqRpI1IXhPM_H84B74Zv0jM";
            $googleapis = Http::get($url);
            if($googleapis->ok()){
                $response = $googleapis->json();
                $meters = $response['routes'][0]['legs'][0]['distance']['value'];
                $time = $response['routes'][0]['legs'][0]['duration']['value'];
                $distance = $meters/1000;
                $eta = $time/60;
                if ($distance <= $max_km) {
                    $max_km  = $distance;
                    $compact_result['distance'] = $distance;
                    $compact_result['eta'] = $eta;
                    $compact_result['rider'] = $rider;
                }else{
                    $max_km = $max_km;
                    $compact_result['distance'] = $distance;
                    $compact_result['eta'] = $eta;
                    $compact_result['rider'] = $rider;
                }
            }
        }
        return $compact_result;
    }

    public function pairRider($user_id, $rider_id, $job_id) {
        $checkpairing = PairRiders::whereJobId($job_id)->first();
        if ($checkpairing){
            $checkpairing->rider_id = $rider_id;
            return $checkpairing->save();
        }else {
            $pair_rider = new PairRiders();
            $pair_rider->user_id = $user_id;
            $pair_rider->rider_id = $rider_id;
            $pair_rider->job_id = $job_id;
            return $pair_rider->save();
        }
    }
    
}