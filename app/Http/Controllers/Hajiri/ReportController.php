<?php

namespace App\Http\Controllers\Hajiri;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Hajiri\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Http\Controllers\Hajiri\NepaliCalendarController;
use App\Models\Hajiri\AttendanceLogs;
use App\Models\Hajiri\Department;
use App\Models\Hajiri\HajiriSetting;
use App\Models\Hajiri\Leave;
use App\Models\Hajiri\LeaveRequest;
use App\Services\Hajiri\AttendanceWindow;
// use Carbon\Carbon;

class ReportController extends Controller
{
    private $users;
    private $npCal;
    private $attnLogs;
    private $holiday;
    private $departments;
    private $leaves;
    private $setting;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(User $users,AttendanceLogs $attnLogs,Holiday $holiday,Department $departments,Leave $leaves)
    {
        $this->users = $users;
        $this->attnLogs = $attnLogs;
        $this->npCal = new NepaliCalendarController();
        $this->holiday = $holiday;
        $this->departments = $departments;
        $this->leaves = $leaves;
        $this->setting = HajiriSetting::current();
    }

    public function index($year = '',$month = ''){
        // return false;
        $npCal = $this->npCal;
        $nowData = $this->getDateCalendar($year,$month);
        if(! Auth()->user()->isAdmin()){
            $users = $this->attendanceProfileQuery()->where('id','=',Auth()->user()->id)->orderBy('device_id')->get();
        }
        else
        {
            $users = $this->attendanceProfileQuery()->where('name','NOT LIKE','IOEPC%')->orderBy('device_id')->get();
        }
        $departments = $this->departments->where('alias',null)->get();
        // return $users;
        return view('hajiri.report.modal',compact('npCal','nowData','users','departments'));
    }

    public function report($year = '',$month = ''){
        if(! Auth()->user()->isAdmin()){ return 'Unauthorized'; }
        $nowData = $this->getDateCalendar($year,$month);
        $periodDate = $nowData['periodAD'];
        // ->whereBetween('device_id',[185,230])
        // return $users;
        // 
        // ->where('work_assigned_id',1)
        // ->whereIn('device_id',[201,194,193,191,199,192])
        $users = $this->attendanceProfileQuery(['designation','working_at'])->where('work_assigned_id',1)->where('employment_type_id','<>',4)->orderBy('sort')->get();
        $npCal =  $this->npCal;
        $labelDepart = '';
        return view('hajiri.report.index',compact('users','nowData','npCal','labelDepart'));
    }

    public function report_user($apd,$userid,$year = '',$month = ''){
        if($userid == ''){
            return ('ERROR');
        }
        // return [Auth()->user()->device_id,$userid];
        if(! Auth()->user()->isAdmin()){
            if(intval($userid) != Auth()->user()->device_id)
            {
            return "Unauthorized"; 
            }
        }

        $nowData = $this->getDateCalendar($year,$month);
        $periodDate = $nowData['periodAD'];
        
        // ->whereBetween('device_id',[185,230])
        // return $users;
        // 
        // ->where('work_assigned_id',1)
        // ->whereIn('device_id',[201,194,193,191,199,192])
        $users = $this->attendanceProfileQuery(['designation','working_at'])->whereIn('device_id',[$userid])->orderBy('sort')->get();
        $npCal =  $this->npCal;
        if($apd == 'ap')
        {
            $labelDepart = '';
            return view('hajiri.report.index_ap',compact('users','nowData','npCal','labelDepart'));
        }
        $labelDepart = '';
        return view('hajiri.report.index',compact('users','nowData','npCal','labelDepart'));
    }


    public function report_ap($year = '',$month = ''){
        if(! Auth()->user()->isAdmin()){ return 'Unauthorized'; }
        $nowData = $this->getDateCalendar($year,$month);
        $periodDate = $nowData['periodAD'];
        
        // ->whereBetween('device_id',[185,230])
        // return $users;
        // ->whereIn('device_id',[201,194,193,191,199,192])
        $users = $this->attendanceProfileQuery(['designation','working_at'])->where('work_assigned_id',2)->orderBy('sort')->get();
        $npCal =  $this->npCal;
        $labelDepart = '';
        return view('hajiri.report.index_ap',compact('users','nowData','npCal','labelDepart'));
    }

