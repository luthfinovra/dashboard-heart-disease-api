<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];
    protected $dontReport = [];
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*') || $request->wantsJson()) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    private function handleApiException($request, Throwable $exception)
    {
        $statusCode = 500;
        $response = [
            'success' => false,
            'message' => 'Internal Server Error',
            'data' => []
        ];

        if ($exception instanceof AuthenticationException) {
            $statusCode = 401;
            $response['message'] = 'Unauthenticated';
        } elseif ($exception instanceof AuthorizationException) {
            $statusCode = 403;
            $response['message'] = 'Forbidden';
        } elseif ($exception instanceof ValidationException) {
            $statusCode = 422;
            $response['message'] = 'Validation Error';
            $response['data'] = $exception->errors();
        } elseif ($exception instanceof NotFoundHttpException) {
            $statusCode = 404;
            $response['message'] = 'Not Found';
        } else {
            $response['message'] = $exception->getMessage() ?: 'Internal Server Error';
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }

        return response()->json($response, $statusCode);
    }
}