<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    public function security(string $eventCode, string $username, string $description, Request $request): void
    {
        AuditLog::query()->create([
            'user_id' => null,
            'username' => $username,
            'role' => 'guest',
            'division_id' => null,
            'event_code' => $eventCode,
            'ip_address' => (string) $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    public function userEvent(string $eventCode, User $actor, User $target, string $description, array $newValues = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $actor->division_id,
            'event_code' => $eventCode,
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'new_values' => $this->redact($newValues),
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        foreach (['password', 'password_reset_token', 'token', 'authorization', 'activation_secret'] as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }
}
