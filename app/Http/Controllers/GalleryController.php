<?php

namespace App\Http\Controllers;
use App\Models\Media;
use Illuminate\Support\Facades\Schema;

class GalleryController extends Controller
{
    public function index()
    {
        $galleryImages = Schema::hasColumn('media', 'show_in_gallery')
            ? Media::where('mime_type', 'like', 'image/%')
                ->where('show_in_gallery', true)
                ->latest()
                ->get()
            : collect();

        return view('pages.gallery', compact('galleryImages'));
    }
}
