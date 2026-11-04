<?php

namespace App\Services;

use App\Models\Division;
use App\Models\User;
use Illuminate\Http\Request;

class DivisionContext
{
    public function activeDivisionId(Request $request, User $user): ?int
    {
        if ($user->role !== 'super_admin') {
            return $user->division_id;
        }

        $activeDivisionId = $request->session()->get('active_division_id');

        if ($activeDivisionId === null) {
            return null;
        }

        $activeDivisionId = (int) $activeDivisionId;

        // Auto-cleanup stale session if the division was deleted or deactivated
        $exists = Division::query()
            ->whereKey($activeDivisionId)
            ->where('status', 'active')
            ->exists();

        if (! $exists) {
            $request->session()->forget('active_division_id');

            return null;
        }

        return $activeDivisionId;
    }
}
