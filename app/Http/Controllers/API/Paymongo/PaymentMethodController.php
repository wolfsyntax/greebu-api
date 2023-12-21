<?php

namespace App\Http\Controllers\API\Paymongo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Luigel\Paymongo\Facades\Paymongo;

class PaymentMethodController extends Controller
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
        //
        $request->validate([
            'payment_type'  => ['required', ], // payment options (GCash, Maya, Card)
            'address_line1' => ['required', ],
            'city'          => ['required', ],
            'state'         => ['required', ],
            'country'       => ['required', ],
            'postal_code'   => ['required', ],
            'fullname'      => ['required', ],
            'email'         => ['required', ],
            'phone'         => ['required', ],
        ]);

        $paymentMethod = Paymongo::paymentMethod()
            ->create([
                'type'                  => $request->input('payment_type'),  // <--- and payment method type should be paymaya
                'billing'               => [
                    'address'           => [
                        'line1'         => $request->input('address_line1'),
                        'city'          => $request->input('city'),
                        'state'         => $request->input('state'),
                        'country'       => $request->input('country'),
                        'postal_code'   => $request->input('postal_code'),
                    ],
                    'name'              => $request->input('fullname'),
                    'email'             => $request->input('email'),
                    'phone'             => $request->input('phone'),
                ],
            ]);

        // Profile Payment method
        // profile_id
        // payment method id
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $paymentMethod = Paymongo::paymentMethod()->find($id);

        if (!$paymentMethod) abort(404, 'Paymongo payment method not found.');

        return response()->json([
            'status'    => 200,
            'message'   => 'Paymongo payment method',
            'result'    => [
                'payment_method'    => $paymentMethod
            ]
        ]);
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

    public function myPaymentMethod(Request $request) {


        $id = '';
        $paymentMethod = Paymongo::paymentMethod()->find($id);

        if (!$paymentMethod) abort(404, 'Paymongo payment method not found.');

        return response()->json([
            'status'    => 200,
            'message'   => 'Paymongo payment method',
            'result'    => [
                'payment_method'    => $paymentMethod
            ]
        ]);

    }
}
