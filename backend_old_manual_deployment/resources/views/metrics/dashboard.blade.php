{{--
STRICT VIEW RULE:
This view renders data only.
No calculations.
No decisions.
No service calls.
--}}

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Business Health KPIs - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">

<div class="min-h-screen p-8" x-data="businessHealthDashboard()" x-init="fetchAllMetrics()">

    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-8">
        <div class="mb-4 text-sm text-gray-400">
            <a href="/" class="hover:text-gray-200 transition-colors">Home</a>
            <span class="mx-2">→</span>
            <a href="/dashboard" class="hover:text-gray-200 transition-colors">Dashboard</a>
            <span class="mx-2">→</span>
            <span class="text-gray-200">Business Health KPIs</span>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold mb-2">Business Health KPIs</h1>
                <p class="text-gray-400">Organisational performance at a glance</p>
            </div>

            <!-- Period Selector -->
            <div>
                <label class="block text-sm text-gray-400 mb-2">Period</label>
                <select x-model="selectedPeriod"
                        @change="fetchAllMetrics()"
                        class="px-4 py-2 rounded-lg bg-slate-800 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors min-w-[180px]">
                    <option value="current_quarter" class="bg-slate-800">Current Quarter</option>
                    <option value="Q1_2026" class="bg-slate-800">Q1 2026</option>
                    <option value="Q2_2026" class="bg-slate-800">Q2 2026</option>
                    <option value="Q3_2026" class="bg-slate-800">Q3 2026</option>
                    <option value="Q4_2026" class="bg-slate-800">Q4 2026</option>
                    <option value="2026-01" class="bg-slate-800">January 2026</option>
                    <option value="2026-02" class="bg-slate-800">February 2026</option>
                    <option value="2026-03" class="bg-slate-800">March 2026</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="error" class="max-w-7xl mx-auto mb-6">
        <div class="rounded-lg bg-red-900/30 border border-red-500/30 p-4 text-red-300">
            <span x-text="error"></span>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="max-w-7xl mx-auto mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Opportunity Conversion Rate -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6"
                 :class="loading.conversionRate ? 'animate-pulse' : ''">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-400">Opportunity Conversion Rate</p>
                        <p class="text-3xl font-bold mt-1"
                           x-text="loading.conversionRate ? '...' : (metrics.conversionRate?.conversion_rate ?? 0) + '%'"></p>
                    </div>
                    <div class="text-4xl">🎯</div>
                </div>
                <div class="text-sm text-gray-400">
                    <span x-text="metrics.conversionRate?.total_won ?? 0"></span> won /
                    <span x-text="metrics.conversionRate?.total_closed ?? 0"></span> closed
                </div>
            </div>

            <!-- Sales Velocity -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6"
                 :class="loading.salesVelocity ? 'animate-pulse' : ''">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-400">Sales Velocity</p>
                        <p class="text-3xl font-bold mt-1"
                           x-text="loading.salesVelocity ? '...' : formatNumber(metrics.salesVelocity?.sales_velocity ?? 0)"></p>
                    </div>
                    <div class="text-4xl">⚡</div>
                </div>
                <div class="text-sm text-gray-400">
                    Avg deal: <span x-text="formatNumber(metrics.salesVelocity?.average_deal_size ?? 0)"></span>
                    · Avg cycle: <span x-text="(metrics.salesVelocity?.average_cycle_days ?? 0) + ' days'"></span>
                </div>
            </div>

            <!-- Average Deal Size -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6"
                 :class="loading.avgDealSize ? 'animate-pulse' : ''">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-400">Average Deal Size</p>
                        <p class="text-3xl font-bold mt-1"
                           x-text="loading.avgDealSize ? '...' : formatNumber(metrics.avgDealSize?.average_deal_size ?? 0)"></p>
                    </div>
                    <div class="text-4xl">💰</div>
                </div>
                <div class="text-sm text-gray-400">
                    Total won: <span x-text="formatNumber(metrics.avgDealSize?.total_won_value ?? 0)"></span>
                    · <span x-text="metrics.avgDealSize?.won_count ?? 0"></span> deals
                </div>
            </div>

            <!-- Pipeline Value -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6"
                 :class="loading.pipelineValue ? 'animate-pulse' : ''">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-400">Total Pipeline Value</p>
                        <p class="text-3xl font-bold mt-1"
                           x-text="loading.pipelineValue ? '...' : formatNumber(metrics.pipelineValue?.total_pipeline_value ?? 0)"></p>
                    </div>
                    <div class="text-4xl">📊</div>
                </div>
                <div class="text-sm text-gray-400">
                    Weighted: <span x-text="formatNumber(metrics.pipelineValue?.weighted_pipeline_value ?? 0)"></span>
                </div>
            </div>

            <!-- Opp-to-Project Conversion -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6"
                 :class="loading.oppToProject ? 'animate-pulse' : ''">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-400">Opp → Project Rate</p>
                        <p class="text-3xl font-bold mt-1"
                           x-text="loading.oppToProject ? '...' : (metrics.oppToProject?.conversion_rate ?? 0) + '%'"></p>
                    </div>
                    <div class="text-4xl">🔄</div>
                </div>
                <div class="text-sm text-gray-400">
                    <span x-text="metrics.oppToProject?.converted_to_project ?? 0"></span> /
                    <span x-text="metrics.oppToProject?.total_won ?? 0"></span> won opps converted
                </div>
            </div>

            <!-- Business Goals Summary -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6"
                 :class="loading.goals ? 'animate-pulse' : ''">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-400">Active Goals</p>
                        <p class="text-3xl font-bold mt-1"
                           x-text="loading.goals ? '...' : (goals.count ?? 0)"></p>
                    </div>
                    <div class="text-4xl">🏆</div>
                </div>
                <div class="text-sm text-gray-400">
                    <a href="#goals-section" class="text-indigo-400 hover:text-indigo-300 transition-colors">View all goals ↓</a>
                </div>
            </div>

        </div>
    </div>

    <!-- Pipeline by Stage -->
    <div class="max-w-7xl mx-auto mb-8">
        <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
            <h2 class="text-xl font-semibold mb-6">Pipeline by Stage</h2>
            <div x-show="loading.pipelineValue" class="text-center py-8 text-gray-400">Loading...</div>
            <div x-show="!loading.pipelineValue">
                <template x-if="(metrics.pipelineValue?.by_stage ?? []).length === 0">
                    <p class="text-center text-gray-400 py-8">No active pipeline opportunities.</p>
                </template>
                <div class="space-y-3">
                    <template x-for="stage in (metrics.pipelineValue?.by_stage ?? [])" :key="stage.stage">
                        <div class="flex items-center gap-4">
                            <div class="w-28 text-sm text-gray-300 capitalize" x-text="stage.stage"></div>
                            <div class="flex-1 bg-slate-700/50 rounded-full h-4 overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full transition-all duration-500"
                                     :style="'width: ' + stageBarWidth(stage.total_value) + '%'"></div>
                            </div>
                            <div class="w-32 text-right text-sm font-medium" x-text="formatNumber(stage.total_value)"></div>
                            <div class="w-14 text-right text-xs text-gray-400" x-text="stage.count + ' opps'"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Stage Conversion Funnel -->
    <div class="max-w-7xl mx-auto mb-8">
        <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
            <h2 class="text-xl font-semibold mb-6">Stage Conversion Funnel</h2>
            <div x-show="loading.stageConversions" class="text-center py-8 text-gray-400">Loading...</div>
            <div x-show="!loading.stageConversions">
                <template x-if="(metrics.stageConversions?.stage_conversions ?? []).length === 0">
                    <p class="text-center text-gray-400 py-8">No stage conversion data for this period.</p>
                </template>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <template x-for="conv in (metrics.stageConversions?.stage_conversions ?? [])" :key="conv.from_stage">
                        <div class="rounded-lg bg-slate-800/50 p-4 text-center">
                            <div class="text-xs text-gray-400 capitalize mb-1"
                                 x-text="conv.from_stage + ' → ' + conv.to_stage"></div>
                            <div class="text-2xl font-bold" x-text="conv.conversion_rate + '%'"></div>
                            <div class="text-xs text-gray-500 mt-1"
                                 x-text="conv.from_count + ' → ' + conv.to_count"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Goals -->
    <div class="max-w-7xl mx-auto mb-8" id="goals-section">
        <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold">Business Goals</h2>
                <a href="#" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">Manage Goals →</a>
            </div>
            <div x-show="loading.goals" class="text-center py-8 text-gray-400">Loading...</div>
            <div x-show="!loading.goals">
                <template x-if="(goals.data ?? []).length === 0">
                    <p class="text-center text-gray-400 py-8">No active business goals.</p>
                </template>
                <div class="space-y-4">
                    <template x-for="goal in (goals.data ?? [])" :key="goal.id">
                        <div class="rounded-lg bg-slate-800/50 border border-white/5 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <span class="text-sm font-medium capitalize"
                                          x-text="goal.goal_type.replace(/_/g, ' ')"></span>
                                    <span class="ml-2 text-xs text-gray-400" x-text="'(' + goal.period + ')'"></span>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-500/20 text-green-300': goal.status === 'achieved' || goal.status === 'on_track',
                                          'bg-yellow-500/20 text-yellow-300': goal.status === 'at_risk',
                                          'bg-red-500/20 text-red-300': goal.status === 'behind',
                                          'bg-blue-500/20 text-blue-300': goal.status === 'active'
                                      }"
                                      x-text="goal.status.replace(/_/g, ' ')"></span>
                            </div>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="flex-1 bg-slate-700/50 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :class="{
                                             'bg-green-500': goal.status === 'achieved' || goal.status === 'on_track',
                                             'bg-yellow-500': goal.status === 'at_risk',
                                             'bg-red-500': goal.status === 'behind',
                                             'bg-blue-500': goal.status === 'active'
                                         }"
                                         :style="'width: ' + goal.progress_percentage + '%'"></div>
                                </div>
                                <span class="text-sm font-medium w-12 text-right"
                                      x-text="goal.progress_percentage + '%'"></span>
                            </div>
                            <div class="text-xs text-gray-400">
                                <span x-text="formatNumber(goal.current_value)"></span>
                                of
                                <span x-text="formatNumber(goal.target_value)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function businessHealthDashboard() {
    return {
        selectedPeriod: 'current_quarter',
        metrics: {
            conversionRate: null,
            salesVelocity: null,
            avgDealSize: null,
            pipelineValue: null,
            oppToProject: null,
            stageConversions: null,
        },
        goals: { data: [], count: 0 },
        loading: {
            conversionRate: true,
            salesVelocity: true,
            avgDealSize: true,
            pipelineValue: true,
            oppToProject: true,
            stageConversions: true,
            goals: true,
        },
        error: null,

        async fetchAllMetrics() {
            this.error = null;

            // Reset loading states
            Object.keys(this.loading).forEach(k => { this.loading[k] = true; });

            const period = encodeURIComponent(this.selectedPeriod);

            await Promise.all([
                this.fetchMetric('/api/metrics/opportunity-conversion-rate?period=' + period, 'conversionRate'),
                this.fetchMetric('/api/metrics/sales-velocity?period=' + period, 'salesVelocity'),
                this.fetchMetric('/api/metrics/average-deal-size?period=' + period, 'avgDealSize'),
                this.fetchMetric('/api/metrics/pipeline-value', 'pipelineValue'),
                this.fetchMetric('/api/metrics/opportunity-to-project-conversion?period=' + period, 'oppToProject'),
                this.fetchMetric('/api/metrics/stage-conversion-rates?period=' + period, 'stageConversions'),
                this.fetchGoals(),
            ]);
        },

        async fetchMetric(url, key) {
            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                this.metrics[key] = await response.json();
            } catch (e) {
                this.metrics[key] = null;
            } finally {
                this.loading[key] = false;
            }
        },

        async fetchGoals() {
            try {
                const response = await fetch('/api/goals', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                this.goals = data;
            } catch (e) {
                this.goals = { data: [], count: 0 };
            } finally {
                this.loading.goals = false;
            }
        },

        stageBarWidth(value) {
            const max = Math.max(...(this.metrics.pipelineValue?.by_stage ?? []).map(s => s.total_value), 1);
            return Math.round((value / max) * 100);
        },

        formatNumber(value) {
            if (value === null || value === undefined) return '0';
            return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(value);
        }
    };
}
</script>

</body>
</html>
