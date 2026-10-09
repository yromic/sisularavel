<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DivisionContextController extends Controller
{
    public function switch(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user?->role === 'super_admin', 403);

        $validated = $request->validate([
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
        ]);

        $division = Division::query()
            ->whereKey($validated['division_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $request->session()->put('active_division_id', $division->id);

        if (! $request->expectsJson()) {
            return back()->with('success', "Konteks divisi dialihkan ke {$division->name}.");
        }

        return response()->json([
            'success' => true,
            'active_division_id' => $division->id,
        ]);
    }
}
