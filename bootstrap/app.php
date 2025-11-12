<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add CORS middleware to API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\Cors::class,
        ]);
        
        // Add input sanitization middleware to prevent XSS
        $middleware->api(append: [
            \App\Http\Middleware\SanitizeInput::class,
        ]);
        
        // Add session tracking middleware to API routes (after auth)
        $middleware->api(append: [
            \App\Http\Middleware\TrackUserSession::class,
        ]);

        // Exclude storage routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            '/storage/*',
        ]);

        // Your role middleware alias
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle 404 Not Found errors
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'The requested resource could not be found.',
                    'status' => 404
                ], 404);
            }
            
            // For web routes, you could return a view
            // return response()->view('errors.404', [], 404);
        });

        // Handle 403 Forbidden errors
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to access this resource.',
                    'status' => 403
                ], 403);
            }
        });

        // Handle 401 Unauthorized errors
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You must be authenticated to access this resource.',
                    'status' => 401
                ], 401);
            }
        });

        // Handle validation errors
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'Validation Failed',
                    'message' => 'The provided data was invalid.',
                    'errors' => $e->errors(),
                    'status' => 422
                ], 422);
            }
        });

        // Handle 500 Internal Server errors
        $exceptions->renderable(function (\Throwable $e, $request) {
            // Only handle 500 errors, let other exceptions pass through
            if (!app()->isProduction() || $request->is('api/*')) {
                // In development or for API, return detailed error info
                if ($request->is('api/*')) {
                    $response = [
                        'error' => 'Internal Server Error',
                        'message' => 'An unexpected error occurred. Our team has been notified.',
                        'status' => 500
                    ];

                    // Add debug info in non-production
                    if (!app()->isProduction()) {
                        $response['debug'] = [
                            'exception' => get_class($e),
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => collect($e->getTrace())->take(5)->toArray()
                        ];
                    }

                    return response()->json($response, 500);
                }
            }
        });

        // Log all exceptions for monitoring
        $exceptions->report(function (\Throwable $e) {
            // Log to Laravel log
            if (app()->isProduction()) {
                \Log::error('Exception occurred', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                ]);
            }

            // TODO: Send to external logging service (e.g., Sentry, Bugsnag)
            // if (app()->isProduction()) {
            //     app('sentry')->captureException($e);
            // }
        });
    })
    ->create();
