<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ContactMessage;
use App\Models\Admission; // The new model we just made
use App\Models\LibraryLoan;
use App\Models\Work\WorkTask;
use App\Models\Work\WorkTaskSubmission;
use App\Models\User;
use App\Services\ModuleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        if ($user?->isTeacher() && ModuleService::enabled('teaching_learning')) {
            return redirect()->route('admin.teacher.workspace');
        }

        if (! $user?->canAccess(['dashboard.admin', 'dashboard.view', 'dashboard.financial'])) {
            $landingUrl = $this->firstAccessibleModuleUrl($user);

            abort_unless($landingUrl, 403, 'No accessible ERP module is assigned to this account.');

            return redirect()->to($landingUrl);
        }

        $today = Carbon::today();
        $workTaskCount = 0;

        if (Schema::hasTable('work_tasks') && Schema::hasTable('work_task_submissions') && $user?->canAccess(['work-tasks.view', 'work-tasks.submit', 'work-tasks.review', 'work-tasks.create'])) {
            $workTaskCount = $user->canAccess(['work-tasks.review', 'work-tasks.create'])
                ? WorkTaskSubmission::where('status', 'submitted')->count()
                : WorkTask::pendingForUser($user)->count();
        }

        // 1. TOP STATS CARDS
        $stats = [
            'new_admissions'  => Admission::where('status', 'Pending')->count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),
            'upcoming_events' => Announcement::where('type', 'event')->where('event_date', '>=', $today)->count(),
            'active_notices'  => Announcement::whereIn('type', ['notice', 'news'])->where('is_published', true)->count(),
            'work_reviews'    => $workTaskCount,
        ];

        // 2. DASHBOARD PANELS (Fetch latest 5 of everything)
        $recentAdmissions = Admission::latest()->take(5)->get();
        
        $recentMessages = ContactMessage::where('is_read', false)->latest()->take(5)->get();
        
        $nextEvents = Announcement::where('type', 'event')
            ->where('event_date', '>=', $today)
            ->orderBy('event_date', 'asc')
            ->take(5)
            ->get();
            
        $recentPosts = Announcement::whereIn('type', ['notice', 'news'])
            ->latest()
            ->take(5)
            ->get();

        // Library stats (only if module table exists)
        $libraryStats = null;
        if (Schema::hasTable('library_loans') && \App\Services\ModuleService::enabled('library')) {
            $libraryStats = [
                'overdue'   => LibraryLoan::where('status', 'issued')->whereDate('due_date', '<', now()->toDateString())->count(),
                'issued'    => LibraryLoan::where('status', 'issued')->count(),
                'fine_due'  => LibraryLoan::sum(\Illuminate\Support\Facades\DB::raw('GREATEST(fine_amount - fine_paid, 0)')),
            ];
        }

        return view('backend.dashboard', compact('stats', 'recentAdmissions', 'recentMessages', 'nextEvents', 'recentPosts', 'libraryStats'));
    }

    /**
     * Send restricted admin roles to a permitted module without exposing the
     * Website Dashboard statistics that they were not granted access to.
     */
    private function firstAccessibleModuleUrl(User $user): ?string
    {
        if (ModuleService::enabled('teaching_learning') && ($user->isTeacher() || $user->canAccess([
            'examinations.marks.enter', 'teaching-learning.routine.manage',
        ]))) {
            return route('admin.teacher.workspace');
        }

        if (ModuleService::enabled('hr')) {
            if ($user->canAccess('hr.members.view')) {
                return route('admin.hr.members.index');
            }
            if ($user->canAccess('hr.members.create')) {
                return route('admin.hr.members.create');
            }
        }

        if (ModuleService::enabled('card')) {
            if ($user->canAccess('students.view')) {
                return route('students.index');
            }
            if ($user->canAccess('students.create')) {
                return route('students.create');
            }
            if ($user->canAccess(['cards.view', 'cards.print'])) {
                return route('bulk.index');
            }
            if ($user->canAccess('students.card-request')) {
                return route('admin.card-requests');
            }
            if ($user->canAccess('card-settings.view')) {
                return route('settings.index');
            }
        }

        if (ModuleService::enabled('hajiri') && ($user->device_id || $user->canAccess([
            'attendance.view', 'attendance.report', 'reports.view', 'users.view', 'leaves.view', 'settings.view',
        ]))) {
            return route('hajiri.home');
        }

        if (ModuleService::enabled('learning')) {
            if ($user->canAccess('learning.courses.view')) {
                return route('admin.learning.dashboard');
            }
            if ($user->canAccess('learning.resources.view')) {
                return route('admin.learning.resources.index');
            }
            if ($user->canAccess('learning.students.view')) {
                return route('admin.learning.students.index');
            }
        }

        if (ModuleService::enabled('store') && $user->canAccess('store.view')) {
            return route('admin.store.dashboard');
        }

        if (ModuleService::enabled('library')) {
            if ($user->canAccess('library.view')) {
                return route('admin.library.dashboard');
            }
            if ($user->canAccess('library.issue')) {
                return route('admin.library.issue.index');
            }
            if ($user->canAccess('library.create')) {
                return route('admin.library.books.create');
            }
        }

        if (ModuleService::enabled('billing')) {
            if ($user->canAccess('billing.view')) {
                return route('admin.billing.index');
            }
            if ($user->canAccess('billing.create')) {
                return route('admin.billing.create');
            }
        }

        if (ModuleService::enabled('work_tasks')) {
            if ($user->canAccess('work-tasks.view')) {
                return route('admin.work-tasks.index');
            }
            if ($user->canAccess('work-tasks.review')) {
                return route('admin.work-tasks.review-queue.index');
            }
            if ($user->canAccess('work-groups.manage')) {
                return route('admin.work-tasks.groups.index');
            }
            if ($user->canAccess('work-checklists.manage')) {
                return route('admin.work-tasks.checklists.index');
            }
        }

        // Website tools that have their own list page but not Dashboard access.
        $websitePages = [
            'users.view' => 'admin.users.index',
            'students.admission' => 'admin.admissions.index',
            'announcements.view' => 'admin.announcements.index',
            'faculty.view' => 'admin.faculty.index',
            'media.view' => 'admin.media.index',
            'popups.view' => 'popups.index',
            'testimonials.view' => 'testimonials.index',
            'vacancies.view' => 'vacancies.index',
            'contacts.view' => 'contacts.index',
        ];

        foreach ($websitePages as $permission => $routeName) {
            if ($user->canAccess($permission)) {
                return route($routeName);
            }
        }

        return null;
    }
}
