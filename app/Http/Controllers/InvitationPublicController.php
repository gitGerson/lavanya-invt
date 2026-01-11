<?php

namespace App\Http\Controllers;

use App\Services\TemplateRenderer;

class InvitationPublicController extends Controller
{
    public function show(string $slug, TemplateRenderer $renderer)
    {
        return $renderer->renderPublicBySlug($slug, useCache: true);
    }
}
