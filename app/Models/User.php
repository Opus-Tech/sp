<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Twilio\Rest\Client;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'phone',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'account_verified_at' => 'datetime',
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
            'account_verified_at' => date("Y-m-d g:i:s"),
        ])->save();
    }

     public function callToVerify()
    {
        $code = random_int(100000, 999999);

        return $this->forceFill([
            'verification_code' => $code
        ])->save();
    }

    public function callEmailVerification()
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

    public function sendEmailToVerify()
    {
        $client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $client->verify->v2->services(env('TWILIO_VERIFY_SID'))
            ->verifications
            ->create($this->email, "email");
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
