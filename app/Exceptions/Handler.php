<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
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
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'status'    => 401,
            'message'   => $exception->getMessage(),
            'result'    => [],
        ], 401);
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof Throwable) {
            return response()->json([
                'status'    => 403,
                'message'   => 'Not authorized access.',
                'result'    => [],
            ], 203);
        }
        return parent::render($request, $exception);
    }
}
