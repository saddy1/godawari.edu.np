<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\Setting;

class AboutController extends Controller
{
    public function index()
    {
        $slug = Setting::get('about_page_slug', 'about-godawari-college');
        $page = CmsPage::where('slug', $slug)->with('parent')->firstOrFail();

        abort_unless(
            $page->status === 'published'
                || request()->user()?->canAccess('settings.view'),
            404
        );

        return view('pages.cms-show', compact('page'));
    }
}
