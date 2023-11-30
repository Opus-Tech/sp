<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\JobRequest;

use App\Http\Requests\JobReceiverRequest;

use App\Http\Requests\DistanceCostRequest;

use App\Http\Requests\JobPaymentRequest;

use App\Http\Requests\RatingRiderRequest;

use App\Http\Requests\CancelJobRequest;

use App\Models\Wallet;

use App\Models\Transaction;

use App\Models\SystemLog;

use App\Services\UserService;

use App\Services\JobService;

use App\Services\RiderService;

use App\Http\Transformers\UserJobTransformer;

use App\Http\Transformers\JobTransformer;

use Tymon\JWTAuth\JWTAuth;
    
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Validation\ValidationException;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; 

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException; 

use Illuminate\Support\Facades\Http;

class JobController extends BaseController
{
    private $userService;
    private $authService;
    private $jobService;
    private $riderService;

    public function __construct(UserService $userService, JobService $jobService, RiderService $riderService, JWTAuth $auth)
    {
        $this->userService = $userService;
        $this->jobService = $jobService;
        $this->riderService = $riderService;
        $this->authService = $auth;
    }

        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    } 

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(JobRequest $request)
    {
        try {
            $data = $request->all();
            $data['user_id'] = $this->authService->user()->id;
            // Create Job
            $job = $this->jobService->create($data);
            if($job){
                $rider = $this->riderService->getAvailableRider($data['pickup_longitude'], $data['pickup_latitude']);
                if($rider){
                    if($this->riderService->pairRider($data['user_id'], $rider['rider']->id, $job->id)){
                        $this->jobService->logTransaction('user_app', 'pair_rider', 'user successfully paired with a rider', $data['user_id'], $rider['rider']->id, $job->id);
                    }
                }
                if($data['is_requester'] === 'sender'){
                    if($user = $this->userService->find($data['user_id'])){
                        $requester = $this->jobService->useUserAsSender($user, $job->id);

                        $receiver = $this->newUserJobReceiver($data, $job->id);
                    }else{
                        return $this->response->error('Job creation failed, requesting user not found.', 403);
                    } 
                }elseif ($data['is_requester'] === 'receiver') {
                    if($user = $this->userService->find($data['user_id'])){
                        $requester = $this->newUserJobSender($data, $job->id);

                        $receiver = $this->jobService->useUserAsReceiver($user, $job->id);
                    }else{
                        return $this->response->error('Job creation failed, sending user not found.', 403);
                    } 
                }else{

                }
                return $this->response->array([
                                    'message' => 'New job created succefully.',
                                    'data' => [
                                        'job_details' => [
                                            'id' => $job->id,
                                            'pickup_address' => $job->pickup,
                                            'dropoff_address' => $job->dropoff,
                                            'item_name' => $job->item,
                                            'total_distance' => $job->distance,
                                            'cost' => $job->price,
                                            'payment_method' => ($job->payment_method)? $job->payment_method : 'card',
                                            'eta' => $rider['eta'],
                                            'distance' => $rider['distance'],
                                            'date_created' => $job->created_at->format('Y-m-d'),
                                            'time_created' => $job->created_at->format('h:i:s A'),
                                            'receiver_details' => [
                                                'name' => $receiver->fullname,
                                                'email' => $receiver->email,
                                                'phonenumber' => $receiver->phone,
                                                'address' => $job->dropoff,
                                            ],
                                            'sender_details' => [
                                                'name' => $requester->fullname,
                                                'email' => $requester->email,
                                                'phonenumber' => $requester->phone,
                                                'address' => $job->pickup,
                                            ],
                                            'rider_details' => [
                                                'first_name' => $rider['rider']->firstname,
                                                'last_name' => $rider['rider']->lastname,
                                                'username' => $rider['rider']->username,
                                                'phonenumber' => $rider['rider']->phone,
                                                'avatar' => $rider['rider']->profile_pic,
                                                'plate_no' => $rider['rider']->vehicle_reg_numb,
                                                'dispatch_type' => $rider['rider']->vehicle_type,
                                                'rating' => null,
                                            ]
                                        ],
                                    ]
                                ], 200);
            }else{
                return $this->response->error('Job creation failed.', 403);
            }
            
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $jobs = $this->jobService->find($id);
            return $this->response->item($jobs, new JobTransformer)->setStatusCode(200);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }  
    }
    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
    }

    public function payment(JobPaymentRequest $request)
    {
        try {
            $data = $request->all();
            $user = $this->authService->user();
            $pay = $this->jobService->pay($user, $data);
            if(!$pay){
                return $this->response->array([
                    'status' => false,
                    'message' => 'Job payment failed, payment verification failed.',
                ])->setStatusCode(403);
            }
            $job = $this->jobService->find($data['job_id']);
            $message = array(
                'job_id' => $job->id,
                'rider_id' => $job->pairedrider->rider->id,
                'type' => 'rider',
            );
            $notification_response = Http::withHeaders([
                'Authorization' => 'key=AAAA8I1btUQ:APA91bH3JFTxmYNHKqyvUB4oAhbnRRo8QoyCuvOVkh3OCchd4FpGoS8Jpi694wXS9GDJO-OSkm2PNoqN886NlCpFWnctrl6iNIBPWyzUz8DHJ9h6hMNRTxBQJxFf_3L3zOhz-Y8x2lgl',
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => "/topics/".$job->pairedrider->rider->id,
                'notification' => array('title' => 'New Job', 'body' => 'You have a new job assigned to you.', 'sound' => 'default', "click_action" => "FLUTTER_NOTIFICATION_CLICK"),
                'data' => array('message' => $message),
            ]);
            if($notification_response->ok()){
                $fcm_response = $notification_response->json();
            }else{
                $fcm_response = $notification_response->status();
            }

            return $this->response->array([
                'status' => true,
                'message' => 'Job payment made successfully.',
                'data' => [
                    'job_details' => [
                        'id' => $job->id,
                        'pickup_addresss' => $job->pickup,
                        'dropoff_address' => $job->dropoff,
                        'item' => $job->item,
                        'status' => $job->status,
                        'total_distance' => $job->distance,
                        'total_cost' => $job->price,
                        'payment_method' => ($job->payment_method)? $job->payment_method : 'card',
                        'payment_status' => ($job->payment_status == true) ? 'paid' : 'un-paid',
                    ],
                    'fcm_response' => [
                        'data' => $fcm_response,
                    ]
                ]
            ], 200);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
    }

    public function getUserJobs()
    {
        try {
            $user = $this->authService->user();
            return $this->response->item($user, new UserJobTransformer)->setStatusCode(200);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
    }

    public function cancel(CancelJobRequest $request)
    {
        try {
            $data = $request->all();
            $user = $this->authService->user();
            $job = $this->jobService->find($data['job_id']);
            if($job->status = 'pending'){
                if($this->jobService->cancel($job)){
                    if($job->payment_status == true){
                        $wallet = $user->wallet;
                        if(!$wallet){
                            $wallet = new Wallet();
                            $wallet->user_id = $user->id;
                            $wallet->balance = $job->price;
                        }else{
                            $wallet->balance = $wallet->balance + ($job->price);
                        }   
                        if($wallet->save()){
                            $log = new SystemLog();
                            $log->category = 'wallet';
                            $log->log_type = 'credit';
                            $log->message = "{$user->firstname} cancel job payment reversed to wallet";
                            $log->user_id = $user->id;
                            $log->save();
                            $transaction = new Transaction();
                            $transaction->source = 'wallet';
                            $transaction->type = 'credit';
                            $transaction->amount = $job->price;
                            $transaction->user_id = $user->id;
                            $transaction->reference = 'SPTH-JOB-CANCEL'.$job->id;
                            $transaction->status = 'success';
                            $transaction->save();
                        }
                    }
                    return $this->response->array([
                                'status' => true,
                                'message' => 'Job cancelled successfully.',
                            ], 200);
                }else{
                    return $this->response->array([
                        'status' => false, 
                        'message' => 'Job cancelling failed'
                        ])->setStatusCode(403);
                }
            }else{
                return $this->response->array([
                        'status' => false, 
                        'message' => 'Job has been previously cancelled'
                        ])->setStatusCode(403);
            }
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
    }

    public function newUserJobReceiver($data, $job_id)
    {
        try {
            if(!array_key_exists("receiver_full_name", $data)){
                throw ValidationException::withMessages([
            		"receiver_full_name" => ['The receiver fullname field is required.'],
            	]);
            }
            return $this->jobService->newUserAsReceiver($data, $job_id);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
    }

    public function newUserJobSender($data, $job_id)
    {
        try {
            if(!array_key_exists("sender_full_name", $data)){
                throw ValidationException::withMessages([
            		"sender_full_name" => ["The sender's fullname field is required."],
            	]);
            }

            if (!array_key_exists("sender_phone", $data)) {
                throw ValidationException::withMessages([
            		"sender_phone" => ["The sender's phone field is required."],
            	]);
            }
            return $this->jobService->newUserAsSender($data, $job_id);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
    }

    public function getTotalDistanceCost(DistanceCostRequest $request)
    {
        try {
            $data = $request->all();
            $cost = $this->jobService->routeCost($data);
            return $this->response->array([
                                    'success' => true,
                                    'data' => [
                                        'distance' => $data['distance'],
                                        'price' => number_format((float)$cost, 2, '.', ''),
                                        'bonus_price' => number_format((float)($cost - ((25/100) * $cost)), 2, '.', ''),
                                        'bonus_percentage' => 25,
                                        'spatch_option' => ($data['delivery_mode'] == 'today')? 'same day' : (($data['delivery_mode'] == 'express')? 'express' : (($data['delivery_mode'] == 'dedicated')? 'dedicated' : 'interstate')),
                                    ]
                                ], 200);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
    }

    public function rateRider(RatingRiderRequest $request)
    {
        try {
            $data = $request->all();
            $job = $this->jobService->find($data['job_id']);
            if($job->rating_status){
                return $this->response->array([
                    'status' => true,
                    'message' => 'Job has been rated previously',
                ]);
            }
            $rider = $this->riderService->find($job->pairedrider->rider_id);
            $rate = $rider->rate($data['rating']);
            $job->rating_status = true;
            if ($job->save()) {
                return $this->response->array([
                    'status' => true,
                    'message' => 'rating done successfully',
                    'user_rating' => $data['rating'],
                    'rider_ratings' => $rider->ratings,
                ], 200);
            }else{
                return $this->response->array([
                    'status' => true,
                    'message' => 'Job has been rated previously',
                ]);
            }
            
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
    }
    
}
