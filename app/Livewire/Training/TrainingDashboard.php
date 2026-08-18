<?php

namespace App\Livewire\Training;

use App\Services\Training\TrainingAcademyDashboardService;
use App\Services\Training\TrainingAssignmentService;
use App\Services\Training\TrainingCertificationService;
use App\Services\Training\TrainingCoursePlayerService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TrainingDashboard extends Component
{
    public function render(
        TrainingAcademyDashboardService $dashboard,
        TrainingCoursePlayerService $player,
        TrainingAssignmentService $assignments,
        TrainingCertificationService $certifications,
    ): View {
        $user = auth()->user();
        $data = $dashboard->dashboardFor($user);
        $activityMax = max(1, collect($data['monthly_activity'])->max(fn (array $row) => max($row['completed'], $row['started'])) ?? 1);

        $courseCatalog = $player->publishedCourses()->map(function ($module) use ($user, $player): array {
            $module->loadMissing('lessons');

            return [
                'module' => $module,
                'progress_percent' => $player->moduleProgressPercent($user, $module),
            ];
        });

        return view('livewire.training.training-dashboard', [
            'data' => $data,
            'activityMax' => $activityMax,
            'courseCatalog' => $courseCatalog,
            'assignmentRows' => array_slice($assignments->rowsForUser($user), 0, 4),
            'certificationRows' => array_slice($certifications->certificationRowsFor($user), 0, 4),
        ]);
    }
}
