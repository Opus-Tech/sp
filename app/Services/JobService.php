<?php

namespace App\Services;

use App\Models\Job;

use App\Models\User;

use App\Models\SenderDetails;

use App\Models\ReceiverDetails;

use App\Models\SystemLog;

use App\Models\RouteCosts;

use App\Models\Transaction;

use App\Services\ExternalService;

use Dingo\Api\Routing\Helpers;

class JobService
{
    /**
     * Get the job by ID
     * 
     * @var Job
     */
    private $apiCall;
    private $authService;
    use Helpers;

    public function __construct(ExternalService $apiCall)
    {
        $this->apiCall = $apiCall;
    }

    public function find($id) : Job
    {
        return Job::findOrFail($id);
    }

    /**
     * Create a new job order
     * 
     * @var boolean
     */
    public function create(array $data) : Job
    {
        $order = new Job();

        return $this->update($order, $data);
    } 

    /**
     * Update a Job order
     * 
     * @var boolean
     */
    public function update(Job $order, array $data) : Job
    { 
        $order->user_id = array_key_exists("user_id",$data)? $data['user_id'] : $order->user_id;
        $order->delivery_note = array_key_exists("delivery_note", $data )? $data['delivery_note'] : $order->delivery_note;
        $order->pickup = array_key_exists("pickup", $data )? $data['pickup'] : $order->pickup;
        $order->dropoff = array_key_exists("dropoff", $data )? $data['dropoff'] : $order->dropoff;
        $order->pickup_lng = array_key_exists("pickup_longitude",$data)?$data['pickup_longitude']: $order->pickup_lng;
        $order->pickup_lat = array_key_exists("pickup_latitude", $data )? $data['pickup_latitude'] : $order->pickup_lat;
        $order->dropoff_lat = array_key_exists("dropoff_latitude", $data )? $data['dropoff_latitude'] : $order->dropoff_lat;
        $order->dropoff_lng = array_key_exists("dropoff_longitude", $data )? $data['dropoff_longitude'] : $order->dropoff_lng;
        $order->item = array_key_exists("item", $data )? $data['item'] : $order->item;
        $order->quantity = array_key_exists("quantity", $data )? $data['quantity'] : $order->quantity;
        $order->spatch_type = array_key_exists("vehicle_type", $data )? $data['vehicle_type'] : $order->spatch_type;
        $order->reciever_phone = array_key_exists("receiver_phone", $data )? $data['receiver_phone'] : $order->reciever_phone;
        $order->distance = array_key_exists("distance", $data)? $data['distance'] : $order->distance;
        $order->price = array_key_exists("price", $data)? $data['price'] : $order->price;
        $order->delivery_note = array_key_exists("note", $data)? $data['note'] : $order->delivery_note;
        $order->status = array_key_exists("status", $data)? $data['status'] : 'pending';
        $order->save();

        return $order;
    }

    /**
     * Delete a Job account
     * 
     * @var boolean
     */
    public function delete(Job $order)
    {
        return $order->delete();
    } 

    /**
     * Log the Reciever Details
     * 
     * @var boolean
     */
     public function useUserAsSender($user, $job_id) : SenderDetails
     {
        $sender = new SenderDetails();
        $sender->job_id = $job_id;
        $sender->fullname = $user->firstname.' '.$user->lastname;
        $sender->email = $user->email;
        $sender->phone = $user->phone;
        $sender->save();
        return $sender;
     }

     public function useUserAsReceiver($user, $job_id) : ReceiverDetails
     {
        $receiver = new ReceiverDetails();
        $receiver->job_id = $job_id;
        $receiver->fullname = $user->firstname.' '.$user->lastname;
        $receiver->email = $user->email;
        $receiver->phone = $user->phone;
        $receiver->save();
        return $receiver;
     }

     public function newUserAsReceiver(array $data, $job_id) : ReceiverDetails
     {
        $receiver = new ReceiverDetails();
        $receiver->job_id = $job_id;
        $receiver->fullname = array_key_exists("receiver_full_name",$data)? $data['receiver_full_name'] : $receiver->fullname;
        $receiver->email = array_key_exists("receiver_email",$data)? $data['receiver_email'] : $receiver->email;
        $receiver->phone = array_key_exists("receiver_phone", $data)? $data['receiver_phone'] : $receiver->phone;
        $receiver->save();
        return $receiver;
     }

     public function newUserAsSender(array $data, $job_id) : SenderDetails
     {
        $sender = new SenderDetails();
        $sender->job_id = $job_id;
        $sender->fullname = array_key_exists("sender_full_name",$data)? $data['sender_full_name'] : $sender->fullname;
        $sender->email = array_key_exists("sender_email",$data)? $data['sender_email'] : $sender->email;
        $sender->phone = array_key_exists("sender_phone", $data)? $data['sender_phone'] : $sender->phone;
        $sender->save();
        return $sender;
     }

