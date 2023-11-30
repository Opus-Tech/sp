<?php

namespace App\Http\Controllers\Rider;

use Illuminate\Http\Request;

use App\Http\Resources\Job as JobResource;

use App\Http\Requests\JobRequest;

use App\Http\Requests\JobReceiverRequest;

use App\Http\Requests\DistanceCostRequest;

use App\Http\Requests\JobPaymentRequest;

use App\Http\Requests\UpdateJobStatusRequest;

use App\Services\UserService;

use App\Services\JobService;

use App\Services\RiderService;

use App\Models\Job;

use App\Http\Transformers\UserJobTransformer;

use App\Http\Transformers\JobTransformer;

use Tymon\JWTAuth\JWTAuth;

use Tymon\JWTAuth\Facades\JWTAuth as JWTAuthuser;

use Dingo\Api\Routing\Helpers;
    
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Validation\ValidationException;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; 

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException; 

use Illuminate\Support\Facades\Http;

class JobController
{
    private $jobService;

    private $riderService;

    use Helpers;

    public function __construct(JobService $jobService, RiderService $riderService)
    {
        $this->jobService = $jobService;
        $this->riderService = $riderService;
    }

        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $rider = JWTAuthuser::user();
            $jobs = Job::join('pair_riders', 'pair_riders.job_id', 'jobs.id')
                        ->join('riders', 'pair_riders.rider_id', 'riders.id')
                        ->join('sender_details', 'jobs.id', 'sender_details.job_id')
                        ->join('receiver_details', 'jobs.id', 'receiver_details.job_id')
                        ->where(['riders.id' => $rider->id, 'jobs.payment_status' => 1, ['jobs.status', '!=', 'cancelled'], ['jobs.status', '!=', 'completed']])
                        ->select('jobs.*', 'sender_details.fullname as sender_name', 'sender_details.email as sender_email', 'sender_details.phone as sender_phone', 'receiver_details.fullname as receiver_name', 'receiver_details.email as receiver_email', 'receiver_details.phone as receiver_phone')
                        ->get();
            return $this->response->array([
                    'data' => [
                        'jobs' => JobResource::collection($jobs),
                        'total' => count($jobs)
                    ],
                ], 200);
            
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }

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
            $rider = JWTAuthuser::user();
            $job = Job::findOrFail($id);
            return $this->response->array([
                    'data' => [
                        'job_details' => new JobResource($job),
                    ],
                ], 200);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }  
    }

    public function control(UpdateJobStatusRequest $request)
    {
        try {
            $data = $request->all();
            $job = $this->jobService->find($data['job_id']);
            if ($job) {
                if($job = $this->jobService->update($job, $data)){
                    if ($job->status === 'inprogress') {
                        $notification = 'Hi '.ucwords($job->user->firstname).'! Your rider is en-route to pickup location';
                        $type = 'enroute';
                    }elseif($job->status === 'pickedup') {
                        $notification = 'Hi '.ucwords($job->user->firstname).'! Your Package is en-route dropoff location';
                        $type = 'pickup';
                    }elseif($job->status === 'completed') {
                        $notification = 'Hi '.ucwords($job->user->firstname).'! Your Package has been delivered successfully at your dropoff location';
                        $type = 'completed';
                    }
                    $message = array(
                        'job_id' => $job->id,
                        'user_id' => $job->user->id,
                        'type' => $type,

                    );
                    $notification_response = Http::withHeaders([
                        'Authorization' => 'key=AAAA8I1btUQ:APA91bH3JFTxmYNHKqyvUB4oAhbnRRo8QoyCuvOVkh3OCchd4FpGoS8Jpi694wXS9GDJO-OSkm2PNoqN886NlCpFWnctrl6iNIBPWyzUz8DHJ9h6hMNRTxBQJxFf_3L3zOhz-Y8x2lgl',
                        'Content-Type' => 'application/json'
                    ])->post('https://fcm.googleapis.com/fcm/send', [
                        'to' => "/topics/".$job->user->id,
                        'notification' => array('title' => 'Job status', 'body' => $notification, 'sound' => 'default', "click_action" => "FLUTTER_NOTIFICATION_CLICK"),
                        'data' => array('message' => $message),
                    ]);
                    if($notification_response->ok()){
                        $response = $notification_response->json();
                    }else{
                        $response = $notification_response->status();
                    }
                    return $this->response->array([
                        'success' => true,
                        'message' => 'Job status updated successfully.',
                        'job_details' => new JobResource($job),
                        'fcm_response' => [
                            'data' => $response,
                        ]
                    ], 200);
                }else{
                    return $this->response->array([
                        'success' => false,
                        'message' => 'job status update failed.',
                    ])->setStatusCode(500);
                }
            }else{
                return $this->response->array([
                    'success' => false,
                    'message' => 'access denied invalid job id supplied.',
                ])->setStatusCode(500);
            }
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

    public function completed()
    {
        try {
            $rider = JWTAuthuser::user();
            $jobs = Job::join('pair_riders', 'pair_riders.job_id', 'jobs.id')
                        ->join('riders', 'pair_riders.rider_id', 'riders.id')
                        ->join('sender_details', 'jobs.id', 'sender_details.job_id')
                        ->join('receiver_details', 'jobs.id', 'receiver_details.job_id')
                        ->where(['riders.id' => $rider->id, 'jobs.payment_status' => 1, 'jobs.status' => 'completed'])
                        ->select('jobs.*', 'sender_details.fullname as sender_name', 'sender_details.email as sender_email', 'sender_details.phone as sender_phone', 'receiver_details.fullname as receiver_name', 'receiver_details.email as receiver_email', 'receiver_details.phone as receiver_phone')
                        ->get();
            return $this->response->array([
                    'data' => [
                        'jobs' => JobResource::collection($jobs),
                        'total' => count($jobs)
                    ],
                ], 200);
            
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }

    } 

    public function cancelled()
    {
        try {
            $rider = JWTAuthuser::user();
            $jobs = Job::join('pair_riders', 'pair_riders.job_id', 'jobs.id')
                        ->join('riders', 'pair_riders.rider_id', 'riders.id')
                        ->join('sender_details', 'jobs.id', 'sender_details.job_id')
                        ->join('receiver_details', 'jobs.id', 'receiver_details.job_id')
                        ->where(['riders.id' => $rider->id, 'jobs.payment_status' => 1, 'jobs.status' => 'cancelled'])
                        ->select('jobs.*', 'sender_details.fullname as sender_name', 'sender_details.email as sender_email', 'sender_details.phone as sender_phone', 'receiver_details.fullname as receiver_name', 'receiver_details.email as receiver_email', 'receiver_details.phone as receiver_phone')
                        ->get();
            return $this->response->array([
                    'data' => [
                        'jobs' => JobResource::collection($jobs),
                        'total' => count($jobs)
                    ],
                ], 200);
            
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }

    }
    
}
