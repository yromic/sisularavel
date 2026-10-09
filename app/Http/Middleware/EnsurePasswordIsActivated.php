<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsActivated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_reset_password) {
            if (! $request->expectsJson() && ! $request->is('password/activate')) {
                return redirect('/password/activate');
            }

            return response()->json([
                'success' => false,
                'code' => 'ERR_PASSWORD_RESET_REQUIRED',
                'message' => 'Password harus diganti sebelum melanjutkan.',
            ], 403);
        }

        return $next($request);
    }
}
