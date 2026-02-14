<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use DateTime;
use DateInterval;

/**
 * AlertService
 * 
 * Evaluates business conditions and generates system alerts.
 * 
 * Rules:
 * - Single responsibility: evaluate alert conditions, write alert records
 * - Returns facts only (alert created: true/false)
 * - Does NOT send notifications (that's a separate service)
 * - Alert conditions defined in docs/_truth.md and spec
 * 
 * Alert Types:
 * 1. Project behind schedule (time_score < 60)
 * 2. Payment gap breach (payment_gap > 20%)
 * 3. Low cash runway (< 3 months)
 * 4. Expense overdue (past due_date and status = 'due')
 * 5. Opportunity closing soon (< 7 days to expected_close_date)
 * 
 * Source: docs/PRODUCTION_ROADMAP.md Sprint 3
 */
class AlertService
{
    private ProjectHealthService $healthService;
    private PaymentGapService $paymentGapService;
    private CashFlowService $cashFlowService;

    public function __construct(
        ProjectHealthService $healthService,
        PaymentGapService $paymentGapService,
        CashFlowService $cashFlowService
    ) {
        $this->healthService = $healthService;
        $this->paymentGapService = $paymentGapService;
        $this->cashFlowService = $cashFlowService;
    }

    /**
     * Evaluate all alert conditions and generate alerts
     * 
     * Returns FACT ONLY - count of alerts created.
     * 
     * @return array ['alerts_created' => int, 'alerts_by_type' => array]
     */
    public function evaluateAllAlerts(): array
    {
        $alertsCreated = 0;
        $alertsByType = [];

        // Clear old alerts (keep for 30 days)
        $this->clearOldAlerts();

        // Evaluate each alert type
        $alertsCreated += $this->evaluateProjectScheduleAlerts();
        $alertsByType['project_behind_schedule'] = DB::table('alerts')
            ->where('type', 'project_behind_schedule')
            ->where('is_dismissed', false)
            ->count();

        $alertsCreated += $this->evaluatePaymentGapAlerts();
        $alertsByType['payment_gap_breach'] = DB::table('alerts')
            ->where('type', 'payment_gap_breach')
            ->where('is_dismissed', false)
            ->count();

        $alertsCreated += $this->evaluateCashRunwayAlerts();
        $alertsByType['low_cash_runway'] = DB::table('alerts')
            ->where('type', 'low_cash_runway')
            ->where('is_dismissed', false)
            ->count();

        $alertsCreated += $this->evaluateOverdueExpenseAlerts();
        $alertsByType['expense_overdue'] = DB::table('alerts')
            ->where('type', 'expense_overdue')
            ->where('is_dismissed', false)
            ->count();

        $alertsCreated += $this->evaluateOpportunityClosingAlerts();
        $alertsByType['opportunity_closing_soon'] = DB::table('alerts')
            ->where('type', 'opportunity_closing_soon')
            ->where('is_dismissed', false)
            ->count();

        return [
            'alerts_created' => $alertsCreated,
            'alerts_by_type' => $alertsByType,
        ];
    }

    /**
     * Evaluate project schedule alerts
     * 
     * Alert condition: time_score < 60
     * 
     * Returns count of alerts created.
     */
    private function evaluateProjectScheduleAlerts(): int
    {
        $alertsCreated = 0;

        // Get all active projects
        $activeProjects = DB::table('projects')
            ->where('status', 'active')
            ->get();

        foreach ($activeProjects as $project) {
            // Get project health
            $health = $this->healthService->getProjectHealth($project->id);
            $timeScore = $health['signals']['time_score'] ?? 100;

            // Check if time score below threshold
            if ($timeScore < 60) {
                // Check if alert already exists for this project
                $existingAlert = DB::table('alerts')
                    ->where('type', 'project_behind_schedule')
                    ->where('entity_type', 'project')
                    ->where('entity_id', $project->id)
                    ->where('is_dismissed', false)
                    ->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                    ->exists();

                if (!$existingAlert) {
                    $message = sprintf(
                        "Project '%s' is behind schedule. Time score: %.1f/100. Expected progress vs actual progress misaligned.",
                        $project->name,
                        $timeScore
                    );

                    DB::table('alerts')->insert([
                        'type' => 'project_behind_schedule',
                        'severity' => $timeScore < 40 ? 'critical' : 'warning',
                        'entity_type' => 'project',
                        'entity_id' => $project->id,
                        'message' => $message,
                        'created_at' => now(),
                    ]);

                    $alertsCreated++;
                }
            }
        }

        return $alertsCreated;
    }