    public function report_type($apd,$typeid,$year = '',$month = ''){
        if($apd == ''){
            return ('ERROR');
        }

        $nowData = $this->getDateCalendar($year,$month);
        $periodDate = $nowData['periodAD'];
        
        // ->whereBetween('device_id',[185,230])
        // return $users;
        // 
        // ->where('work_assigned_id',1)
        // ->whereIn('device_id',[201,194,193,191,199,192])
        if($typeid==0)
        {
            $users = $this->attendanceProfileQuery(['designation','working_at'])->where('employment_type_id',4)->orderBy('sort')->get();
        }
        else
        {
            $users = $this->attendanceProfileQuery(['designation','working_at'])->where('work_assigned_id',$typeid)->where('employment_type_id','!=',4)->orderBy('sort')->get();
        }
        $npCal =  $this->npCal;
        if($apd == 'ap')
        {
            $labelDepart = '';
            return view('hajiri.report.index_ap',compact('users','nowData','npCal','labelDepart'));
        }
        $labelDepart = '';
        return view('hajiri.report.index',compact('users','nowData','npCal','labelDepart'));
    }
    
    public function report_department($apd,$typeid,$year = '',$month = ''){
        if($apd == ''){
            return ('ERROR');
        }

        $nowData = $this->getDateCalendar($year,$month);
        $periodDate = $nowData['periodAD'];
        
        $departments = $this->departments->find($typeid);
        // ->whereBetween('device_id',[185,230])
        // return $users;
        // 
        // ->where('work_assigned_id',1)
        // ->whereIn('device_id',[201,194,193,191,199,192])
        $users = $this->attendanceProfileQuery(['designation','working_at'])->where('hajiri_department_id',$typeid)->orderBy('sort')->get();
        $npCal =  $this->npCal;
        if($apd == 'ap')
        {
            $labelDepart = $departments['label'];
            return view('hajiri.report.index_ap',compact('users','nowData','npCal','labelDepart'));
        }
        $labelDepart = "{$departments['label']}";
        return view('hajiri.report.index',compact('users','nowData','npCal','labelDepart'));
    }

    public function getUserLogData(Request $request)
    {
        $device_id = $request->input('device_id');
        $user_ = $this->users->where('device_id','=',$device_id)->first();
        $user_id = $user_['id'];

        $year = $request->input('year');
        $month = $request->input('month');

        $nowData = $this->getDateCalendar($year,$month);
        $countCheckINOUT = 0;
        $countDSA = 0;
        $countAako = 0;
        $countHoliday = 0;
        $attendance = array();
        $dates = array();
        $hajiriArray = [];
        
        foreach($nowData['periodAD'] as $periodData){
            // $attendanceData = $this->attnLogs->where('user_id','LIKE',$device_id)->whereDate('at','=',"{$periodData->format('Y-m-d')}")->orderBy('at','asc')->get();
            $hajiriArray["{$periodData->format('Y-m-d')}"] = $dataH =  $this->getHajiriDetail($user_id,$periodData);
            $attendance[$periodData->format('Y-m-d')] = array(
                'in'=>$dataH['in'],
                'out'=>$dataH['out'],
                'holiday'=>$dataH['label'],
                'in_valid'=>$dataH['in_valid'],
                'out_valid'=>$dataH['out_valid'],
            );
        }
        // return $hajiriArray;
        // return $dsaArray;
        [$dsaInfo,$count] = $this->calculateDSA($hajiriArray,$dates,$user_id);
        // return $countDSA;
        return array('status'=>true,'data'=>$attendance,'length'=>$count,'aako'=>$countAako,'dsa'=>$count,'holiday'=>$countHoliday,'dsaTF'=>$dsaInfo);
    }
    
