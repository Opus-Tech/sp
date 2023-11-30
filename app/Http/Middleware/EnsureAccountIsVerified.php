<?php

namespace App\Http\Middleware;

use Closure;
use App\Traits\apiResponse;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use Tymon\JWTAuth\JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class EnsureAccountIsVerified extends BaseMiddleware
{
    use apiResponse;
    use Helpers;

    public function __construct(JWTAuth $auth)
    { 
        $this->authService = $auth; 
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {   
            if (!$this->authService->user()->hasVerifiedAccount()) {
                return $this->sendResponse(
                    ['message' => 'Access Denied, account is not yet verified' ],
                    "error",
                    403
                );
            }
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['status' => 'Token is Invalid']);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['status' => 'Token is Expired']);
            }else{
                return response()->json(['status' => 'Authorization Token not found']);
            }
        }

        return $next($request);
    }
}
