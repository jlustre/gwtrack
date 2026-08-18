<?php

namespace Tests\Feature;

use App\Livewire\Admin\Training\AdminCourseEditor;
use App\Livewire\Admin\Training\AdminCourseIndex;
use App\Livewire\Admin\Training\AdminPathEditor;
use App\Livewire\Admin\Training\AdminPathIndex;
use App\Livewire\Admin\Training\AdminTrainingHub;
use App\Models\TrainingCategory;
use App\Models\TrainingModule;
use App\Models\TrainingPath;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingAdminBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TrainingAcademySeeder::class);

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('trainer');
    }

    public function test_trainer_can_view_training_studio_hub(): void
    {
        $this->actingAs($this->trainer)
            ->get(route('admin.training.index'))
            ->assertOk()
            ->assertSeeText('Training Content Studio');

        Livewire::actingAs($this->trainer)
            ->test(AdminTrainingHub::class)
            ->assertSee('Published courses')
            ->assertSee('Course Builder');
    }

    public function test_trainer_can_create_course_and_add_lesson(): void
    {
        $category = TrainingCategory::query()->firstOrFail();

        Livewire::actingAs($this->trainer)
            ->test(AdminCourseIndex::class)
            ->set('trainingCategoryId', $category->id)
            ->set('title', 'Builder Test Course')
            ->set('description', 'Created from admin builder test')
            ->set('courseType', 'video')
            ->set('difficulty', 'beginner')
            ->set('isPublished', false)
            ->call('createCourse')
            ->assertRedirect();

        $module = TrainingModule::query()->where('title', 'Builder Test Course')->first();
        $this->assertNotNull($module);

        Livewire::actingAs($this->trainer)
            ->test(AdminCourseEditor::class, ['module' => $module])
            ->set('lessonTitle', 'Intro Lesson')
            ->set('lessonType', 'video')
            ->set('lessonContent', 'Welcome')
            ->set('lessonSortOrder', 10)
            ->call('saveLesson')
            ->assertSet('lessonTitle', '');

        $this->assertDatabaseHas('training_lessons', [
            'training_module_id' => $module->id,
            'title' => 'Intro Lesson',
        ]);
    }

    public function test_trainer_can_create_path_and_attach_modules(): void
    {
        $publishedModule = TrainingModule::query()->published()->firstOrFail();

        Livewire::actingAs($this->trainer)
            ->test(AdminPathIndex::class)
            ->set('name', 'Custom Builder Path')
            ->set('description', 'Test path from builder')
            ->set('audience', 'associate')
            ->call('createPath')
            ->assertRedirect();

        $path = TrainingPath::query()->where('name', 'Custom Builder Path')->first();
        $this->assertNotNull($path);

        Livewire::actingAs($this->trainer)
            ->test(AdminPathEditor::class, ['path' => $path])
            ->set('attachModuleId', $publishedModule->id)
            ->call('addModuleRow')
            ->call('savePath');

        $path->refresh()->load('modules');
        $this->assertTrue($path->modules->contains('id', $publishedModule->id));
    }

    public function test_member_cannot_access_training_studio(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('admin.training.index'))
            ->assertForbidden();
    }

    public function test_team_leader_without_manage_training_cannot_access_studio(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $this->actingAs($leader)
            ->get(route('admin.training.index'))
            ->assertForbidden();
    }
}
