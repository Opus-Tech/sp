<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Wallet;

use App\Models\Transaction;

use App\Models\SystemLog;

use App\Services\UserService;

use App\Services\ExternalService;

use App\Http\Requests\WalletRequest;

use Tymon\JWTAuth\JWTAuth;
    
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Validation\ValidationException;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; 

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WalletController extends BaseController
{
    private $userService;

    private $authService;

    private $apiCall;

    public function __construct(UserService $userService, ExternalService $apiCall, JWTAuth $auth)
    {
        $this->userService = $userService;
        $this->authService = $auth;
        $this->apiCall = $apiCall;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $user = $this->authService->user();
            $wallet = ($user->wallet)? $user->wallet->balance : 0.00;
            return $this->response->array([
                'status' => true,
                'data' => [
                    'user' => [
                        'firstname' => $user->firstname,
                        'lastname' =>$user->lastname,
                        'email' => $user->email,
                        'phonenumber' => $user->phone,
                    ],
                    'wallet_balance' => $wallet, 
                    'user_transactions' => ($user->transactions)? $user->transactions->makeHidden(["user_id", "job_id", "gateway_message"]) : null,
                ]
            ], 200);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound();
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(WalletRequest $request)
    {
        try {
            $payment = $this->apiCall->getPayRequest('https://api.paystack.co/transaction/verify/'.$request->reference);
            if($payment['data']['status'] == 'success'){
                $user = $this->authService->user();
                $check_trans = Transaction::whereReference($payment['data']['reference'])->first();
                if (!$check_trans) {
                    $wallet = $user->wallet;
                    if(!$wallet){
                        $wallet = new Wallet();
                        $wallet->user_id = $user->id;
                        $wallet->balance = $payment['data']['amount'] / 100;
                    }else{
                        $wallet->balance = $wallet->balance + ($payment['data']['amount'] / 100);
                    }
                    if($wallet->save()){
                        $log = new SystemLog();
                        $log->category = 'wallet';
                        $log->log_type = 'credit';
                        $log->message = "{$user->firstname} credit wallet";
                        $log->user_id = $user->id;
                        $log->save();
                        $wallet_balance = $wallet->balance;
                        $transaction = new Transaction();
                        $transaction->source = 'card';
                        $transaction->type = 'credit';
                        $transaction->amount = $payment['data']['amount'] / 100;
                        $transaction->user_id = $user->id;
                        $transaction->reference = $payment['data']['reference'];
                        $transaction->gateway_message = $payment['data']['gateway_response'];
                        $transaction->status = 'success';
                        if($transaction->save()){
                            return $this->response->array([
                                'status' => true,
                                'message'   =>      'Wallet credited successfully',
                                'data' => array(
                                    'user' => [
                                        'id' => $user->id,
                                        'firstname' => $user->firstname,
                                        'lastname' => $user->lastname,
                                        'email' => $user->email,
                                        'phonenumber' => $user->phone,
                                    ],
                                    'wallet' => number_format($wallet_balance, 2, '.', ''),
                                ), 
                            ]);
                        }
                    }
                }else{
                    return $this->response->array([
                            'status' => false,
                            'message' => 'Wallet previously topped'
                        ]
                    )->setStatusCode(401);
                }
            }
            return $this->response->array([
                    'status' => false,
                    'message' => 'Wallet topup failed'
                ]
            )->setStatusCode(403);
            
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
