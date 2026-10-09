<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $rateLimitKey = $this->rateLimitKey($request, $credentials['username']);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $auditLogger->security(
                'SEC_RATE_LIMIT_EXCEEDED',
                $credentials['username'],
                'Login rate limit exceeded.',
                $request
            );

            return response()->json([
                'success' => false,
                'code' => 'ERR_429_RATE_LIMIT',
                'message' => 'Terlalu banyak percobaan login. Silakan tunggu 15 menit.',
            ], 429);
        }

        $user = User::query()
            ->where('username', Str::lower($credentials['username']))
            ->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($rateLimitKey, 900);

            throw ValidationException::withMessages([
                'username' => ['Username atau Password salah!'],
            ])->status(401);
        }

        RateLimiter::clear($rateLimitKey);
        Auth::login($user);
        $request->session()->regenerate();

        if ($user->must_reset_password) {
            return response()->json([
                'success' => false,
                'code' => 'ERR_PASSWORD_RESET_REQUIRED',
                'message' => 'Password harus diganti sebelum melanjutkan.',
                'redirect' => '/password/activate',
            ], 403);
        }

        if (! $request->expectsJson()) {
            return redirect()->intended('/dashboard');
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'division_id' => $user->division_id,
            ],
        ]);
    }

    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (! $request->expectsJson()) {
            return redirect('/login');
        }

        return response()->json(['success' => true]);
    }

    private function rateLimitKey(Request $request, string $username): string
    {
        return Str::lower($username).'|'.$request->ip();
    }
}
