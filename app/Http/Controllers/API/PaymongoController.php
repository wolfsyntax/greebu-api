<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Rules: Custom
use App\Rules\PhoneCheck;

use Ixudra\Curl\Facades\Curl;

use App\Models\User;

class PaymongoController extends Controller
{
    //
    public function stepOne(Request $request, User $user)
    {
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
            ->withHeader('authorization: Basic '.config('paymongo.auth'))
            ->withHeader('content-type: application/json')
            ->withData($payload)
            ->asJson()
            ->post();

        $request->session()->put('payment_intent', $response->data?->id);

        return response()->json([
            'status'        => 200,
            'message'       => 'Create Payment Intent',
            'result'        => [
                'paymongo'  => $response->data,
                'id'        => $response->data->id,
            ],
        ]);
    }

    public function stepTwo(Request $request, User $user, $intent)
    {
        $request->validate([
            'type'          => ['required', 'in:card,gcash,paymaya', ],
            'card_number'   => ['required_if:type,card', 'numeric', 'regex:/^[0-9]{16}$/', ],
            'exp_month'     => ['required_if:type,card', 'numeric', 'min:1', 'max:12', ],
            'exp_year'      => ['required_if:type,card', 'numeric', 'min:'.now()->format('Y'), 'max:'.now()->addYears(50)->format('Y'),],
            'cvc'           => ['required_if:type,card', 'string', 'regex:/^[0-9]{3,4}$/', ],
            // 'bank_code'     => ['required_if:type,dob', ],
            'name'          => ['required',  'string', 'min:2', ],
            'email'         => !app()->isProduction() ? ['required', 'email', 'max:255',] : ['required', 'email:rfc,dns', ],
            'phone'         => ['nullable', new PhoneCheck(),],
            'line1'         => ['nullable', 'string', 'max:255', ],
            'line2'         => ['nullable', 'string', 'max:255', ],
            'city'          => ['nullable', 'string', 'max:255', ],
            'state'         => ['nullable', 'string', 'max:255', ],
            'postal_code'   => ['nullable', 'string', 'max:255', ],
            'country'       => ['nullable', 'string', 'max:255', 'min:2', ],
        ]);

        $payload = [
            'data'                          => [
                'attributes'                => [
                    'type'                  => $request->input('type'),
                    'details'               => [
                        'card_number'       => $request->card_number,
                        'exp_month'         => $request->exp_month,
                        'exp_year'          => $request->exp_year,
                        'cvc'               => $request->cvc,
                    ],
                    'billing'               => [
                        'name'              => $request->name,
                        'email'             => $request->email,
                        'phone'             => $request->phone,
                        'address'           => [
                            'line1'         => $request->line1,
                            'line2'         => $request->line2,
                            'city'          => $request->city,
                            'state'         => $request->state,
                            'postal_code'   => $request->postal_code,
                            'country'       => $request->country,
                        ]
                    ]
                ]
            ]
        ];

        // Create Payment method
        $payment_method = Curl::to('https://api.paymongo.com/v1/payment_methods')
            ->withHeader('accept: application/json')
            ->withHeader('authorization: Basic '.config('paymongo.auth'))
            ->withHeader('content-type: application/json')
            ->withData($payload)
            ->asJson()
            ->post();

        $attachment = [
            'data'              => [
                'attributes'    => [
                    'payment_method'    => $payment_method->data->id,
                    'return_url'        => env('FRONTEND_URL'), // Optional: card; Required: atome / paymaya
                ]
            ]
        ];

        // Attach Payment Intent
        $response = Curl::to('https://api.paymongo.com/v1/payment_intents/'.$intent.'/attach')
            ->withHeader('accept: application/json')
            ->withHeader('authorization: Basic '.config('paymongo.auth'))
            ->withHeader('content-type: application/json')
            ->withData($attachment)
            ->asJson()
            ->post();

        return response()->json([
            'status'            => 200,
            'message'           => 'Create Payment Method.',
            'result'            => [
                'paymongo_pm'   => $payment_method->data,
                'pm_id'         => $payment_method->data->id,
                'paymongo_pi'   => $response,
                // 'pi_id'         => $response->data->id,
            ],
        ]);
    }
}
