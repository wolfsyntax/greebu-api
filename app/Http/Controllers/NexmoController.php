<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Vonage\Client;
use Vonage\Verify;

class NexmoController extends Controller
{
    protected $basic;
    protected $client;

    public function __construct()
    {
        $this->basic  = new Client\Credentials\Basic(config('services.nexmo.api_key'), config('services.nexmo.api_secret'));
        $this->client = new Client(new Client\Credentials\Container($this->basic));
    }

    //
    public function store(Request $request)
    {
        // $basic  = new Client\Credentials\Basic("ae2fc8ae", "pT5QRS3vBISTQYrN");
        // $client = new Client(new Client\Credentials\Container($basic));

        $vrequest = new Verify\Request($request->input('phone'), config('services.nexmo.brand_name'));

        // Choose PIN length (4 or 6)
        $vrequest->setCodeLength(6);

        // Set Locale
        $vrequest->setCountry('ph');

        $response = $this->client->verify()->start($vrequest);

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => [
                'response' => $response,
                'request_id' => $response->getRequestId(),
            ]
        ]);
    }

    public function verify(Request $request)
    {

        $result = $this->client->verify()->check($request->input('request_id'), $request->input('code'));

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => [
                'response' => $result,
                'request_id' => $result->getRequestId(),
            ]
        ]);
    }
}
