<?php

use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$router = app(Router::class);
$router->version('v1', function (Router $router) {
    $router->group(['namespace' => '\App\Http\Controllers'], function(Router $router){
        $router->post('register', 'Auth\AuthController@register');
        $router->post('email-register', 'Auth\AuthController@register_by_email');
        $router->post('resend-email', 'Auth\AuthController@resend_email_verification');
        $router->post('login', 'Auth\AuthController@userLogin');
        $router->post('verify', 'AccountVerificationController@verify');
        $router->post('resend-code', 'AccountVerificationController@reVerify');
        $router->post('verify-email', 'AccountVerificationController@verifyEmail');
        $router->post('call', 'AccountVerificationController@makeCall');
        $router->post('call/{code}', 'AccountVerificationController@voiceML');
        $router->post('delete-account', 'Auth\AuthController@delete');
        $router->post('forgot-password-otp', 'Auth\AuthController@forgotPassword');
        $router->post('forgot-password', 'Auth\AuthController@newPassword');

        $router->group(['middleware' => ['api.auth']], function (Router $router) {
            $router->post('profile-update', 'Auth\AuthController@update');
            $router->post('user-profile-update', 'Auth\AuthController@profileupdate');
            $router->resource('job-request', 'JobController');
            $router->post('job-payment', 'JobController@payment');
            $router->post('cancel-job', 'JobController@cancel');
            $router->resource('wallet', 'WalletController');
            $router->post('distance_cost', 'JobController@getTotalDistanceCost');
            $router->get('profile', 'Auth\AuthController@getUser');
            $router->get('user-jobs', 'JobController@getUserJobs');
            $router->post('rate', 'JobController@rateRider');
            $router->post('change-password', 'Auth\AuthController@changePassword');
            $router->get('logout', 'Auth\AuthController@logout');
        });
        
        /**
         * Rider's Route
         */
        $router->group(['prefix' => 'rider'], function (Router $router) {
            $router->post('register', 'Rider\AuthController@register');
            $router->post('resend-code', 'Rider\AuthController@resendCode');
            $router->post('login', 'Rider\AuthController@login');
            $router->post('verify', 'Rider\AuthController@verify');
            $router->post('call', 'Rider\AuthController@makecall');
            $router->post('call/{code}', 'Rider\AuthController@voiceML');
            $router->post('delete-account', 'Rider\AuthController@delete');
            $router->post('forgot-password-otp', 'Rider\AuthController@forgotPassword');
            $router->post('forgot-password', 'Rider\AuthController@newPassword');

			$router->group(['middleware' => ['assign.guard:rider','jwt.auth']], function (Router $router) {
			    $router->post('complete-profile', 'Rider\AuthController@update');
                $router->post('profile-update', 'Rider\AuthController@profileupdate');
                $router->post('identity-profile', 'Rider\AuthController@identity');
                $router->resource('jobs', 'Rider\JobController');
                $router->get('completed-jobs', 'Rider\JobController@completed');
                $router->get('cancelled-jobs', 'Rider\JobController@cancelled');
                $router->get('profile', 'Rider\AuthController@getRider');
                $router->post('gps-log', 'Rider\AuthController@logGPS');
                $router->post('update-job-status', 'Rider\JobController@control');
                $router->post('change-password', 'Rider\AuthController@changePassword');
                $router->get('logout', 'Rider\AuthController@logout');
	        });
	        /**
             * End Rider's Route
             */
		});
    });
});
