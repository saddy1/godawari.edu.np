<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hajiri\Designation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    public function index()
    {
        // Exclude only the seeded DEFAULT sentinel row.
        // Must use grouped OR — MySQL evaluates  NULL != 'DEFAULT'  as NULL (not TRUE),
        // which would silently drop every row whose alias is null.
        $designations = Designation::where(function ($q) {
            $q->whereNull('alias')->orWhere('alias', '!=', 'DEFAULT');
        })->get();

        // Count how many users (employees) are assigned to each designation.
        // Used in the view to disable the Delete button when a designation is in use.
        $usageCounts = User::whereIn('designation_id', $designations->pluck('id'))
            ->selectRaw('designation_id, COUNT(*) as cnt')
            ->groupBy('designation_id')
            ->pluck('cnt', 'designation_id');

        return view('hr.designations.index', compact('designations', 'usageCounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('designation', 'label'),
            ],
        ], [
            'name.unique' => 'A designation with this name already exists.',
        ]);

        Designation::create(['label' => $request->name, 'status' => 1]);

        return back()->with('success', 'Designation added successfully.');
    }

    public function update(Request $request, Designation $designation)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:191',
                Rule::unique('designation', 'label')->ignore($designation->id),
            ],
            'status' => 'required|in:0,1',
        ], [
            'name.unique' => 'A designation with this name already exists.',
        ]);

        $designation->update(['label' => $request->name, 'status' => $request->status]);

        return back()->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation)
    {
        $inUse = User::where('designation_id', $designation->id)->count();

        if ($inUse > 0) {
            return back()->with('error', 'Cannot delete "' . $designation->label . '": ' . $inUse . ' employee(s) are currently assigned to this designation. Reassign them first.');
        }

        $designation->delete();

        return back()->with('success', 'Designation deleted.');
    }
}
