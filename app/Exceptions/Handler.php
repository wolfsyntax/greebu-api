<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'    => 403,
                    'message'   => 'You do not have the required authorization.',
                    'result'    => [],
                ], 203);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'    => 404,
                    'message'   => 'Page not found.',
                    'result'    => [],
                ], 203);
            }
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'    => 404,
                    'message'   => 'Model not found.',
                    'result'    => [],
                ], 203);
            }
        });

        $this->renderable(function (ValidationException $e, $request) {

            if ($request->is('api/*')) {
                return response()->json([
                    'status'    => 422,
                    'message'   => 'Unprocessible Entity.',
                    'result'    => [
                        'errors' => $e->errors(),
                        'form'  => $request->all(),
                    ],
                ], 203);
            }
        });

        $this->renderable(function (Throwable $e, $request) {

            if ($request->is('api/*')) {
                return response()->json([
                    'status'    => 500,
                    'message'   => 'Server Error.',
                    'result'    => [
                        'errors' => $e->getMessage(),
                        'request' => $request
                    ],
                ], 203);
            }
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'status'    => 401,
            'message'   => $exception->getMessage(),
            'result'    => [],
        ], 401);
    }
}
