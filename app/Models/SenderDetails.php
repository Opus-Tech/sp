<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SenderDetails extends Model
{
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
