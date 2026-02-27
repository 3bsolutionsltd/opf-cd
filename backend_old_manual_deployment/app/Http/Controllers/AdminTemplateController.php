<?php

namespace App\Http\Controllers;

use App\Services\ProjectTemplateService;
use Illuminate\View\View;

/**
 * AdminTemplateController
 * 
 * Handles admin views for template management
 * Thin controller - delegates all logic to ProjectTemplateService
 */
class AdminTemplateController extends Controller
{
    private ProjectTemplateService $templateService;

    public function __construct(ProjectTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display templates management page
     */
    public function index(): View
    {
        $templates = $this->templateService->getAllTemplates();
        
        return view('admin.templates.index', [
            'templates' => $templates
        ]);
    }
}
