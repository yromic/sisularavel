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

        $divisionId = $request->input('division_id');

        if (empty($divisionId) || $divisionId === 'global') {
            $request->session()->forget('active_division_id');

            if (! $request->expectsJson()) {
                return back()->with('success', 'Konteks divisi dialihkan ke Semua Divisi (Global).');
            }

            return response()->json([
                'success' => true,
                'active_division_id' => null,
            ]);
        }

        $validated = $request->validate([
            'division_id' => ['integer', 'exists:divisions,id'],
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
