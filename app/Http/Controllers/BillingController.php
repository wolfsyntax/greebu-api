<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;

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
            'type'                  => ['required', 'in:gcash,grab_pay', ],
            'amount'                => ['required', 'numeric', 'gt:0'],
            'name'                  => ['nullable', 'max:255', ],
            'phone'                 => ['required_if:name', 'max:255', ],
            'email'                 => ['required_if:name', 'max:255', ],
            'line1'                 => ['required_if:name', 'max:255', ],
            'line2'                 => ['nullable', 'max:255', ],
            'state'                 => ['required_if:city', 'max:255', ],
            'postal_code'           => ['required_if:city', 'max:255', ],
            'city'                  => ['required_if:name', 'max:255', ],
            'country'               => ['required_if:state', 'min:2', 'max:255', ],
        ]);

        $frontend = env('FRONTEND_URL', 'https://develop.geebu.ph');

        $payload = [
            'type' => $request->input('type'),
            'amount' => $request->input('amount'),
            'currency' => 'PHP',
            'redirect' => [
                'success' => $frontend.'/success',
                'failed' => $frontend.'/failed',
            ],
            'billing'   => [
                'name'  => $request->input('name', ''),
                'phone' => $request->input('phone', ''),
                'email' => $request->input('email', ''),
                'address'   => [
                    'line1' => $request->input('line1', ''),
                    'line2' => $request->input('line2', ''),
                    'state' => $request->input('state', ''),
                    'postal_code' => $request->input('postal_code',''),
                    'city'      => $request->input('city'),
                    'country'      => $request->input('country'),
                ]
            ]
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

        $request->validate([
            'has_billing'                   => ['required', 'boolean',],
            'name'                          => ['required_if:has_billing,true', 'max:255', 'string',],
            'email'                         => ['required_if:name', 'email:rfc,dns', 'max:255',],
            'phone'                         => ['required_if:phone', 'max:255', ],
            'description'                   => ['nullable', 'max:255',],
            'line1'                         => ['required_if:name', 'required_if:has_billing,true', 'string', 'max:255',],
            'line2'                         => ['nullable', 'string', 'max:255',],
            'city'                          => ['required_if:name', 'required_if:has_billing,true', 'string', 'max:255',    ],
            'state'                         => ['required_if:city', 'string', 'max:255', ],
            'postal_code'                   => ['required_if:city', 'string', 'max:255',],
            'country'                       => ['required_if:state', 'string', 'min:2', 'max:255',],
            'line_items'                    => ['required','array','max:999', ],
            'line_items.*.amount'           => ['required', 'numeric', 'min:1'],
            'line_items.*.currency'         => ['required', 'string', 'in:PHP',],
            'line_items.*.description'      => ['nullable', 'string', 'max:255',],
            'line_items.*.images'           => ['required', 'array', 'max:1', ],
            'line_items.*.images.*'         => ['required', 'string', ],
            'payment_method_types'          => ['required', 'array', ],
            'payment_method_types.*'        => ['required', 'string', 'in:billease,card,dob,dob_ubp,gcash,grab_pay,paymaya', ],
            'reference_number'              => ['nullable', 'string', 'max:255',],
            'send_email_receipt'            => ['nullable', 'boolean',],
            'show_description'              => ['nullable', 'boolean',],
            'show_line_items'               => ['nullable', 'boolean',],
            'statement_descriptor'          => ['nullable', 'string', 'max:255',],
        ]);

        $payload = [
            'cancel_url'    => env('FRONTEND_URL').'/cancel-payment',
            'description'   => $request->input('description', ''),
            'line_items'    => $request->input('line_items'),
            'payment_method_types'  => $request->input('payment_method_types'),
            'success_url' => env('FRONTEND_URL').'/success-payment',
            'statement_descriptor' => 'Geebu Checkout',
        ];

        // If Billing Details is required
        if (filter_var($request->input('has_billing', false),FILTER_VALIDATE_BOOLEAN)) {
            $payload['billing'] = $request->only(['name', 'email', 'phone',]);
            $payload['billing']['address'] = $request->only(['line1', 'line2', 'city', 'state', 'postal_code', 'country',]);
        }

        if($request->has('reference_number')) $payload['reference_number'] = $request->input('reference_number', '');

        if($request->has('send_email_receipt')) $payload['send_email_receipt'] = filter_var($request->input('send_email_receipt', true), FILTER_VALIDATE_BOOLEAN);
        if($request->has('show_description')) $payload['show_description'] = filter_var($request->input('show_description', true), FILTER_VALIDATE_BOOLEAN);
        if($request->has('show_line_items')) $payload['show_line_items'] = filter_var($request->input('show_line_items', true), FILTER_VALIDATE_BOOLEAN);

        return response()->json([
            'status'    => 200,
            'message'   => 'Geebu Checkout',
            'result'    => [
                'paymongo'  => $payload,
            ],
        ]);

        $checkout = Paymongo::checkout()->create($payload);

        return response()->json([
            'status'    => 200,
            'message'   => 'Checkout',
            'result'    => [
                'paymongo'  => $checkout,
                // session id ==> paymongo data->id
            ],
        ]);

    }

    public function checkoutSuccess(Request $request, User $user, $session) {

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

    // Paymongo in Nut Shell
    public function stepOne(Request $request, User $user) {

        $request->validate([
            'amount'    => ['required', 'numeric', 'min:0'],
            // 'payment_method_allowed'    => ['required', 'array',],
            // 'payment_method_allowed.*'          => ['required', 'string', 'in:card,dob,gcash,paymaya',],
            // 'payment_method_allowed'        => ['required', 'in:card,gcash,paymaya',],
            'description'                   => ['nullable', 'string', 'max:255', ],
        ]);

        $payload = [
            'amount'    => $request->input('amount'),
            'payment_method_allowed'    => [
                'card', 'gcash', 'paymaya',
            ],
            'description'   => $request->input('description', 'Subscription for the account'),
            'statement_descriptor'  => 'Geebu plan subscription',
            'currency'  => $request->input('currency', 'PHP'),
        ];

        if ($request->input('payment_method_allowed') === 'card') {
            $payload['payment_method_options'] = [
                'card' => [
                    'request_three_d_secure' => 'automatic',
                ],
            ];
        }

        $payment_intent = Paymongo::paymentIntent()->create($payload);

        return response()->json([
            'status'    => 200,
            'message'   => 'Payment Intent created',
            'result'    => [
                'paymongo'  => $payment_intent,
            ],
        ]);

    }
}
