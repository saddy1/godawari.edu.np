<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\FacultyGroup;

class FacultyController extends Controller
{
   public function index()
    {
        $groups = FacultyGroup::where('is_active', true)
            ->with(['activeMembers' => fn ($query) => $query->orderBy('order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($group) => $group->activeMembers->isNotEmpty())
            ->values();

        $faculties = $groups->flatMap->activeMembers;
        $categories = $groups->pluck('name');

        return view('pages.faculty', compact('faculties', 'categories', 'groups'));
    }
}
