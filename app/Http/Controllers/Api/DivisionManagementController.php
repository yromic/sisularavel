<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DivisionManagementController extends Controller
{
    public function store(Request $request): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:divisions,code'],
            'name' => ['required', 'string', 'max:150'],
        ]);

        $division = Division::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => Str::upper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'status' => 'active',
            'created_by_user_id' => $request->user()->id,
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Divisi {$division->name} ({$division->code}) berhasil ditambahkan.");
        }

        return response()->json(['data' => $division], 201);
    }

    public function update(Request $request, Division $division): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:divisions,code,'.$division->id],
            'name' => ['required', 'string', 'max:150'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $division->update([
            'code' => Str::upper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'status' => $validated['status'] ?? $division->status,
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Divisi {$division->name} berhasil diperbarui.");
        }

        return response()->json(['data' => $division]);
    }

    public function updateStatus(Request $request, Division $division): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $division->update(['status' => $validated['status']]);

        if (! $request->expectsJson()) {
            return back()->with('success', "Status divisi {$division->name} diubah menjadi {$division->status}.");
        }

        return response()->json(['data' => $division->refresh()]);
    }

    public function destroy(Request $request, Division $division): Response
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $hasData = $division->users()->exists()
            || IncomingLetter::where('division_id', $division->id)->exists()
            || OutgoingLetter::where('division_id', $division->id)->exists()
            || Piagam::where('division_id', $division->id)->exists();

        if ($hasData) {
            $division->update(['status' => 'inactive']);

            if (! $request->expectsJson()) {
                return back()->with('error', "Divisi {$division->code} memiliki arsip surat/pengguna terkait, sehingga tidak dapat dihapus permanen. Status dialihkan menjadi Nonaktif.");
            }

            return response()->json(['success' => false, 'message' => 'Divisi memiliki data terkait, status dialihkan menjadi inactive.'], 422);
        }

        $division->delete();

        if (! $request->expectsJson()) {
            return redirect('/master/divisi')->with('success', "Divisi {$division->code} berhasil dihapus permanen.");
        }

        return response()->json(['success' => true]);
    }
}
