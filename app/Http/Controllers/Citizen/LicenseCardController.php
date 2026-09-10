<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseCardController extends Controller
{
    public function __invoke(Request $request, License $license): View
    {
        abort_unless($license->user_id === $request->user()->id, 403);
        $license->load(['user.citizenProfile', 'reservoir.district', 'category']);

        return view('citizen.licenses.card', compact('license'));
    }
}
