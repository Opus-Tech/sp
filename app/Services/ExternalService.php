<?php

namespace App\Services;
use \GuzzleHttp\Client;

class ExternalService
{
    public function connect() : Client
    {
        $secretKey = config('paystack.secretKey');
        return $client = new Client([
                'headers' => [
                        'Authorization' => 'Bearer '.$secretKey,
                        'Content-Type' => 'application/json',
                    ]
            ]);
    }

    public function getRequest($url)
    {
        $request = $this->connect()->get($url);
        $response = $request->getBody();
        return $response;
    }
    
    public function postRequest($url, array $data)
    {
        $request = $this->connect()->post($url,  ['form_params' =>$data]);
        $response = $request->getStatusCode();
        return $response;
    }

    public function postPayRequest($url, array $data)
    {
        $request = $this->connect()->post($url,  ['form_params' =>$data]);
        $response = json_decode($request->getBody()->getContents(), true);
        return $response;
    }

    public function getPayRequest($url)
    {
        $request = $this->connect()->get($url);
        $response = json_decode($request->getBody()->getContents(), true);
        return $response;
    }

    public function putRequest($url, array $data)
    {
        $request = $this->connect()->put($url,  ['body'=>$data]);
        $response = $request->getBody();
        return $response;
    }

    public function deleteRequest($url)
    {
        $request = $this->connect()->delete($url);
        $response = $request->getBody();
        return $response;
    }
}
