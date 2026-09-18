<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function update(Request $request, string $key): JsonResponse
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $validated = $request->validate([
            'value' => ['nullable', 'string'],
        ]);

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $validated['value'] ?? null]
        );

        return response()->json(['data' => $setting]);
    }
}
