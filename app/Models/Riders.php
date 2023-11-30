<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Twilio\Rest\Client;
use willvincent\Rateable\Rateable;

class Riders extends Authenticatable implements JWTSubject
{
    use Notifiable;

    use Rateable;

    protected $guard = 'rider';

    protected $fillable = [
        'firstname', 'lastname', 'username', 'phone', 'vehicle_reg_numb', 'identity_card', 'profile_pic', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'user' => [
                'id' => $this->id
            ]
        ];
    }

        /**
     * Check if user account is Verified
     * 
     * @var boolean
     */
    public function hasVerifiedAccount()
    {
        return $this->verified;
    }

    /**
     * Mark a successfully verified account as verified
     * 
     * @var boolean
     */
    public function markAccountAsVerified()
    {
        return $this->forceFill([
            'verified' => 1,
        ])->save();
    }

     public function callToVerify()
    {
        $code = random_int(100000, 999999);

        return $this->forceFill([
            'verification_code' => $code
        ])->save();
    }

    public function sendSMSToVerify()
    {
        $client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $client->verify->v2->services(env('TWILIO_VERIFY_SID'))
            ->verifications
            ->create($this->phone, "sms");
    }

    public function checkSMSVerify($code)
    {
        $client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        return $client->verify->v2->services(env('TWILIO_VERIFY_SID'))
                ->verificationChecks
                ->create($code, array('to' => $this->phone));
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function riderpairto()
    {
        return $this->hasMany(PairRiders::class);
    }
}