    public function calculateDSA($dsaArray,$dates,$user_id)
    {
        $lastDSA = [];
        foreach($dates as $date){
            $lastDSA[$date->format('Y-m-d')] = false;
        }
        
        $count = 0;
        $temp = [];
        $firstTime = true;
        foreach($dsaArray as $key=>$dsa)
        {
            // $temp[] = $dsa;
            $today = Carbon::createFromFormat('Y-m-d', $key);
            $tempToday = "{$today->format('Y-m-d')}";
            $yesterday_ = Carbon::createFromFormat('Y-m-d',$tempToday);
            $yesterday = $yesterday_->subDays(1);
            
            if($firstTime){
                $lastDSA["{$yesterday->format('Y-m-d')}"] = $this->checkPrevNextDSA($user_id,$today);
                $firstTime = false;
            }
            
            if($dsa['A'] == true || $dsa['L'] == true)
            {
                $count += 1;
                $lastDSA[$key] = true;
                continue;
            }
            
            if(($dsa['S'] == true || $dsa['H'] == true))
            {
                if($lastDSA["{$yesterday->format('Y-m-d')}"] == true)
                {
                    $count += 1;
                    $lastDSA[$key] = true;
                    continue;  
                }
                else
                {
                    if($this->checkPrevNextDSA($user_id,$today,'N')){
                        $count += 1;
                        $lastDSA[$key] = true;
                        continue;  
                    }
                }
            }
            $count += 0;
            $lastDSA[$key] = false;
        }
        // dd($lastDSA);
        return [$lastDSA,$count];
        // return $count;
    }
    
    
    public function getHajiriDetail($user_id,$date)
    {
        $labelToPrint = [];
        $user = $this->users->find($user_id);
        // dd($date);
        $attendanceData = $this->attnLogs->where('user_id','LIKE',$user['device_id'])->whereDate('at','=',"{$date->format('Y-m-d')}")->orderBy('at','asc')->get();
        $isWeekendHoliday = $this->setting->isWeekend($date->dayOfWeek);
        $isLeaveTaken = $this->approvedLeaveForDate($user_id, $date);
        $isHoliday = $this->holiday->whereDate('date',"{$date->format('Y-m-d')}")->where('status','=',true)->first();
        // return $date;
        if($isLeaveTaken != null){ $labelToPrint[] = $this->leaveLabel($isLeaveTaken); }
        if($isHoliday !=null){ $labelToPrint[] = $this->holidayLabel($isHoliday); }
        elseif($isWeekendHoliday){ $labelToPrint[] = $this->weekendLabel($date); }
        
        $attendanceSummary = AttendanceWindow::summary($attendanceData, $this->setting);
        $checkIN = $attendanceSummary['in']['time'];
        $checkOUT = $attendanceSummary['out']['time'];
        [$YY,$MM,$DD] = $this->getDateYYMM($date);
        // dd();
        [$flagDSA,$flagH] = $this->isTimeOk($checkIN,$checkOUT,$YY,$MM,$DD);
        return [
            'A'=>$flagDSA,
            'P'=>$flagH,
            'H'=>($isHoliday)?true:false,
            'L'=>($isLeaveTaken)?true:false,
            'S'=>$isWeekendHoliday,
            'in'=>$checkIN,
            'out'=>$checkOUT,
            'in_valid'=>$attendanceSummary['in']['valid'],
            'out_valid'=>$attendanceSummary['out']['valid'],
            'label'=>($labelToPrint == [])?null:$labelToPrint,
        ];
    }
    
    public function checkPrevNextDSA($user_id,$today,$type = 'P'){
        $dsa = false;
        for($i=1;$i<=20;$i++)
        {
            $key = "{$today->format('Y-m-d')}";
            $key_pn = Carbon::createFromFormat('Y-m-d',$key);
            if($type == 'P')
            {
                $pn = $key_pn->subDays($i);
            }
            else
            {
                $pn = $key_pn->addDays($i);
            }

            $detailsH = $this->getHajiriDetail($user_id,$pn);
            if($detailsH['H'] == true || $detailsH['S'] == true)
            {
                continue;
            }
            else
            {
                if($detailsH['A'] == true)
                {
                    $dsa = true;
                    break;
                }
                if($detailsH['L'] == true)
                {
                    $dsa = true;
                    break;
                }
                $dsa = false;
                break;
            }
        }
        return $dsa;
    }
    
    public function getCheckInOut($attendanceData){
        return AttendanceWindow::checkInOutTimes($attendanceData, $this->setting);
    }
    
