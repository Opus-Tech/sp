<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Password extends Model
{
    protected $table = 'password_resets';

    public function generateOtp()
    {
        $code = random_int(100000, 999999);
        return $this->forceFill([
            'otp' => $code
        ])->save();
    }

    public function scopeOtp($query, $otp)
    {
        return $query->where('otp', $otp);
    }
}
