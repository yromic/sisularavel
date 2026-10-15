<?php

use App\Console\Commands\MigrateLegacySisurat;
use App\Console\Commands\PruneExpiredIdempotencyKeys;
use App\Http\Middleware\EnsureIdempotentMutation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        MigrateLegacySisurat::class,
        PruneExpiredIdempotencyKeys::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->alias([
            'idempotent' => EnsureIdempotentMutation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $expectsSisuratJson = fn (Request $request): bool => $request->expectsJson() || ($request->is('api/*') && ! $request->acceptsHtml());
        $errorResponse = fn (string $code, string $message, int $status, array $errors = []) => response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'errors' => empty($errors) ? (object) [] : $errors,
            'timestamp' => now()->utc()->toIso8601String(),
        ], $status);

        $exceptions->render(function (ValidationException $e, Request $request) use ($errorResponse, $expectsSisuratJson) {
            if ($expectsSisuratJson($request)) {
                if ($e->status === 401) {
                    return $errorResponse(
                        'ERR_401_SESSION',
                        collect($e->errors())->flatten()->first() ?? 'Sesi Anda telah berakhir. Silakan login kembali.',
                        401,
                        $e->errors()
                    );
                }

                return $errorResponse(
                    'ERR_422_VALIDATION',
                    'Data yang dikirim tidak memenuhi persyaratan validasi.',
                    422,
                    $e->errors()
                );
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($errorResponse, $expectsSisuratJson) {
            if ($expectsSisuratJson($request)) {
                return $errorResponse(
                    'ERR_401_SESSION',
                    'Sesi Anda telah berakhir. Silakan login kembali.',
                    401
                );
            }

            return redirect()->guest('/login');
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($errorResponse, $expectsSisuratJson) {
            if ($expectsSisuratJson($request)) {
                return $errorResponse(
                    'ERR_403_SCOPE',
                    'Anda tidak memiliki wewenang untuk melakukan tindakan ini.',
                    403
                );
            }

            return null;
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($errorResponse, $expectsSisuratJson) {
            if ($expectsSisuratJson($request)) {
                return $errorResponse(
                    'ERR_404_RECORD',
                    'Data yang Anda cari tidak ditemukan.',
                    404
                );
            }

            return null;
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($errorResponse, $expectsSisuratJson) {
            if ($expectsSisuratJson($request)) {
                return $errorResponse(
                    'ERR_429_RATE_LIMIT',
                    'Terlalu banyak permintaan. Silakan tunggu beberapa saat.',
                    429
                );
            }

            return null;
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($errorResponse, $expectsSisuratJson) {
            if (! $expectsSisuratJson($request)) {
                return null;
            }

            if ($e->getStatusCode() === 403) {
                return $errorResponse(
                    'ERR_403_SCOPE',
                    'Anda tidak memiliki wewenang untuk melakukan tindakan ini.',
                    403
                );
            }

            return null;
        });
    })->create();
