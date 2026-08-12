<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;

class AcademicsController extends Controller
{
    public function elementary()
    {
        return $this->showCmsPage('academics-elementary');
    }

    public function primary()
    {
        return $this->showCmsPage('academics-primary');
    }

    public function secondary()
    {
        return $this->showCmsPage('academics-secondary');
    }

    private function showCmsPage(string $slug)
    {
        $page = CmsPage::with('parent')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('pages.cms-show', compact('page'));
    }
}
