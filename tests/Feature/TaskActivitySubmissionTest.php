<?php

namespace Tests\Feature;

use App\Models\TaskUser;
use App\Models\User;
use App\Support\TaskUserAttributes;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskActivitySubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_activity_comment_without_full_page_reload(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $task = TaskUser::query()->create(TaskUserAttributes::forTask('Prospect Follow-Up', 'Follow up with prospect', [
            'assignee_id' => $user->id,
            'assignor_id' => $user->id,
            'priority' => 'medium',
            'status' => 'to_do',
            'related_module' => 'Prospects',
            'related_person' => 'Alex Rivera',
            'due_date' => now()->addDay()->toDateString(),
            'progress' => 0,
        ], 'Call back tomorrow.'));

        $this->actingAs($user)
            ->postJson(route('tasks.comments.store', $task), [
                'body' => 'Left voicemail and sent follow-up text.',
            ])
            ->assertCreated()
            ->assertJsonPath('comment.text', 'Left voicemail and sent follow-up text.')
            ->assertJsonPath('comment.author', $user->name);

        $this->assertDatabaseHas('task_user_comments', [
            'task_user_id' => $task->id,
            'user_id' => $user->id,
            'body' => 'Left voicemail and sent follow-up text.',
        ]);
    }
}
