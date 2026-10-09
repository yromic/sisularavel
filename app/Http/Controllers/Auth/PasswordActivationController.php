<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PasswordActivationController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', Str::lower($validated['email']))
            ->first();

        if ($user) {
            $plainToken = Str::random(64);
            $user->forceFill([
                'password_reset_token' => hash('sha256', $plainToken),
                'password_reset_expires_at' => now()->addDay(),
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Jika akun terdaftar, tautan reset telah dikirimkan',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()
            ->where('password_reset_token', hash('sha256', $validated['token']))
            ->where('password_reset_expires_at', '>', now())
            ->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_reset_password' => false,
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ])->save();

        return response()->json(['success' => true]);
    }

    public function issueSecret(Request $request, User $user, AuditLogger $auditLogger): JsonResponse
    {
        $actor = $request->user();

        abort_unless($actor, 401);
        abort_unless($this->canIssueSecret($actor, $user), 403);

        $secret = Str::password(20);
        $expiresAt = now()->addDay();

        $user->forceFill([
            'password' => Hash::make($secret),
            'must_reset_password' => true,
            'password_reset_token' => null,
            'password_reset_expires_at' => $expiresAt,
        ])->save();

        $auditLogger->userEvent(
            'AUTH_PASSWORD_RESET',
            $actor,
            $user,
            'Administrative activation secret issued.',
            ['activation_secret' => '[REDACTED]']
        );

        return response()->json([
            'temporary_secret' => $secret,
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    public function activate(Request $request): Response
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_reset_password' => false,
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ])->save();

        $request->session()->regenerate();

        if (! $request->expectsJson()) {
            return redirect('/dashboard')->with('success', 'Password permanen berhasil diaktifkan.');
        }

        return response()->json(['success' => true]);
    }

    private function canIssueSecret(User $actor, User $target): bool
    {
        if ($actor->role === 'super_admin') {
            return true;
        }

        return $actor->role === 'division_admin'
            && $actor->division_id !== null
            && $actor->division_id === $target->division_id
            && $target->role !== 'super_admin';
    }
}