     public function routeCost(array $data) : float
     {
        if ($data['delivery_mode'] === 'express') {
            $distance_rnd = number_format($data['distance'], 2);
            $cost_model = RouteCosts::price($data['distance'])->first();
            if($cost_model){
                $calculator = (($distance_rnd * $cost_model->fuel_cost) + $cost_model->rider_salary + ($distance_rnd * $cost_model->bike_fund)) * $cost_model->ops_fee * $cost_model->spatch_log * $cost_model->spatch_disp;
                $cost = (($calculator * 1.5) / 50) * 50;
                return $cost;
            }
        }elseif ($data['delivery_mode'] === 'dedicated') {
            $cost = 13333.3333;
            return $cost;
        }else{
            $distance_rnd = number_format($data['distance'], 2);
            $cost_model = RouteCosts::price($data['distance'])->first();
            if($cost_model){
                $calculator = (($distance_rnd * $cost_model->fuel_cost) + $cost_model->rider_salary + ($distance_rnd * $cost_model->bike_fund)) * $cost_model->ops_fee * $cost_model->spatch_log * $cost_model->spatch_disp;
                $cost = ceil($calculator / 50) * 50;
                return $cost;
            }
        }
        
     }

     public function pay(User $user, array $data)
     {
        $wallet = $user->wallet;
        $wallet_balance = $wallet->balance;
        $job = $this->find($data['job_id']);
        if ($wallet_balance > $job->price) {
            if($job->payment_status == false) {
                $wallet->balance = ($wallet_balance - $job->price);
                $wallet->save();
                if ($pay = $this->payFromBalance($user, $job->price, $data)){
                    $job->payment_method = 'wallet';
                    $job->payment_status = true;
                    return $job->save();
                }
            } else{
                return false;
            }
        }else{
            $pay = $this->makePayment($user, $data);
            if($pay){
                 return $pay;
            }else{
                return false;
            }
        }
     }

     public function payFromBalance(User $user, $amount, array $data){
        $transaction = new Transaction();
        $transaction->type = 'debit';
        $transaction->source = 'wallet';
        $transaction->amount = $amount;
        $transaction->user_id = $user->id;
        $transaction->reference = $data['reference'];
        $transaction->gateway_message = "User made payment using his wallet credit";
        $transaction->status = 'success';
        if ($transaction->save()) {
            return true;
        }else{
            return false;
        }
     }

     public function cancel(Job $order) : Job
     {
         $order->status = 'cancelled';
         if($order->save()) return $order;
     }

     public function payUsingCard(User $user, $amount, array $data) {
        $transaction = new Transaction();
        $transaction->source = 'card';
        $transaction->type = 'debit';
        $transaction->amount = $amount;
        $transaction->user_id = $user->id;
        $transaction->reference = $data['reference'];
        $transaction->gateway_message = "User made payment using card payment option";
        $transaction->status = 'success';
        if ($transaction->save()) {
            return true;
        }else{
            return false;
        }
     }

     public function makePayment(User $user, array $data){
         try {
            $payment = $this->apiCall->getPayRequest('https://api.paystack.co/transaction/verify/'.$data['reference']);
            if($payment['data']['status'] == 'success'){
                $check_trans = Transaction::whereReference($payment['data']['reference'])->first();
                if (!$check_trans) {
                    $job = $this->find($data['job_id']);
                    if ($pay = $this->payUsingCard($user, $job->price, $data)){
                        $job->payment_method = 'credit_card';
                        $job->payment_status = true;
                        $job_paid_id = $job->id;
                        if($job->save()){
                            $log = new SystemLog();
                            $log->category = 'card_payment';
                            $log->log_type = 'job_payment';
                            $log->message = "{$user->firstname} paid for job: {$job_paid_id} reference: {$data['reference']}";
                            $log->user_id = $user->id;
                            $log->save();
                            return $job;
                        }else{
                            $log = new SystemLog();
                            $log->category = 'card_payment';
                            $log->log_type = 'job_payment_update_fail';
                            $log->message = "{$user->firstname} job: {$job_paid_id} failed to update after payment, reference: {$data['reference']}";
                            $log->user_id = $user->id;
                            $log->save();
                            return false;
                        }
                    }else{
                        $log = new SystemLog();
                        $log->category = 'card_payment';
                        $log->log_type = 'job_payment_fail';
                        $log->message = "{$user->firstname} job: {$job->id} payment failed, reference: {$data['reference']}";
                        $log->user_id = $user->id;
                        $log->save();
                        return false;
                    }
                }else{
                    $log = new SystemLog();
                    $log->category = 'card_payment';
                    $log->log_type = '419';
                    $log->message = "{$user->firstname} job: {$data['job_id']} payment failed, reference: {$data['reference']}";
                    $log->user_id = $user->id;
                    $log->save();
                    return false;
                }
            }
            $log = new SystemLog();
            $log->category = 'card_payment';
            $log->log_type = '419';
            $log->message = "{$user->firstname} job: {$job->id} payment failed, reference: {$data['reference']}";
            $log->user_id = $user->id;
            $log->save();
            return false;
            
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }
     }

     public function logTransaction($category, $type, $message, $user_id, $rider_id, $job_id)
     {
         $log = new SystemLog();
         $log->category = $category;
         $log->log_type = $type;
         $log->message = $message;
         $log->user_id = $user_id;
         $log->rider_id = $rider_id;
         $log->job_id = $job_id;
         return $log->save();
     }
    
}