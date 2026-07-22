<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Services\LoginStreakService;
use App\Services\MemberProgressService;
use App\Services\VerseOfTheDayService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        MemberProgressService $progressService,
        VerseOfTheDayService $verseService,
        LoginStreakService $loginStreakService,
    ): View {
        $user = Auth::user();

        return view('members.dashboard', [
            'continueCards' => $progressService->continueCards($user),
            'suggestedStartUrl' => $progressService->suggestedStartUrl($user),
            'suggestedStartLabel' => $progressService->suggestedStartLabel(),
            'monthlyGoal' => $progressService->monthlyGoalProgress($user),
            'verseOfTheDay' => $verseService->today(),
            'loginStreak' => $loginStreakService->for($user),
        ]);
    }
}
