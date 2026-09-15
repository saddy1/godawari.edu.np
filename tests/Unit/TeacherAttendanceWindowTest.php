<?php

namespace Tests\Unit;

use App\Http\Controllers\TeachingLearning\TeacherWorkspaceController;
use App\Models\TeachingLearning\RoutineLesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TeacherAttendanceWindowTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function authorizeAt(string $time, bool $editing): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 '.$time));
        $lesson = new RoutineLesson;
        $lesson->day_of_week = 'Tuesday';
        $lesson->setRelation('plan', new class extends Model {
            protected $attributes = ['status' => 'published'];
        });
        $lesson->setRelation('period', new class extends Model {
            protected $attributes = ['starts_at' => '08:00:00', 'ends_at' => '08:50:00'];
        });
        $lesson->setRelation('endPeriod', null);
        $lesson->setRelation('groups', collect([(object) ['teacher_id' => 1]]));
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('canAccess')->andReturn(false);
        $method = new \ReflectionMethod(TeacherWorkspaceController::class, 'authorizeAttendance');
        $groups = $method->invoke(new TeacherWorkspaceController, $lesson, $user, $editing);
        $this->assertCount(1, $groups);
    }

    public function test_teacher_can_edit_during_class(): void
    {
        $this->authorizeAt('08:49:59', true);
    }

    public function test_editing_is_rejected_at_exact_class_end(): void
    {
        $this->expectException(HttpException::class);
        $this->authorizeAt('08:50:00', true);
    }

    public function test_teacher_can_view_after_class_ends(): void
    {
        $this->authorizeAt('09:30:00', false);
    }

    public function test_editing_is_rejected_before_opening(): void
    {
        $this->expectException(HttpException::class);
        $this->authorizeAt('07:49:59', true);
    }
}
