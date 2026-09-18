<?php

namespace App\Services;

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

        return $activeDivisionId === null ? null : (int) $activeDivisionId;
    }
}
