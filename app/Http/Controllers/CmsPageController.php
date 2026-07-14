<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    /** Render a published CMS page. Drafts 404 (never leak unpublished). */
    public function show(CmsPage $page): View
    {
        abort_unless($page->isPublished(), 404);

        return view('cms.show', ['page' => $page]);
    }
}