    /**
     * Evaluate payment gap alerts
     * 
     * Alert condition: payment_gap > 20%
     * 
     * Returns count of alerts created.
     */
    private function evaluatePaymentGapAlerts(): int
    {
        $alertsCreated = 0;

        // Get all active projects
        $activeProjects = DB::table('projects')
            ->where('status', 'active')
            ->get();

        foreach ($activeProjects as $project) {
            // Get payment gap
            $paymentGap = $this->paymentGapService->calculatePaymentGap($project->id);
            $gapPercentage = $paymentGap['gap_percentage'];

            // Check if payment gap exceeds threshold
            if ($gapPercentage > 20) {
                // Check if alert already exists
                $existingAlert = DB::table('alerts')
                    ->where('type', 'payment_gap_breach')
                    ->where('entity_type', 'project')
                    ->where('entity_id', $project->id)
                    ->where('is_dismissed', false)
                    ->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                    ->exists();

                if (!$existingAlert) {
                    $message = sprintf(
                        "Project '%s' has significant payment gap: %.1f%%. Work delivered exceeds payment received by %s %s.",
                        $project->name,
                        $gapPercentage,
                        number_format($paymentGap['gap_amount'], 0),
                        $project->contract_currency
                    );

                    DB::table('alerts')->insert([
                        'type' => 'payment_gap_breach',
                        'severity' => $gapPercentage > 40 ? 'critical' : 'warning',
                        'entity_type' => 'project',
                        'entity_id' => $project->id,
                        'message' => $message,
                        'created_at' => now(),
                    ]);

                    $alertsCreated++;
                }
            }
        }

        return $alertsCreated;
    }

