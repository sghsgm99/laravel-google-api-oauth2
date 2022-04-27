<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ValidationException $ex, $request) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $ex->validator->errors()
            ], 422);
        });

        $this->renderable(function (RouteNotFoundException $ex, $request) {
            return response()->json([
                'message' => 'Not Found',
                'error' => $ex->getMessage()
            ], 404);
        });

        $this->renderable(function (ModelNotFoundException $ex, $request) {
            return response()->json([
                'message' => 'Resource Not Found',
                'error' => $ex->getMessage()
            ], 404);
        });

        $this->renderable(function (NotFoundHttpException $ex, $request) {
            return response()->json([
                'message' => 'Resource Not Found',
                'error' => $ex->getMessage()
            ], 404);
        });
    }
}
