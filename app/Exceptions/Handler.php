<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
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

    
      // Register the exception handling callbacks for the application.
    
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    
     // Render an exception into an HTTP response.
     
    public function render($request, Throwable $e): mixed
    {
        // Handle API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    
     // Handle API exceptions and return JSON responses.
     
    protected function handleApiException($request, Throwable $exception): JsonResponse
    {
        $exception = $this->prepareException($exception);

        // Validation Exception
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        // Authentication Exception
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'Please login to access this resource.',
            ], 401);
        }

        // Authorization/Access Denied Exception
        if ($exception instanceof AccessDeniedHttpException) {
            return response()->json([
                'message' => 'Forbidden.',
                'error' => 'You do not have permission to access this resource.',
            ], 403);
        }

        // Model Not Found Exception
        if ($exception instanceof ModelNotFoundException) {
            $model = strtolower(class_basename($exception->getModel()));
            return response()->json([
                'message' => 'Resource not found.',
                'error' => "The requested {$model} could not be found.",
            ], 404);
        }

        // Not Found Exception (Route)
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Not found.',
                'error' => 'The requested endpoint does not exist.',
            ], 404);
        }

        // Method Not Allowed Exception
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'Method not allowed.',
                'error' => 'The HTTP method used is not supported for this endpoint.',
            ], 405);
        }

        // Too Many Requests Exception (Rate Limiting)
        if ($exception instanceof TooManyRequestsHttpException) {
            return response()->json([
                'message' => 'Too many requests.',
                'error' => 'You have exceeded the rate limit. Please try again later.',
            ], 429);
        }

        // Database Exceptions
        if ($exception instanceof \Illuminate\Database\QueryException) {
            return response()->json([
                'message' => 'Database error.',
                'error' => config('app.debug') 
                    ? $exception->getMessage() 
                    : 'A database error occurred. Please try again later.',
            ], 500);
        }

        // General Server Error
        $statusCode = method_exists($exception, 'getStatusCode') 
            ? $exception->getStatusCode() 
            : 500;

        $message = config('app.debug') 
            ? $exception->getMessage() 
            : 'An unexpected error occurred. Please try again later.';

        return response()->json([
            'message' => 'Server error.',
            'error' => $message,
            'debug' => config('app.debug') ? [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
            ] : null,
        ], $statusCode);
    }

    
     // Convert an authentication exception into a response.
     
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse|\Illuminate\Http\Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'Please login to access this resource.',
            ], 401);
        }

        return redirect()->guest(route('login'));
    }
}