    /**
     * Evaluate cash runway alerts
     * 
     * Alert condition: cash_runway < 3 months
     * 
     * Returns count of alerts created.
     */
    private function evaluateCashRunwayAlerts(): int
    {
        $alertsCreated = 0;

        // Get cash runway for USD (primary currency)
        $runway = $this->cashFlowService->calculateCashRunway('USD');

        // Check if runway below threshold
        if ($runway > 0 && $runway < 3) {
            // Check if alert already exists
            $existingAlert = DB::table('alerts')
                ->where('type', 'low_cash_runway')
                ->where('entity_type', 'company')
                ->where('entity_id', 1)
                ->where('is_dismissed', false)
                ->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                ->exists();

            if (!$existingAlert) {
                $message = sprintf(
                    "Low cash runway: %.1f months remaining. Current burn rate may exhaust cash reserves soon.",
                    $runway
                );

                DB::table('alerts')->insert([
                    'type' => 'low_cash_runway',
                    'severity' => $runway < 1 ? 'critical' : 'warning',
                    'entity_type' => 'company',
                    'entity_id' => 1,
                    'message' => $message,
                    'created_at' => now(),
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Evaluate overdue expense alerts
     * 
     * Alert condition: due_date < today && status = 'due'
     * 
     * Returns count of alerts created.
     */
    private function evaluateOverdueExpenseAlerts(): int
    {
        $alertsCreated = 0;
        $today = date('Y-m-d');

        // Get overdue expenses
        $overdueExpenses = DB::table('expenses')
            ->where('due_date', '<', $today)
            ->where('status', 'due')
            ->get();

        foreach ($overdueExpenses as $expense) {
            // Check if alert already exists
            $existingAlert = DB::table('alerts')
                ->where('type', 'expense_overdue')
                ->where('entity_type', 'expense')
                ->where('entity_id', $expense->id)
                ->where('is_dismissed', false)
                ->exists();

            if (!$existingAlert) {
                $daysOverdue = (new DateTime($today))->diff(new DateTime($expense->due_date))->days;

                $message = sprintf(
                    "Expense '%s' is %d day(s) overdue. Amount: %s %s. Due date was: %s.",
                    $expense->name,
                    $daysOverdue,
                    number_format($expense->amount, 0),
                    $expense->currency,
                    $expense->due_date
                );

                DB::table('alerts')->insert([
                    'type' => 'expense_overdue',
                    'severity' => $daysOverdue > 30 ? 'critical' : 'warning',
                    'entity_type' => 'expense',
                    'entity_id' => $expense->id,
                    'message' => $message,
                    'created_at' => now(),
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Evaluate opportunity closing alerts
     * 
     * Alert condition: expected_close_date < 7 days from now
     * 
     * Returns count of alerts created.
     */
    private function evaluateOpportunityClosingAlerts(): int
    {
        $alertsCreated = 0;
        $today = new DateTime();
        $sevenDaysOut = (clone $today)->add(new DateInterval('P7D'));

        // Get opportunities closing soon (not won or lost)
        $opportunities = DB::table('opportunities')
            ->whereIn('stage', ['lead', 'qualified', 'proposal', 'negotiation'])
            ->where('expected_close_date', '<=', $sevenDaysOut->format('Y-m-d'))
            ->where('expected_close_date', '>=', $today->format('Y-m-d'))
            ->get();

        foreach ($opportunities as $opportunity) {
            // Check if alert already exists
            $existingAlert = DB::table('alerts')
                ->where('type', 'opportunity_closing_soon')
                ->where('entity_type', 'opportunity')
                ->where('entity_id', $opportunity->id)
                ->where('is_dismissed', false)
                ->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                ->exists();

            if (!$existingAlert) {
                $closeDate = new DateTime($opportunity->expected_close_date);
                $daysUntil = $today->diff($closeDate)->days;

                $message = sprintf(
                    "Opportunity '%s' (%s) closing in %d day(s). Expected value: %s. Stage: %s.",
                    $opportunity->description,
                    $opportunity->client,
                    $daysUntil,
                    number_format($opportunity->estimated_value, 0),
                    $opportunity->stage
                );

                DB::table('alerts')->insert([
                    'type' => 'opportunity_closing_soon',
                    'severity' => $daysUntil <= 2 ? 'warning' : 'info',
                    'entity_type' => 'opportunity',
                    'entity_id' => $opportunity->id,
                    'message' => $message,
                    'created_at' => now(),
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Get all active alerts (not dismissed)
     * 
     * Returns FACTS ONLY - list of alert records.
     * 
     * @return array
     */
    public function getActiveAlerts(): array
    {
        $alerts = DB::table('alerts')
            ->where('is_dismissed', false)
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        return array_map(function ($alert) {
            return (array) $alert;
        }, $alerts);
    }

    /**
     * Get alert count by severity
     * 
     * Returns FACTS ONLY.
     * 
     * @return array ['total' => int, 'critical' => int, 'warning' => int, 'info' => int]
     */
    public function getAlertCountBySeverity(): array
    {
        $total = DB::table('alerts')
            ->where('is_dismissed', false)
            ->count();

        $critical = DB::table('alerts')
            ->where('is_dismissed', false)
            ->where('severity', 'critical')
            ->count();

        $warning = DB::table('alerts')
            ->where('is_dismissed', false)
            ->where('severity', 'warning')
            ->count();

        $info = DB::table('alerts')
            ->where('is_dismissed', false)
            ->where('severity', 'info')
            ->count();

        return [
            'total' => $total,
            'critical' => $critical,
            'warning' => $warning,
            'info' => $info,
        ];
    }

    /**
     * Get count of active (non-dismissed) alerts
     * 
     * Returns FACT ONLY - total count of active alerts.
     * 
     * @return int
     */
    public function getTotalAlertCount(): int
    {
        return DB::table('alerts')
            ->where('is_dismissed', false)
            ->count();
    }

    /**
     * Dismiss an alert
     * 
     * Returns FACT ONLY - success status.
     * 
     * @param int $alertId
     * @param int $userId
     * @return bool
     */
    public function dismissAlert(int $alertId, int $userId): bool
    {
        $result = DB::table('alerts')
            ->where('id', $alertId)
            ->update([
                'is_dismissed' => true,
                'dismissed_at' => now(),
                'dismissed_by' => $userId,
            ]);

        return $result > 0;
    }

    /**
     * Clear old dismissed alerts (30+ days)
     * 
     * Returns FACT ONLY - count of deleted alerts.
     * 
     * @return int
     */
    private function clearOldAlerts(): int
    {
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

        return DB::table('alerts')
            ->where('is_dismissed', true)
            ->whereDate('dismissed_at', '<', $thirtyDaysAgo)
            ->delete();
    }
}
