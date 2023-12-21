<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Luigel\Paymongo\Facades\Paymongo;

use App\Models\BillingMethod;
use App\Models\User;

class BillingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store billing details
     */
    public function store(Request $request, User $user)
    {


        $request->validate([
            'payment_type'          => ['required', 'in:gcash,paymaya,card' ],
            'card_num'              => ['required_if:payment_type,card',  ],
            'exp_month'             => ['required_if:payment_type,card', 'numeric', 'min:1', 'max:12', ],
            'exp_year'              => ['required_if:payment_type,card', 'numeric', 'min:'.now()->format('YYYY'), ],
            'exp_cvc'               => ['required_if:payment_type,card', 'numeric', 'size:3', ],
            'address_line1'         => ['required', 'max:255', ],
            'city'                  => ['required', 'max:255', ],
            'state'                 => ['required', 'max:255', ],
            'country'               => ['nullable', 'max:255', ],
            'postal_code'           => ['required', 'max:255', ],
            'fullname'              => ['required', 'max:255', ],
            'email'                 => ['required', 'max:255', ],
            'phone'                 => ['required', 'max:255', ],
            'amount'                => ['required', 'numeric', 'gt:0'],
        ]);

        $frontend = env('FRONTEND_URL', 'https://develop.geebu.ph');

        $payload = [
            'type' => $request->input('payment_type', 'gcash'),
            'amount' => $request->input('amount'),
            'currency' => 'PHP',
            'redirect' => [
                'success' => $frontend.'/success',
                'failed' => $frontend.'/failed',
            ],
        ];

        $payment = Paymongo::source()->create($payload);

        return response()->json([
            'status'    => 200,
            'message'   => 'Billing Info',
            'result'    => [
                'payment'   => $payment,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function checkout(Request $request, User $user) {

        $checkout = Paymongo::checkout()->create([
            'cancel_url' => env('FRONTEND_URL').'/cancel-payment',
            'billing' => [
                'name' => 'Juan Doe',
                'email' => 'juan@doe.com',
                'phone' => '+639123456789',
            ],
            'description' => 'My checkout session description',
            'line_items' => [
                [
                    'amount' => 10000,
                    'currency' => 'PHP',
                    'description' => 'Something of a product.',
                    'images' => [
                        'https://images.unsplash.com/photo-1613243555988-441166d4d6fd?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80'
                    ],
                    'name' => 'A payment card',
                    'quantity' => 1,
                ]
            ],
            'payment_method_types' => [
                'atome',
                'billease',
                'card',
                'dob',
                'dob_ubp',
                'gcash',
                'grab_pay',
                'paymaya',
            ],
            'success_url' => env('FRONTEND_URL').'/success-payment',
            'statement_descriptor' => 'Laravel Paymongo Library',
            'metadata' => [
                'Key' => 'Value'
            ],
        ]);

        return response()->json([
            'status'    => 200,
            'message'   => 'Checkout',
            'result'    => [
                'paymongo'  => $checkout,
                // session id ==> paymongo data->id
            ],
        ]);

    }

    public function checkoutSuccess(Request $request, $sessionId) {

        $checkout = Paymongo::checkout()->find($sessionId);

        return response()->json([
            'status'    => 200,
            'message'   => 'Successful checkout.',
            'result'    => [
                'paymongo'  => $checkout,
            ],
        ]);

    }

    public function refund(Request $request, $paymentId) { // pay_

        $request->validate([
            'amount'    => ['required', ],
            'reason'    => ['required', ],
        ]);

        $refund_payment = Paymongo::refund()->create([]);

        return response()->json([
            'status'    => 200,
            'message'   => 'Refund payment',
            'result'    => [
                'paymongo'  => $refund_payment,
            ],
        ]);
    }

    public function refundStatus(Request $request, $refundId) { // ref_

        $refund_status = Paymongo::refund()->find($refundId);

        return response()->json([
            'status'    => 200,
            'message'   => 'Refund payment status',
            'result'    => [
                'paymongo'  => $refund_status,
            ],
        ]);
    }

    public function link(Request $request) {

        $request->validate([
            'amount'   => ['required',],
            'description'   => ['required', ],
        ]);

        $link = Paymongo::link()->create([]);

        return response()->json([
            'status'    => 200,
            'message'   => 'Payment via Link',
            'result'    => [
                'paymongo'  => $link,
            ],
        ]);
    }

    public function linkStatus(Request $request, $linkId) { // link_

        $link = Paymongo::link()->find($linkId);

        return response()->json([
            'status'    => 200,
            'message'   => 'Payment via Link',
            'result'    => [
                'paymongo'  => $link,
            ],
        ]);
    }

}
