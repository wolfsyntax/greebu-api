<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// use GuzzleHttp\Client;
// use GuzzleHttp\Exception\RequestException;
// use Guzzle\Http\Exception\ClientErrorResponseException;
// use GuzzleHttp\Exception\ServerException;
// use GuzzleHttp\Exception\BadResponseException;

use Ixudra\Curl\Facades\Curl;

class PaymentController extends Controller
{
    /**
     * Paymongo Payment method allowed
     * "atome",
     * "card",
     * "dob" => Direct Online Banking,
     * "paymaya",
     * "billease",
     * "gcash",
     * "grab_pay"
    */
    public function __construct() {
        $this->client = new \GuzzleHttp\Client();
    }

    /**
     * 1. Create A Payment Intent from the server-side
     * When a customer initiates a credit card or a PayMaya payment,
     * create a payment intent by calling our Create A Payment Intent API: https://developers.paymongo.com/reference/create-a-paymentintent.
     * Store the Payment Intent ID.
     *
    */
    public function stepOne(Request $request) {

        $request->validate([
            'amount'    => ['required', 'numeric',],
        ]);

        $payload = [
            'data'  => [
                'attributes'    => [
                    'amount'    => $request->input('amount'),
                    'payment_method_allowed' => [
                        'card','paymaya', 'gcash',
                    ],
                    "payment_method_options" => [
                        "card" => [
                            "request_three_d_secure" => "any"
                        ]
                    ],
                    "description"   => "Payment Intent",
                    "statement_descriptor"  => "Geebu Payment Intent",
                    "currency" => "PHP",
                    "capture_type" =>"automatic",
                ]
            ]
        ];

        $response = Curl::to('https://api.paymongo.com/v1/payment_intents')
            ->withHeader('accept: application/json')
            ->withHeader('authorization: Basic '.base64_encode(env('PAYMONGO_SECRET_KEY')))
            ->withHeader('content-type: application/json')
            ->withData($payload)
            ->asJson()
            ->post();

        return $response;

    }

    /**
     * 2. Create a Payment Method from the client-side
     * Collect Card Information from the client-side with the use of forms.
     * We do not recommend storing this information on your server.
     * Send this information over to us and we'll handle the rest!
     * Create a payment method by calling our Create A Payment Method API: https://developers.paymongo.com/reference#create-a-paymentmethod.
     * Store the Payment Method ID.
    */
    public function stepTwo(Request $request, $intentId) {

        $request->validate([
            'amount'    => ['required', 'numeric',],
        ]);

        $payload = [
            'data'  => [
                'attributes'    => [
                    'type'      => $request
                    'amount'    => $request->input('amount'),
                    'payment_method_allowed' => [
                        'card','paymaya', 'gcash',
                    ],
                    "payment_method_options" => [
                        "card" => [
                            "request_three_d_secure" => "any",
                        ],
                    ],
                    "description"   => "Payment Intent",
                    "statement_descriptor"  => "Geebu Payment Intent",
                    "currency" => "PHP",
                    "capture_type" =>"automatic",
                ],
            ]
        ];

        $response = Curl::to('https://api.paymongo.com/v1/payment_intents')
            ->withHeader('accept: application/json')
            ->withHeader('authorization: Basic '.base64_encode(env('PAYMONGO_SECRET_KEY')))
            ->withHeader('content-type: application/json')
            ->withData($payload)
            ->asJson()
            ->post();

        return $response;
    }
}
