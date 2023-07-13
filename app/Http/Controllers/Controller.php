<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function successResponse($message, $data = null, $code = 200)
    {
        return response()->json([
            'status'    => $code,
            'message'   => $message,
            'result'    => $data,
        ], 200);
    }

    protected function debugResponse($message, $data)
    {
        return response()->json([
            'status'    => 201,
            'message'   => 'Debugging',
            'result'    => [
                'data'  => $data,
            ]
        ]);
    }
}
