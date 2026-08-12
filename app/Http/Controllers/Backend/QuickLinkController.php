<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\QuickLink;
use Illuminate\Http\Request;

class QuickLinkController extends Controller
{
    public function index()
    {
        $quickLinks = QuickLink::orderBy('sort_order')->orderBy('title')->get();

        return view('backend.quick-links.index', compact('quickLinks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'title_ne'        => 'nullable|string|max:255',
            'url'             => 'required|string|max:500',
            'open_in_new_tab' => 'nullable|boolean',
            'sort_order'      => 'nullable|integer|min:0|max:9999',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['is_active']       = $request->boolean('is_active');
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $data['sort_order']      = (int) ($data['sort_order'] ?? 0);

        QuickLink::create($data);

        return redirect()->route('admin.quick-links.index')->with('success', 'Quick link added successfully.');
    }

    public function update(Request $request, QuickLink $quickLink)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'title_ne'        => 'nullable|string|max:255',
            'url'             => 'required|string|max:500',
            'open_in_new_tab' => 'nullable|boolean',
            'sort_order'      => 'nullable|integer|min:0|max:9999',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['is_active']       = $request->boolean('is_active');
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $data['sort_order']      = (int) ($data['sort_order'] ?? 0);

        $quickLink->update($data);

        return redirect()->route('admin.quick-links.index')->with('success', 'Quick link updated successfully.');
    }

    public function destroy(QuickLink $quickLink)
    {
        $quickLink->delete();

        return response()->json(['success' => true]);
    }

    public function toggleActive(QuickLink $quickLink)
    {
        $quickLink->update(['is_active' => ! $quickLink->is_active]);

        return response()->json(['success' => true, 'is_active' => $quickLink->is_active]);
    }
}
