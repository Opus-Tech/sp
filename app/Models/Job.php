<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sender()
    {
        return $this->hasOne(SenderDetails::class);
    }

    public function receiver()
    {
        return $this->hasOne(ReceiverDetails::class);
    }

    public function pairedrider()
    {
        return $this->hasOne(PairRiders::class);
    }

    public function rated()
    {
        return $this->forceFill([
            'rating_status' => true,
        ])->save();
    }

    public function hasRated()
    {
        return $this->rating_status;
    }
}
