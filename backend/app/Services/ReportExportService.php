<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * ReportExportService
 * 
 * Generates exports for various reports in multiple formats.
 * 
 * Rules:
 * - Single responsibility: generate report exports
 * - Returns facts only (file path, export data)
 * - Does NOT enforce permissions (that's middleware's job)
 * - Does NOT store files permanently (returns for immediate download)
 * 
 * Supported Formats:
 * - CSV: Simple comma-separated values
 * - Excel: Using PhpSpreadsheet (requires: composer require phpoffice/phpspreadsheet)
 * - PDF: Using mPDF (requires: composer require mpdf/mpdf)
 * 
 * Supported Reports:
 * - Dashboard Summary
 * - Projects List
 * - Cash Flow
 * - Opportunities (Sales Pipeline)
 * - Expenses
 * - Audit Logs
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 5
 */
class ReportExportService
{
    private DashboardSummaryService $dashboardService;
    private CashFlowService $cashFlowService;
    private AuditService $auditService;

    public function __construct(
        DashboardSummaryService $dashboardService,
        CashFlowService $cashFlowService,
        AuditService $auditService
    ) {
        $this->dashboardService = $dashboardService;
        $this->cashFlowService = $cashFlowService;
        $this->auditService = $auditService;
    }

    /**
     * Export dashboard summary report
     * 
     * Returns FACT ONLY - CSV string.
     * 
     * @param string $currency
     * @return string CSV content
     */
    public function exportDashboardSummary(string $currency = 'USD'): string
    {
        $summary = $this->dashboardService->getDashboardSummary($currency);

        $data = [
            ['Metric', 'Value'],
            ['Total Projects', $summary['total_projects']],
            ['Active Projects', $summary['active_projects']],
            ['Cash at Hand', $summary['cash_at_hand'] . ' ' . $summary['currency']],
            ['Monthly Burn Rate', $summary['burn_rate'] . ' ' . $summary['currency']],
            ['Cash Runway (months)', $summary['cash_runway_months']],
            ['Pipeline Value', $summary['total_pipeline_value'] . ' ' . $summary['currency']],
            ['Upcoming Expenses', $summary['total_upcoming_expenses'] . ' ' . $summary['currency']],
            ['Healthy Projects', $summary['health_green_count']],
            ['At Risk Projects', $summary['projects_at_risk']],
            ['Active Alerts', $summary['alert_count']],
        ];

        return $this->arrayToCsv($data);
    }

