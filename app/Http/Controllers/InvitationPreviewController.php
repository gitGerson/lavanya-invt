<?php

namespace App\Http\Controllers;

use App\Services\TemplateRenderer;

class InvitationPreviewController extends Controller
{
    public function show(string $slug, TemplateRenderer $renderer)
    {
        return $renderer->renderPreviewBySlug($slug, useCache: false);
    }
}
