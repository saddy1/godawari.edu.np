<?php

namespace App\Http\Controllers\Hajiri;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use File;
use App\Models\Hajiri\AttendanceLogs;
use Carbon\Carbon;

class DeviceController extends Controller
{
    private $attnLogs;
    
    public function __construct(AttendanceLogs $attnLogs)
    {
        $this->attnLogs = $attnLogs;
    }
    
    public function index(){
        return view('hajiri.devices.index');
    }

    public function sync_api(Request $request)
    {
        $configuredToken = (string) config('services.hajiri_sync.token');
        $providedToken = (string) $request->bearerToken();

        if ($configuredToken === '' || $providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing Hajiri sync token.',
            ], 401);
        }

        $validated = $request->validate([
            'machineInfo' => ['required', 'array', 'max:10000'],
            'machineInfo.*.indRegID' => ['required', 'integer', 'min:1'],
            'machineInfo.*.dateTimeRecord' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);

        $now = now();
        $records = collect($validated['machineInfo'])
            ->map(function (array $record) use ($now) {
                return [
                    'user_id' => (int) $record['indRegID'],
                    'at' => Carbon::createFromFormat('Y-m-d H:i:s', $record['dateTimeRecord'])->format('Y-m-d H:i:s'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->unique(fn (array $record) => $record['user_id'].'|'.$record['at'])
            ->values();

        $inserted = 0;
        foreach ($records->chunk(1000) as $chunk) {
            $inserted += $this->attnLogs->newQuery()->insertOrIgnore($chunk->all());
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance synchronized successfully.',
            'received' => count($validated['machineInfo']),
            'inserted' => $inserted,
            'duplicates' => count($validated['machineInfo']) - $inserted,
        ]);
    }
    
    public function sync_online(){
        $publicDir = public_path()."/upload_json/";
        if (!File::isDirectory($publicDir)) {
            return response()->json(['status' => 0]);
        }

        $files = File::files($publicDir);
        $hajiriDB = array();
        foreach ($files as $file)
        {
            $fileName = $file->getFileName();
            $hajiriLogs = json_decode(file_get_contents($publicDir.$fileName), true);
            if (! is_array($hajiriLogs) || ! isset($hajiriLogs['machineInfo']) || ! is_array($hajiriLogs['machineInfo'])) {
                unlink($publicDir.$fileName);
                continue;
            }

            foreach ($hajiriLogs['machineInfo'] as $hajiriLog)
            {
                if (empty($hajiriLog['indRegID']) || empty($hajiriLog['dateTimeRecord'])) {
                    continue;
                }

                $deviceID = $hajiriLog['indRegID'];
                $dateTimeRecord = $hajiriLog['dateTimeRecord'];
                $dateHajiri = (Carbon::parse($dateTimeRecord))->format('Y-m-d H:i:s');
                // if($this->attnLogs->where('user_id','LIKE',$deviceID)->where('at',$dateHajiri)->count() >= 1){
                //     continue;
                // }
                // else{
                    $hajiriDB[] = array('user_id'=>$deviceID,'at'=>$dateHajiri);
               // }
               // if($this->attnLogs->where(''))
            }
            if ($hajiriDB) {
                $this->attnLogs->insertOrIgnore($hajiriDB);
            }
            unlink($publicDir.$fileName);   
            return response()->json(['status'=>count($hajiriDB)]);
        }

        return response()->json(['status' => 0]);

    }
    
    public function upload_json(Request $request)
    {
        $request->validate([
            'file' => 'required',
        ]);
        if (!File::isDirectory(public_path('upload_json'))) {
            File::makeDirectory(public_path('upload_json'), 0755, true);
        }

        $fileNameOrg = $request->file->getClientOriginalName();
        $fileName = $fileNameOrg.'-'.time().'.'.$request->file->extension();  
        $request->file->move(public_path('upload_json'), $fileName);
        return array('status'=>1,'msg'=>'Data Uploaded to Server');
    }
}
