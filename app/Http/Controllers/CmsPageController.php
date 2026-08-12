<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;

class CmsPageController extends Controller
{
    public function show(string $slug)
    {
        $page = CmsPage::where('slug', $slug)
            ->with('parent')
            ->firstOrFail();

        abort_unless(
            $page->status === 'published'
                || request()->user()?->canAccess('settings.view'),
            404
        );

        return view('pages.cms-show', compact('page'));
    }
}
