<?php

namespace App\Http\Controllers;

use App\Services\DashboardActivityService;
use App\Services\DashboardHomeService;
use App\Services\DashboardOverviewService;
use App\Services\DashboardStatDetailsService;
use App\Services\DashboardStatsService;
use App\Services\ProfileCompletionService;
use App\Services\ProfilePhotoService;
use App\Support\LocationOptions;
use App\Support\ProfileCompletionFormState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $stats,
        private readonly DashboardStatDetailsService $statDetails,
        private readonly DashboardOverviewService $overview,
        private readonly DashboardActivityService $activity,
        private readonly DashboardHomeService $home,
    ) {}

    public function index(Request $request, ProfileCompletionService $profileCompletion, ProfilePhotoService $profilePhotos): View
    {
        $user = $request->user();
        $user->loadMissing([
            'profile',
            'rank',
            'team',
            'sponsor',
        ]);

        return view('dashboard', [
            'user' => $user,
            'statCards' => $this->stats->statCards($user),
            'overview' => $this->overview->forUser($user),
            'home' => $this->home->forUser($user),
            'activity' => $this->activity->panelsFor($user),
            'profileCompletion' => $profileCompletion->snapshot($user),
            'profileCompletionForm' => ProfileCompletionFormState::forUser($user),
            'profileCompletionPhotoPreview' => $profilePhotos->hasStagedPreview($user)
                ? $profilePhotos->stagedPreviewUrl($user)
                : $user->profilePhotoUrl(),
            'hasStagedProfilePhoto' => $profilePhotos->hasStagedPreview($user),
            'locationOptions' => LocationOptions::forPortal(),
            'forceProfileCompletionModal' => (bool) $request->session()->pull('show_profile_completion_modal', false),
        ]);
    }

    public function statDetails(Request $request, string $type): JsonResponse
    {
        $context = $request->string('context', 'team')->toString();

        abort_unless($this->statDetails->isValidType($type), 404);
        abort_unless($this->statDetails->isValidContext($type, $context), 404);

        return response()->json(
            $this->statDetails->detailsFor($request->user(), $type, $context)
        );
    }
}
