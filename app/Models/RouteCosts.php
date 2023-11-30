<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteCosts extends Model
{
    public function scopePrice($query, $distance)
    {
        return $query->where('min_km', '<=', $distance)
                    ->where('max_km', '>=', $distance);
    }
}
