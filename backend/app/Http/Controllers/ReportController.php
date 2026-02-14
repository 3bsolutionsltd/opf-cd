<?php

namespace App\Http\Controllers;

use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ReportController
 * 
 * Thin pass-through controller for report exports.
 * 
 * Rules:
 * - NO business logic
 * - NO calculations
 * - Only calls ReportExportService and returns results
 * - Permissions enforced by middleware
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 5
 */
class ReportController extends Controller
{
    private ReportExportService $exportService;

    public function __construct(ReportExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export dashboard summary
     * 
     * Query params:
     * - currency: Currency code (default USD)
     * 
     * @param Request $request
     * @return Response
     */
    public function exportDashboard(Request $request): Response
    {
        $currency = $request->input('currency', 'USD');
        $csv = $this->exportService->exportDashboardSummary($currency);
        $filename = $this->exportService->getExportFilename('dashboard_summary', 'csv');

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export projects list
     * 
     * Query params:
     * - status: Filter by status
     * - client: Filter by client name
     * 
     * @param Request $request
     * @return Response
     */
    public function exportProjects(Request $request): Response
    {
        $filters = $request->only(['status', 'client']);
        $csv = $this->exportService->exportProjects($filters);
        $filename = $this->exportService->getExportFilename('projects', 'csv');

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export cash flow report
     * 
     * Query params:
     * - currency: Currency code (default USD)
     * - start_date: Start date (YYYY-MM-DD)
     * - end_date: End date (YYYY-MM-DD)
     * 
     * @param Request $request
     * @return Response
     */
    public function exportCashFlow(Request $request): Response
    {
        $currency = $request->input('currency', 'USD');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $csv = $this->exportService->exportCashFlow($currency, $startDate, $endDate);
        $filename = $this->exportService->getExportFilename('cash_flow', 'csv');

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export opportunities (sales pipeline)
     * 
     * Query params:
     * - stage: Filter by stage
     * - min_probability: Minimum close probability
     * 
     * @param Request $request
     * @return Response
     */
    public function exportOpportunities(Request $request): Response
    {
        $filters = $request->only(['stage', 'min_probability']);
        $csv = $this->exportService->exportOpportunities($filters);
        $filename = $this->exportService->getExportFilename('opportunities', 'csv');

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export expenses report
     * 
     * Query params:
     * - status: Filter by status
     * - type: Filter by type
     * - from_date: Start date (YYYY-MM-DD)
     * - to_date: End date (YYYY-MM-DD)
     * 
     * @param Request $request
     * @return Response
     */
    public function exportExpenses(Request $request): Response
    {
        $filters = $request->only(['status', 'type', 'from_date', 'to_date']);
        $csv = $this->exportService->exportExpenses($filters);
        $filename = $this->exportService->getExportFilename('expenses', 'csv');

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export audit logs
     * 
     * Query params:
     * - entity_type: Filter by entity type
     * - action: Filter by action
     * - user_id: Filter by user
     * - from_date: Start date (YYYY-MM-DD)
     * - to_date: End date (YYYY-MM-DD)
     * - limit: Max records (default 500)
     * 
     * @param Request $request
     * @return Response
     */
    public function exportAuditLogs(Request $request): Response
    {
        $filters = $request->only(['entity_type', 'action', 'user_id', 'from_date', 'to_date']);
        $limit = (int) $request->input('limit', 500);
        $limit = min($limit, 10000); // Cap at 10k

        $csv = $this->exportService->exportAuditLogs($filters, $limit);
        $filename = $this->exportService->getExportFilename('audit_logs', 'csv');

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export project health report
     * 
     * @param Request $request
     * @return Response
     */
    public function exportProjectHealth(Request $request): Response
    {
        $csv = $this->exportService->exportProjectHealth();
        $filename = $this->exportService->getExportFilename('project_health', 'csv');

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
