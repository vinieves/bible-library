<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Services\MemberProgressService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(MemberProgressService $progressService): View
    {
        $user = Auth::user();

        return view('members.dashboard', [
            'continueCards' => $progressService->continueCards($user),
            'lastActivityAt' => $progressService->lastActivityAt($user),
            'suggestedStartUrl' => $progressService->suggestedStartUrl($user),
            'suggestedStartLabel' => $progressService->suggestedStartLabel(),
        ]);
    }
}
