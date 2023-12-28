<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;

trait PaymongoTrait
{

    public function paymentIntent($payload) {

        $response = Curl::to('https://api.paymongo.com/v1/payment_intents')
            ->withHeader('accept: application/json')
            ->withHeader('authorization: Basic '.config('paymongo.auth'))
            ->withHeader('content-type: application/json')
            ->withData($payload)
            ->asJson()
            ->post();

        return $response->data->id ?? '';

    }

    public function paymentMethod($payload) {

        $response = Curl::to('https://api.paymongo.com/v1/payment_methods')
            ->withHeader('accept: application/json')
            ->withHeader('authorization: Basic '.config('paymongo.auth'))
            ->withHeader('content-type: application/json')
            ->withData($payload)
            ->asJson()
            ->post();

        return $response->data->id ?? '';
    }

    public function attach($paymentMethodIntent, $paymentMethod, $payload) {

        $response = Curl::to('https://api.paymongo.com/v1/payment_intents/'.$paymethodIntent.'/attach')
            ->withHeader('accept: application/json')
            ->withHeader('authorization: Basic '.config('paymongo.auth'))
            ->withHeader('content-type: application/json')
            ->withData($payload)
            ->asJson()
            ->post();

        return $response->id ?? '';
    }
}