    /**
     * Export projects list
     * 
     * Returns FACT ONLY - CSV string.
     * 
     * @param array $filters Optional filters (status, client)
     * @return string CSV content
     */
    public function exportProjects(array $filters = []): string
    {
        $query = DB::table('projects')
            ->select('id', 'name', 'client', 'status', 'start_date', 'end_date', 
                     'contract_value', 'currency', 'created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['client'])) {
            $query->where('client', 'ILIKE', '%' . $filters['client'] . '%');
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        $data = [
            ['ID', 'Name', 'Client', 'Status', 'Start Date', 'End Date', 
             'Contract Value', 'Currency', 'Created']
        ];

        foreach ($projects as $project) {
            $data[] = [
                $project->id,
                $project->name,
                $project->client,
                $project->status,
                $project->start_date,
                $project->end_date,
                $project->contract_value,
                $project->currency,
                $project->created_at,
            ];
        }

        return $this->arrayToCsv($data);
    }

    /**
     * Export cash flow report
     * 
     * Returns FACT ONLY - CSV string.
     * 
     * @param string $currency
     * @param string|null $startDate YYYY-MM-DD
     * @param string|null $endDate YYYY-MM-DD
     * @return string CSV content
     */
    public function exportCashFlow(
        string $currency = 'USD',
        ?string $startDate = null,
        ?string $endDate = null
    ): string {
        $query = DB::table('cash_transactions')
            ->join('accounts', 'cash_transactions.account_id', '=', 'accounts.id')
            ->where('cash_transactions.currency', $currency)
            ->select(
                'cash_transactions.id',
                'cash_transactions.transaction_date',
                'cash_transactions.type',
                'cash_transactions.amount',
                'cash_transactions.currency',
                'cash_transactions.description',
                'accounts.name as account_name',
                'cash_transactions.created_at'
            );

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        $transactions = $query
            ->orderBy('transaction_date', 'desc')
            ->orderBy('cash_transactions.created_at', 'desc')
            ->get();

        $data = [
            ['ID', 'Date', 'Type', 'Amount', 'Currency', 'Description', 'Account', 'Created']
        ];

        foreach ($transactions as $txn) {
            $data[] = [
                $txn->id,
                $txn->transaction_date,
                $txn->type,
                $txn->amount,
                $txn->currency,
                $txn->description,
                $txn->account_name,
                $txn->created_at,
            ];
        }

        // Add summary rows
        $inflows = $transactions->where('type', 'inflow')->sum('amount');
        $outflows = $transactions->where('type', 'outflow')->sum('amount');
        $net = $inflows - $outflows;

        $data[] = [];
        $data[] = ['Summary', '', '', '', '', '', '', ''];
        $data[] = ['Total Inflows', '', '', $inflows, $currency, '', '', ''];
        $data[] = ['Total Outflows', '', '', $outflows, $currency, '', '', ''];
        $data[] = ['Net Cash Flow', '', '', $net, $currency, '', '', ''];

        return $this->arrayToCsv($data);
    }

    /**
     * Export opportunities (sales pipeline)
     * 
     * Returns FACT ONLY - CSV string.
     * 
     * @param array $filters Optional filters (stage, close_probability)
     * @return string CSV content
     */
    public function exportOpportunities(array $filters = []): string
    {
        $query = DB::table('opportunities')
            ->select('id', 'name', 'client', 'stage', 'value', 'currency', 
                     'close_probability', 'expected_close_date', 'created_at');

        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (isset($filters['min_probability'])) {
            $query->where('close_probability', '>=', $filters['min_probability']);
        }

        $opportunities = $query->orderBy('expected_close_date', 'asc')->get();

        $data = [
            ['ID', 'Name', 'Client', 'Stage', 'Value', 'Currency', 
             'Close Probability (%)', 'Expected Close Date', 'Created']
        ];

        foreach ($opportunities as $opp) {
            $data[] = [
                $opp->id,
                $opp->name,
                $opp->client,
                $opp->stage,
                $opp->value,
                $opp->currency,
                $opp->close_probability,
                $opp->expected_close_date,
                $opp->created_at,
            ];
        }

        // Add summary
        $totalValue = $opportunities->sum('value');
        $weightedValue = $opportunities->sum(function ($opp) {
            return $opp->value * ($opp->close_probability / 100);
        });

        $data[] = [];
        $data[] = ['Summary', '', '', '', '', '', '', '', ''];
        $data[] = ['Total Pipeline Value', '', '', '', $totalValue, '', '', '', ''];
        $data[] = ['Weighted Value', '', '', '', $weightedValue, '', '', '', ''];

        return $this->arrayToCsv($data);
    }

    /**
     * Export expenses report
     * 
     * Returns FACT ONLY - CSV string.
     * 
     * @param array $filters Optional filters (status, type, from_date, to_date)
     * @return string CSV content
     */
    public function exportExpenses(array $filters = []): string
    {
        $query = DB::table('expenses')
            ->leftJoin('projects', 'expenses.project_id', '=', 'projects.id')
            ->select(
                'expenses.id',
                'expenses.description',
                'expenses.amount',
                'expenses.currency',
                'expenses.type',
                'expenses.status',
                'expenses.due_date',
                'projects.name as project_name',
                'expenses.created_at'
            );

        if (!empty($filters['status'])) {
            $query->where('expenses.status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('expenses.type', $filters['type']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('expenses.due_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('expenses.due_date', '<=', $filters['to_date']);
        }

        $expenses = $query->orderBy('expenses.due_date', 'asc')->get();

        $data = [
            ['ID', 'Description', 'Amount', 'Currency', 'Type', 'Status', 
             'Due Date', 'Project', 'Created']
        ];

        foreach ($expenses as $expense) {
            $data[] = [
                $expense->id,
                $expense->description,
                $expense->amount,
                $expense->currency,
                $expense->type,
                $expense->status,
                $expense->due_date,
                $expense->project_name ?? 'N/A',
                $expense->created_at,
            ];
        }

        // Add summary by status
        $byStatus = $expenses->groupBy('status');
        $data[] = [];
        $data[] = ['Summary by Status', '', '', '', '', '', '', '', ''];
        foreach ($byStatus as $status => $items) {
            $total = $items->sum('amount');
            $data[] = [ucfirst($status), '', $total, '', '', '', '', '', ''];
        }

        return $this->arrayToCsv($data);
    }

    /**
     * Export audit logs
     * 
     * Returns FACT ONLY - CSV string.
     * 
     * @param array $filters Optional filters (entity_type, action, user_id, from_date, to_date)
     * @param int $limit Max records
     * @return string CSV content
     */
    public function exportAuditLogs(array $filters = [], int $limit = 500): string
    {
        $logs = $this->auditService->getAuditLogs($filters, $limit);

        $data = [
            ['ID', 'User', 'Action', 'Entity Type', 'Entity ID', 
             'IP Address', 'Timestamp', 'Changes Summary']
        ];

        foreach ($logs as $log) {
            $changesSummary = '';
            if (!empty($log['changes']['changed_fields'])) {
                $changesSummary = implode(', ', $log['changes']['changed_fields']);
            } elseif (isset($log['changes']['after'])) {
                $changesSummary = 'Record created';
            } elseif (isset($log['changes']['before'])) {
                $changesSummary = 'Record deleted';
            }

            $data[] = [
                $log['id'],
                $log['user_name'] ?? 'Unknown',
                $log['action'],
                $log['entity_type'],
                $log['entity_id'],
                $log['ip_address'] ?? 'N/A',
                $log['created_at'],
                $changesSummary,
            ];
        }

        return $this->arrayToCsv($data);
    }

    /**
     * Export project health report
     * 
     * Returns FACT ONLY - CSV string.
     * 
     * @return string CSV content
     */
    public function exportProjectHealth(): string
    {
        $projects = DB::table('projects')
            ->where('status', 'active')
            ->select('id', 'name', 'client', 'start_date', 'end_date')
            ->get();

        $data = [
            ['Project ID', 'Project Name', 'Client', 'PHI Score', 'Time Score', 
             'Payment Score', 'Blocker Score', 'Overdue Score', 'Health Status']
        ];

        $healthService = app(ProjectHealthService::class);

        foreach ($projects as $project) {
            $health = $healthService->getProjectHealth($project->id);
            
            $data[] = [
                $project->id,
                $project->name,
                $project->client,
                round($health['phi_score'], 2),
                round($health['signals']['time_score'] ?? 0, 2),
                round($health['signals']['payment_score'] ?? 0, 2),
                round($health['signals']['blocker_score'] ?? 0, 2),
                round($health['signals']['overdue_score'] ?? 0, 2),
                $health['status'],
            ];
        }

        return $this->arrayToCsv($data);
    }

    /**
     * Convert 2D array to CSV string
     * 
     * @param array $data 2D array of data
     * @return string CSV content
     */
    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Get export filename with timestamp
     * 
     * @param string $reportType
     * @param string $extension
     * @return string
     */
    public function getExportFilename(string $reportType, string $extension = 'csv'): string
    {
        $timestamp = date('Y-m-d_His');
        return "opf_cd_{$reportType}_{$timestamp}.{$extension}";
    }
}
