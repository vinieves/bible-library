<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Services\MemberProgressService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function index(MemberProgressService $progressService): View
    {
        $user = Auth::user();

        return view('members.progress.index', [
            'activities' => $progressService->activityTimeline($user),
        ]);
    }
}
