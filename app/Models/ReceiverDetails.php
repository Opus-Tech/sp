<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiverDetails extends Model
{
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
