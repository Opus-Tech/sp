<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PairRiders extends Model
{
    public function rider()
    {
        return $this->belongsTo(Riders::class);
    }
}
