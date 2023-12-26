<?php

namespace App\Http\Controllers\API\Paymongo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Luigel\Paymongo\Facades\Paymongo;

class PaymentIntentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount'                => ['required',],
            'payment_method'        => ['required', ],
            'description'           => ['required', ],
            'statement_descriptor'  => ['required', ],
            'currency'              => ['required', ],
        ]);

        //
        
        $paymentIntent = Paymongo::paymentIntent()->create([
            'amount'                    => $request->input('amount'),
            'payment_method_allowed'    => [
                'card',
            ],
            'payment_method_options'    => [
                'card'      => [
                    'request_three_d_secure'    => 'automatic',
                ],
            ],
            'description'   => $request->input('description'),
            'statement_descriptor'  => $request->input('statement_descriptor'),
            'currency'  => $request->input('currency', 'PHP'),
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

    /**
     * Cancel the payment intent.
    */
    public function cancelPayment(Request $request)
    {
        $request->validate([
            'paymentIntent_id'  => ['required', ],
        ]);

        $paymentIntent = Paymongo::paymentIntent()->find($request->input('paymentIntent_id')); // paymentIntent_id => 'pi_hsJNpsRFU1LxgVbxW4YJHRs6'
        $cancelledPaymentIntent = $paymentIntent->cancel();

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => [
                'payment_intent' => $cancelledPaymentIntent
            ]
        ]);
    }

    /**
     * Attach the payment method in payment intent.
    */
    public function attachPaymentMethod(Request $request, $paymentIntent)
    {
        $paymentIntent = Paymongo::paymentIntent()->find($paymentIntent); // 'pi_hsJNpsRFU1LxgVbxW4YJHRs6'

        // Attached the payment method to the payment intent
        // $successfulPayment = $paymentIntent->attach('pm_wr98R2gwWroVxfkcNVZBuXg2');

        // Attaching paymaya payment method in payment intent.

    }
}
