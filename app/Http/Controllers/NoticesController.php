<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;


class NoticesController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category', 'All');

    $query = Announcement::where('type', 'notice')
                        ->where('is_published', true);

    // Apply category filter if one is selected
    if ($category !== 'All') {
        $query->where('category', $category);
    }

    $noticeCategories = Announcement::where('type', 'notice')
        ->where('is_published', true)
        ->whereNotNull('category')
        ->distinct()
        ->orderBy('category')
        ->pluck('category')
        ->filter()
        ->values();

    // Get notices with pagination (10 per page)
    $notices = $query->latest()->paginate(12)->withQueryString();

    return view('pages.notices', compact('notices', 'category', 'noticeCategories'));
    }
    /**
     * Display the specified notice/event to the public.
     */
    public function show($slug)
    {
        // Find the announcement or fail with 404
        $announcement = Announcement::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Get recent notices for the sidebar (excluding current one)
        $recentNotices = Announcement::where('id', '!=', $announcement->id)
            ->where('is_published', true)
            ->latest()
            ->take(5)
            ->get();

        return view('pages.news-detail', compact('announcement', 'recentNotices'));
    }
}
