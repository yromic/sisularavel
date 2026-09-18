<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use App\Services\DivisionContext;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotentMutation
{
    public function __construct(private readonly DivisionContext $divisionContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('Idempotency-Key', '');
        $validator = Validator::make(
            ['Idempotency-Key' => $key],
            ['Idempotency-Key' => ['required', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i']],
            ['Idempotency-Key.regex' => 'Header Idempotency-Key harus berupa format UUID v4 valid.']
        );

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $divisionId = $user === null ? null : $this->divisionContext->activeDivisionId($request, $user);
        $requestHash = $this->requestHash($request);

        $existing = IdempotencyKey::query()
            ->where('key', $key)
            ->where('user_id', $user?->id)
            ->first();

        if ($existing !== null) {
            return $this->responseForExistingKey($existing, $requestHash);
        }

        try {
            $record = IdempotencyKey::query()->create([
                'key' => $key,
                'user_id' => $user?->id,
                'division_id' => $divisionId,
                'request_hash' => $requestHash,
                'response_code' => 0,
                'response_body' => '',
                'expires_at' => now()->addDay(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $existing = IdempotencyKey::query()
                ->where('key', $key)
                ->where('user_id', $user?->id)
                ->firstOrFail();

            return $this->responseForExistingKey($existing, $requestHash);
        }

        try {
            /** @var Response $response */
            $response = $next($request);
        } catch (\Throwable $exception) {
            $record->delete();

            throw $exception;
        }

        if ($response instanceof JsonResponse) {
            $record->forceFill([
                'response_code' => $response->getStatusCode(),
                'response_body' => (string) $response->getContent(),
            ])->save();
        }

        return $response;
    }

    private function responseForExistingKey(IdempotencyKey $record, string $requestHash): JsonResponse
    {
        if (! hash_equals($record->request_hash, $requestHash)) {
            return response()->json([
                'success' => false,
                'code' => 'ERR_409_IDEMPOTENCY_CONFLICT',
                'message' => 'Idempotency key telah digunakan untuk permintaan berbeda',
            ], 409);
        }

        if ($record->response_code === 0) {
            return response()->json([
                'success' => false,
                'code' => 'ERR_409_CONCURRENT_REQUEST',
                'message' => 'Permintaan dengan Idempotency-Key ini sedang diproses.',
            ], 409);
        }

        return response()->json(json_decode($record->response_body, true), $record->response_code);
    }

    private function requestHash(Request $request): string
    {
        $payload = $request->all();
        $this->ksortRecursive($payload);

        return hash('sha256', json_encode([
            'method' => $request->method(),
            'route' => $request->route()?->uri(),
            'payload' => $payload,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function ksortRecursive(array &$value): void
    {
        ksort($value);

        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->ksortRecursive($item);
            } elseif ($item instanceof UploadedFile) {
                $item = [
                    'name' => $item->getClientOriginalName(),
                    'size' => $item->getSize(),
                    'mime' => $item->getMimeType(),
                    'hash' => hash_file('sha256', $item->getRealPath()),
                ];
            }
        }
    }
}
