<?php

namespace App\Http\Controllers\Hajiri;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use DB;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use App\Models\Announcement;
use App\Models\Hajiri\AttendanceLogs;
use App\Models\Hajiri\Holiday;
use App\Models\Hajiri\HajiriSetting;
use App\Models\Hajiri\LeavePolicy;
use App\Models\Hajiri\LeaveRequest;
use App\Models\Hajiri\StaffCardRequest;
use App\Models\User;
use App\Http\Controllers\Hajiri\NepaliCalendarController;
use App\Services\Hajiri\AttendanceWindow;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    private $attnLogs;
    private $user;
    private $npCal;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AttendanceLogs $attnLogs,User $user)
    {
        $this->attnLogs = $attnLogs;
        $this->user = $user;
        $this->npCal = new NepaliCalendarController();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index($year = '',$month = '')
    {
        $authUser = auth()->user();

        if (! $authUser) {
            return redirect()->route('login');
        }

        $dateRef = Carbon::now();
        $dateToday = $dateRef->toDateString();
        // return $dateToday;
        $start = $dateToday.' 00:00:00';
        $end = $dateToday.' 23:59:59';
        $checkIN =  $checkOUT = null;
        $currentDeviceId = $authUser->device_id;
        // return $start;
        $attendanceLogs = $currentDeviceId
            ? $this->attnLogs->where('user_id', $currentDeviceId)->whereBetween('at', [$start, $end])->orderBy('at')->get()
            : collect();
        // return $attendanceLogs;
        $totalEmployee =  $this->user->where('status',1)->whereNotNull('device_id')->count();
        $totalPresentEmployee = DB::table('attendacelogs')
            ->whereBetween('at', [$start, $end])
            ->distinct('user_id')
            ->count('user_id');


        $setting = HajiriSetting::current();
        $attendanceSummary = AttendanceWindow::summary($attendanceLogs, $setting);
        $attendanceLogs = [
            'in' => $attendanceSummary['in']['record'],
            'out' => $attendanceSummary['out']['record'],
            'in_valid' => $attendanceSummary['in']['valid'],
            'out_valid' => $attendanceSummary['out']['valid'],
            'in_rule' => $attendanceSummary['in']['rule'],
            'out_rule' => $attendanceSummary['out']['rule'],
        ];
        $showPersonalAttendance = filled($currentDeviceId)
            && ($attendanceLogs['in'] || $attendanceLogs['out']);

        $nowData = $this->getDateCalendar($year, $month);

        $startAD = $nowData['periodAD'][0]->toDateString();
        $endAD   = end($nowData['periodAD'])->toDateString();
        $presentDates = $currentDeviceId
            ? DB::table('attendacelogs')
                ->where('user_id', $currentDeviceId)
                ->whereBetween('at', [$startAD . ' 00:00:00', $endAD . ' 23:59:59'])
                ->selectRaw('DATE(at) as log_date')
                ->distinct()
                ->pluck('log_date')
                ->flip()
            : collect();

        $attendancePerDay = [];
        foreach ($nowData['periodAD'] as $periodData) {
            $attendancePerDay[] = $presentDates->has($periodData->format('Y-m-d'));
        }

        $holidayRows = collect();
        if (Schema::hasTable('holiday')) {
            $holidayQuery = Holiday::whereDate('date', '>=', $startAD)
                ->whereDate('date', '<=', $endAD);

            if (Schema::hasColumn('holiday', 'status')) {
                $holidayQuery->where('status', true);
            }

            $holidayRows = $holidayQuery->get()
                ->keyBy(fn ($h) => $h->date->format('Y-m-d'));
        }

        // Approved leave days for the current user within this calendar month
        $leaveRowsByDate = [];
        $userId = auth()->id();
        if ($userId && Schema::hasTable('leave_requests') && Schema::hasTable('leave_policies')) {
            LeaveRequest::with('policy')
                ->where('user_id', $userId)
                ->where('status', 'approved')
                ->where('start_date', '<=', $endAD)
                ->where('end_date',   '>=', $startAD)
                ->get()
                ->each(function ($req) use (&$leaveRowsByDate, $startAD, $endAD) {
                    $cursor = $req->start_date->copy();
                    while ($cursor->lte($req->end_date)) {
                        $key = $cursor->format('Y-m-d');
                        if ($key >= $startAD && $key <= $endAD) {
                            $leaveRowsByDate[$key] = $req;
                        }
                        $cursor->addDay();
                    }
                });
        }

        $npCal   = $this->npCal;
        $empolyeeData = ['total' => $totalEmployee, 'present' => $totalPresentEmployee];

        // Extra data for non-admin employee dashboard
        $latestNotices  = [];
        $leaveBalances  = [];
        $pendingCardReq = false;

        if ($userId && ! $authUser->isAdmin()) {
            $latestNotices = Announcement::where('is_published', true)
                ->latest()
                ->limit(5)
                ->get();

            $user = $authUser;
            $userCategory = null;
            if ($user->working_at) {
                $label = strtolower($user->working_at->label ?? '');
                $userCategory = str_contains($label, 'academic') || str_contains($label, 'teach')
                    ? 'teaching' : 'non_teaching';
            }
            [$fyStart, $fyEnd] = $this->currentFiscalYear();

            if (Schema::hasTable('leave_policies') && Schema::hasTable('leave_requests')) {
                $leaveBalances = LeavePolicy::where('is_active', true)
                    ->where(function ($q) use ($userCategory) {
                        $q->where('applicable_to', 'all')
                          ->orWhere('applicable_to', $userCategory);
                    })
                    ->get()
                    ->map(function ($policy) use ($userId, $fyStart, $fyEnd) {
                        $query = LeaveRequest::where('user_id', $userId)
                            ->where('leave_policy_id', $policy->id)
                            ->where('status', 'approved');
                        if ($policy->period_type === 'annual') {
                            $query->whereBetween('start_date', [$fyStart, $fyEnd]);
                        }
                        $used = $query->sum('days_count');
                        return [
                            'policy'    => $policy,
                            'used'      => $used,
                            'remaining' => max(0, $policy->days_allowed - $used),
                            'pct'       => $policy->days_allowed > 0
                                ? min(100, round($used / $policy->days_allowed * 100))
                                : 0,
                        ];
                    });
            }

            if (Schema::hasTable('staff_card_requests')) {
                $pendingCardReq = StaffCardRequest::where('user_id', $userId)
                    ->whereIn('status', ['pending', 'approved', 'printed'])
                    ->exists();
            }
        }

        return view('hajiri.logs.index', compact(
            'attendanceLogs', 'nowData', 'attendancePerDay', 'npCal',
            'empolyeeData', 'holidayRows', 'leaveRowsByDate', 'setting',
            'latestNotices', 'leaveBalances', 'pendingCardReq', 'showPersonalAttendance'
        ));
    }

    public function log_add()
    {
        $period = CarbonPeriod::create('2020-06-20', '2021-06-20');

        // Iterate over the period
        foreach ($period as $date) {
            DB::table('attendacelogs')->insert([
                'user_id' => rand(1,7),
                'at' => $date->format('Y-m-d').' 09:'.rand(4,5).rand(0,9).':00',
            ]);
            DB::table('attendacelogs')->insert([
                'user_id' => rand(1,7),
                'at' => $date->format('Y-m-d').' 17:0'.rand(0,9).':00',
            ]);
        }
    }

    private function currentFiscalYear(): array
    {
        $today  = Carbon::now();
        $bsToday = $this->npCal->ad_2_bs($today->year, $today->month, $today->day);
        $bsMonth = (int) $bsToday['month'];
        $bsYear  = (int) $bsToday['year'];
        $fyYear  = $bsMonth >= 4 ? $bsYear : $bsYear - 1;

        $start   = $this->npCal->bs_2_ad($fyYear, 4, 1);
        $fyStart = sprintf('%04d-%02d-%02d', $start['year'], $start['month'], $start['date']);

        $endYear     = $fyYear + 1;
        $ashadhDays  = $this->npCal->bs[intval(substr((string)$endYear, -2))][3];
        $end         = $this->npCal->bs_2_ad($endYear, 3, $ashadhDays);
        $fyEnd       = sprintf('%04d-%02d-%02d', $end['year'], $end['month'], $end['date']);

        return [$fyStart, $fyEnd];
    }

    private function getDateCalendar($year,$month)
    {
        if($year != '' && $month != ''){
            $adDate = $this->npCal->bs_2_ad($year,$month,'10');
            $dateYYMMDD = implode('-',array($adDate['year'],$adDate['month'],$adDate['date']));
            $dateRef = Carbon::parse("{$dateYYMMDD}");
        }
        else
        {
            $dateRef = Carbon::now();
        }
        
        $dateToday = $dateRef->toDateString();
        list($y,$m,$d) = explode('-',$dateToday);
        $firstDayofMonth = $this->npCal->ad_2_bs($y,$m,$d);
        $forCheckFirstDay = $this->npCal->bs_2_ad($firstDayofMonth['year'],$firstDayofMonth['month'],"01");
        $lastDayofMonth = $this->npCal->bs[intval(substr($firstDayofMonth['year'],2,4))][intval($firstDayofMonth['month'])];
        $forCheckLastDay = $this->npCal->bs_2_ad($firstDayofMonth['year'],$firstDayofMonth['month'],$lastDayofMonth);
        
        return array(
            'yearBS'=>intval($firstDayofMonth['year']),
            'monthBS'=>intval($firstDayofMonth['month']),
            'nmonthBS'=>$firstDayofMonth['nmonth'],            
            'firstBS'=>1,
            'lastBS'=>intval($lastDayofMonth),
            'yearAD'=>intval($forCheckFirstDay['year']),
            'monthAD'=>intval($forCheckFirstDay['month']),
            'nmonthAD_A'=>$forCheckFirstDay['nmonth'],
            'firstAD'=>intval($forCheckFirstDay['date']),
            'lastAD'=>intval($forCheckLastDay['date']),
            'firstDay'=>$forCheckFirstDay['num_day'],
            'lastDay'=>$forCheckLastDay['num_day'],
            'nmonthAD_B'=>$forCheckLastDay['nmonth'],
            'periodAD'=> CarbonPeriod::create("{$forCheckFirstDay['year']}-{$forCheckFirstDay['month']}-{$forCheckFirstDay['date']}", "{$forCheckLastDay['year']}-{$forCheckLastDay['month']}-{$forCheckLastDay['date']}")->toArray()
        );
    }
}
