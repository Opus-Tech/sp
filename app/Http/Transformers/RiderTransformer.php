<?php

namespace App\Http\Transformers;

use App\Models\Riders;
use App\Models\PairRiders;
use App\Models\Job;
use League\Fractal\TransformerAbstract;
use Carbon\Carbon;

class RiderTransformer extends TransformerAbstract
{

    public function transform(Riders $rider)
    {
        $now = Carbon::now();
        $todays_jobs = Job::join('pair_riders', 'jobs.id', 'pair_riders.job_id')
                    ->join('riders', 'riders.id', 'pair_riders.rider_id')
                    ->where(['pair_riders.rider_id' => $rider->id, 'jobs.status' => 'completed', 'jobs.created_at' => Carbon::today()])
                    ->get();
        $todays_jobs_sum = $todays_jobs->sum('jobs.price');
        $current_month_jobs = Job::join('pair_riders', 'jobs.id', 'pair_riders.job_id')
                    ->join('riders', 'riders.id', 'pair_riders.rider_id')
                    ->where(['pair_riders.rider_id' => $rider->id, 'jobs.status' => 'completed', 'jobs.created_at' => $now->month])
                    ->get();
        $current_month_jobs_sum = $current_month_jobs->sum('jobs.price');
        return [
            'firstname' =>$rider->firstname,
            'lastname' =>$rider->lastname,
            'displayname' => $rider->username,
            'phone' =>$rider->phone,
            'avatar' => (!is_null($rider->profile_pic))? env('APP_URL').'/uploads/riders/avatars/'.$rider->profile_pic : $rider->profile_pic,
            'plat_no' => $rider->vehicle_reg_numb,
            'spatch_type' => $rider->vehicle_type,
            'rating' => $rider->averageRating,
            'verified' => ($rider->verified)? true : false,
            'activation' => [
                'status' => ($rider->active)? true : false,
            ], 
            'earnings' => [
                'today' => number_format($todays_jobs_sum - ($todays_jobs_sum * 20/100)),
                'current_month' => number_format($current_month_jobs_sum - ($current_month_jobs_sum * 20/100)),
            ],
            'job_details' => [
                'statistics' => [
                    'today' => [
                        'pending' => 0,
                        'active' => 0,
                        'completed' => 0,
                    ],
                    'total' => [
                        'pending' => 0,
                        'active' => 0,
                        'completed' => 0,
                    ],
                ], 
            ] 
        ];
    }
}
