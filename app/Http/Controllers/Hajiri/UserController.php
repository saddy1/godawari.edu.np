<?php

namespace App\Http\Controllers\Hajiri;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;


use App\Models\User;
use App\Models\Hajiri\Designation;
use App\Models\Hajiri\EmploymentType;
use App\Models\Hajiri\WorkAssigned;
use App\Models\Hajiri\Department;

class UserController extends Controller
{
    private $user;
    private $desig;
    private $employmentType;
    private $work_assigned;
    private $department;

    public function __construct(User $user, Designation $desig,EmploymentType $employmentType, WorkAssigned $work_assigned, Department $department)
    {
        $this->user = $user;
        $this->desig = $desig;
        $this->employmentType = $employmentType;
        $this->work_assigned = $work_assigned;
        $this->department = $department;

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $type = 'Employee';

        $users = $this->staffProfileQuery()->orderBy('sort')->orderBy('name')->get();

        $desig = $this->desig->get();
        $employmentType = $this->employmentType->get();
        $work_assigned = $this->work_assigned->get();

        $sort = false;
        $allowEdit = true;

        return view('hajiri.users.index',compact('type','users','desig','work_assigned','sort','allowEdit'));
    }

    public function index_custom($typeid,$sort = null)
    {
        if($typeid == 'adminstration')
        {
            $type_id = 1;
            $type = 'Administration Employee';
        }
        elseif($typeid == 'academic')
        {
            $type_id = 2;
            $type = 'Academic Employee';
        }

        $users = $this->user->with('designation','employment','student')
            ->where('work_assigned_id',$type_id)
            ->where('status',1)
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        $desig = $this->desig->get();
        $employmentType = $this->employmentType->get();
        $work_assigned = $this->work_assigned->get();

        $sort = false;
        $allowEdit = false;
        return view('hajiri.users.index',compact('type','users','desig','work_assigned','sort','type_id','allowEdit'));
    }

    public function index_inactive()
    {
        $type = 'InActive Employee';
        $users = $this->user->with('designation','employment','student')->where('status',0)->orderBy('sort')->get();

        $desig = $this->desig->get();
        $employmentType = $this->employmentType->get();
        $work_assigned = $this->work_assigned->get();

        $sort = false;
        return view('hajiri.users.index',compact('type','users','desig','work_assigned','sort'));
    }

    public function index_sorting()
    {
        $type = 'InActive Employee';
        $users = $this->staffProfileQuery()->orderBy('sort')->orderBy('name')->get();
        // return $users;

        $desig = $this->desig->get();
        $employmentType = $this->employmentType->get();
        $work_assigned = $this->work_assigned->get();

        $sort = true;
        return view('hajiri.users.index',compact('type','users','desig','work_assigned','sort'));
    }