    public function getDateYYMM($date_){
        $rDate = $this->npCal->ad_2_bs($date_->format('Y'),$date_->format('m'),$date_->format('d'));
        return [$rDate['year'],$rDate['month'],$rDate['date']];
    }
    
    
    public function isTimeOk($checkIN,$checkOUT,$YY,$MM, $DD){
        $flagDSA_IN = $flagDSA_OUT = false;
        $flagH_IN = $flagH_OUT = false;

        if ($checkIN === '-' || $checkOUT === '-') {
            return [false, false];
        }

        $start = Carbon::createFromFormat('H:i:s', $this->setting->office_start_time);
        $end = Carbon::createFromFormat('H:i:s', $this->setting->office_end_time);
        $lateLimit = $start->copy()->addMinutes($this->setting->late_grace_minutes);
        $earlyLimit = $end->copy()->subMinutes($this->setting->early_grace_minutes);

        if(strtotime($checkIN) <= strtotime($lateLimit->format('H:i:s'))){ $flagDSA_IN = true; }
        if(strtotime($checkIN) <= strtotime($lateLimit->copy()->addMinutes(20)->format('H:i:s'))){ $flagH_IN = true; }
        if(strtotime($checkOUT) >= strtotime($earlyLimit->format('H:i:s'))){ $flagH_OUT = true; }
        if(strtotime($checkOUT) >= strtotime($earlyLimit->format('H:i:s'))){ $flagDSA_OUT = true; }

        return [($flagDSA_IN&&$flagDSA_OUT),($flagH_IN&&$flagH_OUT)];
        
    }


    public function getUserLogDataAP(Request $request)
    {
        $device_id = $request->input('device_id');
        if(! Auth()->user()->isAdmin() && (string) $device_id !== (string) Auth()->user()->device_id){
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $year = $request->input('year');
        $month = $request->input('month');

        $nowData = $this->getDateCalendar($year,$month);
        $countCheckINOUT = 0;
        $countDSA = 0;
        $attendance = array();
        // DB::enableQueryLog(); // Enable query log
        foreach($nowData['periodAD'] as $periodData){
            $user_id = $this->users->where('device_id',$device_id)->first();
            // return $user_id;
            $attendanceData = $this->attnLogs->where('user_id','LIKE',$device_id)->whereDate('at','=',"{$periodData->format('Y-m-d')}")->get();
            
            $isWeekendHoliday = $this->setting->isWeekend($periodData->dayOfWeek);
            $isLeaveTaken = $user_id ? $this->approvedLeaveForDate($user_id->id, $periodData) : null;
            $isHoliday = $this->holiday->whereDate('date',"{$periodData->format('Y-m-d')}")->where('status','=',true)->first();
            // return $date;
            $labelToPrint  = [];
            if($isLeaveTaken != null){ $labelToPrint[] = $this->leaveLabel($isLeaveTaken); }
            if($isHoliday !=null){ $labelToPrint[] = $this->holidayLabel($isHoliday); }
            elseif($isWeekendHoliday){ $labelToPrint[] = $this->weekendLabel($periodData); }

            
            if(count($attendanceData) == 0){
                    if($labelToPrint != [])
                    {
                        $attendance[$periodData->format('Y-m-d')] = implode(' / ', $labelToPrint);
                    }
                    else{
                        $attendance[$periodData->format('Y-m-d')] = 'A';
                    } 
            }
            else
            {
                if(count($attendanceData) >= 1){
                    if($isWeekendHoliday){
                        $attendance[$periodData->format('Y-m-d')] = "W.P";
                    }
                    else{
                        $attendance[$periodData->format('Y-m-d')] = "P";
                    }
                }
                else
                {
                    if($isWeekendHoliday){
                        $attendance[$periodData->format('Y-m-d')] = "W";
                    }
                    else{
                        $attendance[$periodData->format('Y-m-d')] = "A";
                    } 
                }
                $countCheckINOUT++;
            }
        }
        

       return array('status'=>true,'data'=>$attendance,'length'=>intval($countCheckINOUT),'dsa'=>$countDSA);

    }
    
    private function str_replace_first($search, $replace, $subject)
    {
        $search = '/'.preg_quote($search, '/').'/';
        return preg_replace($search, $replace, $subject, 1);
    }

    private function holidayLabel($holiday): string
    {
        $label = trim((string) ($holiday->alias ?: $holiday->label));

        if (mb_strlen($label) <= 12) {
            return $label;
        }

        return mb_substr($label, 0, 11) . '.';
    }

    private function weekendLabel(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::SUNDAY => 'आइतबार',
            Carbon::SATURDAY => 'शनिबार',
            default => 'विदा',
        };
    }

    private function approvedLeaveForDate(int $userId, Carbon $date): ?LeaveRequest
    {
        return LeaveRequest::with('policy')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->first();
    }

    private function leaveLabel(LeaveRequest $leave): string
    {
        return $leave->policy?->short_code
            ?: $leave->policy?->name
            ?: 'Leave';
    }

    private function attendanceProfileQuery(array $relations = ['designation','employment'])
    {
        return $this->users->with($relations)
            ->whereNotNull('device_id')
            ->where('device_id', '<>', '');
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
