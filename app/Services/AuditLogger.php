<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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

    public function crossDivisionBlocked(User $actor, Model $model, string $description): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $actor->division_id,
            'event_code' => 'SEC_CROSS_DIVISION_BLOCKED',
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    public function incomingLetterEvent(string $eventCode, User $actor, IncomingLetter $letter, string $description, array $newValues = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $letter->division_id,
            'event_code' => $eventCode,
            'auditable_type' => IncomingLetter::class,
            'auditable_id' => $letter->id,
            'new_values' => $this->redact($newValues),
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    public function outgoingLetterEvent(string $eventCode, User $actor, OutgoingLetter $letter, string $description, array $newValues = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $letter->division_id,
            'event_code' => $eventCode,
            'auditable_type' => OutgoingLetter::class,
            'auditable_id' => $letter->id,
            'new_values' => $this->redact($newValues),
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    public function attachmentEvent(string $eventCode, User $actor, Attachment $attachment, string $description, array $newValues = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $attachment->division_id,
            'event_code' => $eventCode,
            'auditable_type' => Attachment::class,
            'auditable_id' => $attachment->id,
            'new_values' => $this->redact($newValues),
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    public function piagamEvent(string $eventCode, User $actor, Piagam $piagam, string $description): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $piagam->division_id,
            'event_code' => $eventCode,
            'auditable_type' => Piagam::class,
            'auditable_id' => $piagam->id,
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    public function publicPiagamEvent(string $eventCode, Piagam $piagam, string $description): void
    {
        AuditLog::query()->create([
            'user_id' => null,
            'username' => 'public',
            'role' => 'guest',
            'division_id' => $piagam->division_id,
            'event_code' => $eventCode,
            'auditable_type' => Piagam::class,
            'auditable_id' => $piagam->id,
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    public function referenceEvent(string $eventCode, User $actor, Model $model, string $description, array $newValues = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $model->division_id ?? $actor->division_id,
            'event_code' => $eventCode,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'new_values' => $this->redact($newValues),
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    public function log(string $eventCode, User $actor, string $description, ?Model $model = null, array $newValues = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $model?->division_id ?? $actor->division_id,
            'event_code' => $eventCode,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'new_values' => $this->redact($newValues),
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function bulkEvent(string $eventCode, User $actor, string $auditableType, int $count, string $description, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'division_id' => $actor->division_id,
            'event_code' => $eventCode,
            'auditable_type' => $auditableType,
            'auditable_id' => null,
            'new_values' => $this->redact(array_merge(['count' => $count], $metadata)),
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
        foreach ([
            'password',
            'password_hash',
            'password_salt',
            'remember_token',
            'session_token',
            'authorization',
            'credit_card',
            'password_reset_token',
            'token',
            'activation_secret',
        ] as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }
}