    public function filter(Request $request)
    {
        $designation = $request->designation;
        $work_assigned = $request->work_assigned;


        $type = 'Filtered Employee';
        $users = $this->staffProfileQuery()->orderBy('sort')->orderBy('name')->get();
        if($work_assigned != '')
        {
            $users = $this->user->with('designation','employment','student')
                ->where('work_assigned_id','LIKE',$work_assigned)
                ->orderBy('sort')
                ->orderBy('name')
                ->get();
        }

        $desig = $this->desig->get();
        $employmentType = $this->employmentType->get();
        $work_assigned = $this->work_assigned->get();

        $sort = true;

        return view('hajiri.users.index',compact('type','users','desig','work_assigned','sort'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (\App\Services\ModuleService::enabled('hr') && auth()->user()?->canAccess('hr.members.create')) {
            return redirect()->route('admin.hr.members.create')->with('success', 'Create employees from HR People Master. Hajiri will use the same synced record.');
        }

        $desig = $this->desig->get();
        $department = $this->department->get();
        $employmentType = $this->employmentType->get();
        $work_assigned = $this->work_assigned->get();

        return view('hajiri.users.create', compact('desig', 'department', 'employmentType', 'work_assigned'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:150',
            'email' => 'required|unique:users,email|max:150',
            'phone'=>'required|min:10',
            'province'=>'required|max:150',
            'district'=>'required|max:150',
            'municipal'=>'required|max:150',

            'work_assigned_id'=>'required|max:150',
            'hajiri_department_id'=>'nullable|max:150',
            'employment_type_id'=>'required|max:150',

            'device_id'=>'required|unique:users,device_id',
            'designation_id'=>'required|max:150',
            'password'=>'required|confirmed|max:20',
            'status'=>'required|integer|between:0,1',
        ]);

        $reqD = $request->all();

        $userObj = $this->user;

        $userObj->name = $request['name'];
        $userObj->email = $request['email'];
        $userObj->password =  Hash::make($request['password']);
        $userObj->phone = $request['phone'];
        $userObj->province = $request['province'];
        $userObj->district = $request['district'];
        $userObj->municipal = $request['municipal'];
        $userObj->device_id = $request['device_id'];

        $userObj->designation_id = $request['designation_id'];
        $userObj->employment_type_id = $request['employment_type_id'];
        $userObj->work_assigned_id = $request['work_assigned_id'];
        $userObj->hajiri_department_id = $request['hajiri_department_id'];
        $userObj->status = (int)$request['status'];

        if($userObj->save())
        {
            if (Role::where('name', 'teacher')->where('guard_name', 'web')->exists()) {
                $userObj->assignRole('teacher');
            }

            return redirect()->to(route('hajiri.users.edit',$userObj))->with('message', "Update to User was successful!!");
        }
        else
        {
            return redirect()->back()->withError('message', 'Sorry!! Update Failed!');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = $this->user->find($id);
        $desig = $this->desig->get();
        $department = $this->department->get();
        $employmentType = $this->employmentType->get();
        $work_assigned = $this->work_assigned->get();

        return view('hajiri.users.edit', compact('user', 'desig', 'department', 'employmentType', 'work_assigned'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|max:150',
            'device_id' => ['required', 'max:150', Rule::unique('users', 'device_id')->ignore($id)],
            'email' => ['required', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'phone'=>'required|min:10',
            'province'=>'required|max:150',
            'district'=>'required|max:150',
            'municipal'=>'required|max:150',

            'work_assigned_id'=>'required|max:150',
            'hajiri_department_id'=>'nullable|max:150',
            'designation_id'=>'required|max:150',
            'employment_type_id'=>'required|max:150',

            'status'=>'required|integer|between:0,1',
        ]);

        $user = $this->user->find($id);
        $oldDeviceId = $user ? $user->device_id : null;
        $newDeviceId = $request['device_id'];

        $dataUpdate = [];
        $dataUpdate['name'] = $request['name'];
        $dataUpdate['email'] = $request['email'];
        $dataUpdate['phone'] = $request['phone'];
        $dataUpdate['province'] = $request['province'];
        $dataUpdate['device_id'] = $newDeviceId;
        $dataUpdate['district'] = $request['district'];
        $dataUpdate['municipal'] = $request['municipal'];

        $dataUpdate['work_assigned_id'] = $request['work_assigned_id'];
        $dataUpdate['designation_id'] = $request['designation_id'];
        $dataUpdate['employment_type_id'] = $request['employment_type_id'];
        $dataUpdate['hajiri_department_id'] = $request['hajiri_department_id'];

        $dataUpdate['status'] = (int)$request['status'];

        $updated = DB::transaction(function () use ($id, $dataUpdate, $oldDeviceId, $newDeviceId) {
            $result = $this->user->where('id', $id)->update($dataUpdate);

            if ($result && $oldDeviceId && $newDeviceId && (string) $oldDeviceId !== (string) $newDeviceId) {
                DB::table('attendacelogs')
                    ->where('user_id', (int) $oldDeviceId)
                    ->update(['user_id' => (int) $newDeviceId]);
            }

            return $result;
        });

        if ($updated) {
            return redirect()->back()->with('message', "Update to {$dataUpdate['name']} was successful!!");
        } else {
            return redirect()->back()->withError('message', 'Sorry!! Update Failed!');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function sorting(Request $request){
        $device_ids =  $request->data;
        $hajiri_department_id = $request->hajiri_department_id;
        $index = 0;
        foreach($device_ids as $device_id){
            if($hajiri_department_id != '')
            {
                $this->user->where('id','LIKE',$device_id)->where('hajiri_department_id','LIKE',$hajiri_department_id)->update(['sort'=>$index]);
                $index++;
            }
            else
            {
                $this->user->where('id','LIKE',$device_id)->update(['sort'=>$index]);
                $index++;
            }
        }
    }

    private function staffProfileQuery()
    {
        return $this->user->with('designation','employment','roles','student')
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhere(function ($pendingAdminQuery) {
                        $pendingAdminQuery->whereNull('status')
                            ->whereHas('roles', function ($roleQuery) {
                                $roleQuery->whereIn('name', ['super-admin', 'principal', 'administrator', 'accountant']);
                            });
                    });
            });
    }
}
