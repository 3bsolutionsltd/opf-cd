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
    <title>Dashboard - OPF-CD</title>
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
    <div class="min-h-screen p-8" x-data="dashboardHub()">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <!-- Breadcrumb -->
            <div class="mb-4 text-sm text-gray-400">
                <a href="/" class="hover:text-gray-200 transition-colors">Home</a>
                <span class="mx-2">→</span>
                <span class="text-gray-200">Dashboard</span>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2">Analytics Dashboard</h1>
                    <p class="text-gray-400">Unified view of all metrics and insights</p>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Export Button -->
                    <a href="/api/reports/export/dashboard?currency=USD" 
                       download
                       class="flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600/20 border border-green-500/30 hover:bg-green-600/30 transition-all">
                        <span class="text-xl">📊</span>
                        <span class="text-sm font-medium text-green-300">Export CSV</span>
                    </a>

                    <!-- Alerts Badge -->
                    <a href="/alerts" class="relative group">
                        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-800/50 border border-white/10 hover:border-indigo-500/50 transition-all">
                            <span class="text-xl">🔔</span>
                            <span class="text-sm font-medium">Alerts</span>
                            <span x-show="dashboards.summary?.alert_count > 0" 
                                  class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 rounded-full"
                                  x-text="dashboards.summary.alert_count"></span>
                        </div>
                    </a>

                    <!-- Project Selector -->
                    <div>
                        <label for="projectFilter" class="block text-sm text-gray-400 mb-2">Filter by Project</label>
                        <select id="projectFilter" 
                                x-model="selectedProject" 
                                @change="loadDashboards()"
                                class="px-4 py-2 rounded-lg bg-slate-800 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors min-w-[250px]">
                            <option value="" class="bg-slate-800 text-gray-100">All Projects</option>
                            <template x-for="project in projects" :key="project.id">
                                <option :value="project.id" x-text="`${project.name} (${project.client})`" class="bg-slate-800 text-gray-100"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links (Moved to Top) -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <a href="/projects" class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4 hover:border-indigo-500/30 hover:bg-white/10 transition-all text-center">
                    <div class="text-2xl mb-2">📁</div>
                    <div class="text-sm font-medium">Projects</div>
                </a>
                <a href="/opportunities" class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4 hover:border-green-500/30 hover:bg-white/10 transition-all text-center">
                    <div class="text-2xl mb-2">🎯</div>
                    <div class="text-sm font-medium">Opportunities</div>
                </a>
                <a href="/accounts" class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4 hover:border-purple-500/30 hover:bg-white/10 transition-all text-center">
                    <div class="text-2xl mb-2">🏦</div>
                    <div class="text-sm font-medium">Accounts</div>
                </a>
                <a href="/cash-transactions" class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4 hover:border-blue-500/30 hover:bg-white/10 transition-all text-center">
                    <div class="text-2xl mb-2">💸</div>
                    <div class="text-sm font-medium">Transactions</div>
                </a>
                <a href="/alerts" class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4 hover:border-red-500/30 hover:bg-white/10 transition-all text-center">
                    <div class="text-2xl mb-2">🔔</div>
                    <div class="text-sm font-medium">Alerts</div>
                </a>
                <a href="/audit-logs" class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4 hover:border-yellow-500/30 hover:bg-white/10 transition-all text-center">
                    <div class="text-2xl mb-2">📋</div>
                    <div class="text-sm font-medium">Audit Logs</div>
                </a>
            </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="max-w-7xl mx-auto mb-8">
            <h2 class="text-2xl font-bold mb-4">Key Metrics</h2>
            <div x-show="loading.summary" class="text-center py-12 text-gray-500">
                Loading summary...
            </div>
            <div x-show="!loading.summary && dashboards.summary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Projects -->
                <div class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4">
                    <div class="text-sm text-gray-400 mb-1">Total Projects</div>
                    <div class="text-3xl font-bold text-indigo-400" x-text="dashboards.summary.total_projects || 0"></div>
                    <div class="text-xs text-gray-500 mt-1">
                        <span x-text="dashboards.summary.active_projects || 0"></span> active
                    </div>
                </div>

                <!-- Cash at Hand -->
                <div class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4">
                    <div class="text-sm text-gray-400 mb-1">Cash at Hand</div>
                    <div class="text-3xl font-bold text-green-400">
                        <span x-text="dashboards.summary.currency || 'USD'"></span>
                        <span x-text="(dashboards.summary.cash_at_hand || 0).toLocaleString()"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Current balance</div>
                </div>

                <!-- Monthly Burn Rate -->
                <div class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4">
                    <div class="text-sm text-gray-400 mb-1">Monthly Burn Rate</div>
                    <div class="text-3xl font-bold text-orange-400">
                        <span x-text="dashboards.summary.currency || 'USD'"></span>
                        <span x-text="(dashboards.summary.burn_rate || 0).toLocaleString()"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Avg monthly outflows</div>
                </div>

                <!-- Cash Runway -->
                <div class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4">
                    <div class="text-sm text-gray-400 mb-1">Cash Runway</div>
                    <div class="text-3xl font-bold" 
                         :class="{
                             'text-green-400': dashboards.summary.cash_runway_months >= 6,
                             'text-yellow-400': dashboards.summary.cash_runway_months >= 3 && dashboards.summary.cash_runway_months < 6,
                             'text-red-400': dashboards.summary.cash_runway_months < 3
                         }">
                        <span x-text="(dashboards.summary.cash_runway_months || 0).toFixed(1)"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">months remaining</div>
                </div>

                <!-- Pipeline Value -->
                <div class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4">
                    <div class="text-sm text-gray-400 mb-1">Pipeline Value</div>
                    <div class="text-3xl font-bold text-blue-400">
                        <span x-text="dashboards.summary.currency || 'USD'"></span>
                        <span x-text="(dashboards.summary.total_pipeline_value || 0).toLocaleString()"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Total opportunities</div>
                </div>

                <!-- Upcoming Expenses -->
                <div class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4">
                    <div class="text-sm text-gray-400 mb-1">Upcoming Expenses</div>
                    <div class="text-3xl font-bold text-purple-400">
                        <span x-text="dashboards.summary.currency || 'USD'"></span>
                        <span x-text="(dashboards.summary.total_upcoming_expenses || 0).toLocaleString()"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Next 90 days</div>
                </div>

                <!-- Health: Green -->
                <div class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4">
                    <div class="text-sm text-gray-400 mb-1">Healthy Projects</div>
                    <div class="text-3xl font-bold text-green-400" x-text="dashboards.summary.health_green_count || 0"></div>
                    <div class="text-xs text-gray-500 mt-1">Score ≥ 80</div>
                </div>

                <!-- Health: At Risk -->
                <div class="rounded-lg bg-white/5 backdrop-blur-xl border border-white/10 p-4">
                    <div class="text-sm text-gray-400 mb-1">At Risk Projects</div>
                    <div class="text-3xl font-bold text-red-400" x-text="dashboards.summary.projects_at_risk || 0"></div>
                    <div class="text-xs text-gray-500 mt-1">
                        <span x-text="dashboards.summary.health_amber_count || 0"></span> amber, 
                        <span x-text="dashboards.summary.health_red_count || 0"></span> red
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Project Progress -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-indigo-500/30 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold flex items-center gap-2">
                            <span>📊</span> Project Progress
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">Task completion metrics</p>
                    </div>
                    <a x-show="selectedProject" 
                       :href="`/dashboard/project-progress/${selectedProject}`" 
                       class="text-sm text-indigo-400 hover:text-indigo-300">View Details →</a>
                </div>
                
                <div x-show="loading.progress" class="text-center py-12 text-gray-500">
                    Loading...
                </div>
                
                <div x-show="!loading.progress && selectedProject && dashboards.progress !== null" class="text-center py-8">
                    <div class="text-6xl font-bold text-indigo-400 mb-2">
                        <span x-text="dashboards.progress"></span>%
                    </div>
                    <div class="text-gray-400">Completion Rate</div>
                    <div class="mt-4 w-full h-2 bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500" 
                             :style="`width: ${dashboards.progress}%`"></div>
                    </div>
                </div>
                
                <div x-show="!loading.progress && !selectedProject && dashboards.progressAggregate !== null" class="text-center py-8">
                    <div class="text-6xl font-bold text-indigo-400 mb-2">
                        <span x-text="dashboards.progressAggregate"></span>%
                    </div>
                    <div class="text-gray-400">Overall Progress (All Projects)</div>
                    <div class="mt-4 w-full h-2 bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500" 
                             :style="`width: ${dashboards.progressAggregate}%`"></div>
                    </div>
                </div>
                
                <div x-show="!loading.progress && !selectedProject && dashboards.progressAggregate === null" class="text-center py-12 text-gray-400">
                    No project data available
                </div>
            </div>

            <!-- Payment Gap -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-yellow-500/30 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold flex items-center gap-2">
                            <span>💰</span> Payment Gap
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">Work delivered vs payments</p>
                    </div>
                    <a x-show="selectedProject" 
                       :href="`/dashboard/payment-gap/${selectedProject}`" 
                       class="text-sm text-yellow-400 hover:text-yellow-300">View Details →</a>
                </div>
                
                <div x-show="loading.paymentGap" class="text-center py-12 text-gray-500">
                    Loading...
                </div>
                
                <div x-show="!loading.paymentGap && selectedProject && dashboards.paymentGap.gap !== undefined" class="text-center py-8">
                    <div class="text-5xl font-bold text-yellow-400 mb-2">
                        <span x-text="dashboards.paymentGap.currency || 'UGX'"></span> <span x-text="Math.abs(dashboards.paymentGap.gap || 0).toLocaleString()"></span>
                    </div>
                    <div class="text-gray-400">
                        <span x-show="dashboards.paymentGap.gap > 0" class="text-red-400">Client owes you (payment behind work)</span>
                        <span x-show="dashboards.paymentGap.gap < 0" class="text-green-400">Payment ahead of work</span>
                        <span x-show="dashboards.paymentGap.gap === 0" class="text-blue-400">Perfectly balanced</span>
                    </div>
                </div>
                
                <div x-show="!loading.paymentGap && !selectedProject && dashboards.paymentGapAggregate !== null" class="text-center py-8">
                    <div class="text-gray-400 mb-4">Overall Payment Gap (All Projects)</div>
                    <template x-if="dashboards.paymentGapAggregate && dashboards.paymentGapAggregate.gaps">
                        <div class="space-y-2">
                            <template x-for="currency in dashboards.paymentGapAggregate.currencies" :key="currency">
                                <div class="text-4xl font-bold text-yellow-400">
                                    <span x-text="currency"></span> 
                                    <span x-text="Math.abs(dashboards.paymentGapAggregate.gaps[currency] || 0).toLocaleString()"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                
                <div x-show="!loading.paymentGap && !selectedProject && dashboards.paymentGapAggregate === null" class="text-center py-12 text-gray-400">
                    No payment data available
                </div>
            </div>

            <!-- Project Health -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-green-500/30 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold flex items-center gap-2">
                            <span>🏥</span> Project Health
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">Overall project status</p>
                    </div>
                    <a x-show="selectedProject" 
                       :href="`/dashboard/project-health/${selectedProject}`" 
                       class="text-sm text-green-400 hover:text-green-300">View Details →</a>
                </div>
                
                <div x-show="loading.health" class="text-center py-12 text-gray-500">
                    Loading...
                </div>
                
                <div x-show="!loading.health && selectedProject && dashboards.health.status" class="py-6">
                    <!-- Header with Status and Score -->
                    <div class="text-center mb-4">
                        <div class="text-5xl font-bold mb-2"
                             :class="{
                                 'text-green-400': dashboards.health.status === 'healthy',
                                 'text-yellow-400': dashboards.health.status === 'at-risk',
                                 'text-red-400': dashboards.health.status === 'critical'
                             }">
                            <span x-show="dashboards.health.status === 'healthy'">✓</span>
                            <span x-show="dashboards.health.status === 'at-risk'">⚠</span>
                            <span x-show="dashboards.health.status === 'critical'">✗</span>
                        </div>
                        <div class="font-bold text-lg mb-1" 
                             :class="{
                                 'text-green-300': dashboards.health.status === 'healthy',
                                 'text-yellow-300': dashboards.health.status === 'at-risk',
                                 'text-red-300': dashboards.health.status === 'critical'
                             }"
                             x-text="dashboards.health.status_label || dashboards.health.status?.replace('-', ' ')"></div>
                        <div class="text-sm text-gray-400" x-text="dashboards.health.status_description"></div>
                        <div class="mt-2 text-2xl font-bold"
                             :class="{
                                 'text-green-400': dashboards.health.score >= 80,
                                 'text-yellow-400': dashboards.health.score >= 50 && dashboards.health.score < 80,
                                 'text-red-400': dashboards.health.score < 50
                             }">
                            <span x-text="dashboards.health.score"></span><span class="text-gray-500 text-lg">/100</span>
                        </div>
                    </div>
                    
                    <!-- Details Section -->
                    <div x-show="dashboards.health.details && dashboards.health.details.length > 0" 
                         class="mt-4 text-left bg-black/20 rounded-lg p-4 border border-white/5">
                        <div class="text-xs font-semibold text-gray-300 mb-2">ANALYSIS:</div>
                        <ul class="space-y-1.5">
                            <template x-for="(detail, index) in dashboards.health.details" :key="index">
                                <li class="text-sm text-gray-300 flex items-start">
                                    <span class="text-gray-500 mr-2">•</span>
                                    <span x-text="detail"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    
                    <!-- Recommendations Section -->
                    <div x-show="dashboards.health.recommendations && dashboards.health.recommendations.length > 0" 
                         class="mt-3 text-left bg-blue-500/10 rounded-lg p-4 border border-blue-500/20">
                        <div class="text-xs font-semibold text-blue-300 mb-2 flex items-center">
                            <span class="mr-1">💡</span> RECOMMENDED ACTIONS:
                        </div>
                        <ul class="space-y-1.5">
                            <template x-for="(rec, index) in dashboards.health.recommendations" :key="index">
                                <li class="text-sm text-blue-200 flex items-start">
                                    <span class="text-blue-400 mr-2">→</span>
                                    <span x-text="rec"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
                
                <div x-show="!loading.health && !selectedProject && dashboards.healthAggregate" class="text-center py-8">
                    <div class="text-sm text-gray-400 mb-4">Overall Health (All Projects)</div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-400" x-text="dashboards.healthAggregate.healthy || 0"></div>
                            <div class="text-xs text-gray-400 mt-1">Healthy</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-yellow-400" x-text="dashboards.healthAggregate.atRisk || 0"></div>
                            <div class="text-xs text-gray-400 mt-1">At Risk</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-400" x-text="dashboards.healthAggregate.critical || 0"></div>
                            <div class="text-xs text-gray-400 mt-1">Critical</div>
                        </div>
                    </div>
                </div>
                
                <div x-show="!loading.health && !selectedProject && !dashboards.healthAggregate" class="text-center py-12 text-gray-400">
                    No health data available
                </div>
            </div>

            <!-- Cash Flow -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-blue-500/30 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold flex items-center gap-2">
                            <span>💸</span> Cash Flow
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">Inflows and outflows</p>
                    </div>
                    <a href="/dashboard/cash-flow" class="text-sm text-blue-400 hover:text-blue-300">View Details →</a>
                </div>
                
                <div x-show="loading.cashFlow" class="text-center py-12 text-gray-500">
                    Loading...
                </div>
                
                <div x-show="!loading.cashFlow && dashboards.cashFlow.by_currency" class="py-4 space-y-4">
                    <template x-for="currency in dashboards.cashFlow.currencies" :key="currency">
                        <div class="border-t border-gray-700 pt-4 first:border-t-0 first:pt-0">
                            <div class="text-center mb-3">
                                <span class="text-lg font-bold text-blue-400" x-text="currency"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-400 mb-1">
                                        <span x-text="dashboards.cashFlow.by_currency[currency].total_inflows?.toLocaleString() || 0"></span>
                                    </div>
                                    <div class="text-xs text-gray-400">Inflows</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-red-400 mb-1">
                                        <span x-text="dashboards.cashFlow.by_currency[currency].total_outflows?.toLocaleString() || 0"></span>
                                    </div>
                                    <div class="text-xs text-gray-400">Outflows</div>
                                </div>
                            </div>
                            <div class="mt-2 text-center">
                                <div class="text-lg font-bold"
                                     :class="(dashboards.cashFlow.by_currency[currency].net_cash_flow || 0) >= 0 ? 'text-green-400' : 'text-red-400'">
                                    Net: <span x-text="dashboards.cashFlow.by_currency[currency].net_cash_flow?.toLocaleString() || 0"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Upcoming Expenses -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-red-500/30 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold flex items-center gap-2">
                            <span>💳</span> Upcoming Expenses
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">Next 30 days</p>
                    </div>
                    <a href="/dashboard/upcoming-expenses" class="text-sm text-red-400 hover:text-red-300">View Details →</a>
                </div>
                
                <div x-show="loading.expenses" class="text-center py-12 text-gray-500">
                    Loading...
                </div>
                
                <div x-show="!loading.expenses" class="py-4">
                    <div class="text-center mb-4">
                        <div class="text-5xl font-bold text-red-400 mb-2">
                            <span x-text="(dashboards.expenses.expenses || []).length"></span>
                        </div>
                        <div class="text-gray-400">Pending Expenses</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-200">
                            <span x-text="dashboards.expenses.total_amount?.toLocaleString() || 0"></span> <span x-text="dashboards.expenses.currency || 'UGX'"></span>
                        </div>
                        <div class="text-sm text-gray-400">Total Amount Due</div>
                    </div>
                </div>
            </div>

            <!-- Sales Pipeline -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-purple-500/30 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold flex items-center gap-2">
                            <span>🎯</span> Sales Pipeline
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">Opportunity funnel</p>
                    </div>
                    <a href="/dashboard/sales-pipeline" class="text-sm text-purple-400 hover:text-purple-300">View Details →</a>
                </div>
                
                <div x-show="loading.pipeline" class="text-center py-12 text-gray-500">
                    Loading...
                </div>
                
                <div x-show="!loading.pipeline" class="py-4">
                    <div class="text-center mb-4">
                        <div class="text-5xl font-bold text-purple-400 mb-2">
                            <span x-text="(dashboards.pipeline.opportunities || []).length"></span>
                        </div>
                        <div class="text-gray-400">Active Opportunities</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-200">
                            <span x-text="dashboards.pipeline.total_value?.toLocaleString() || 0"></span>
                        </div>
                        <div class="text-sm text-gray-400">Total Pipeline Value</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function dashboardHub() {
            return {
                projects: [],
                selectedProject: '',
                dashboards: {
                    summary: null,
                    progress: null,
                    progressAggregate: null,
                    paymentGap: { gap_percentage: 0 },
                    paymentGapAggregate: null,
                    health: { status: null },
                    healthAggregate: null,
                    cashFlow: { by_currency: {}, currencies: [] },
                    expenses: { expenses: [], total_amount: 0, currency: 'UGX' },
                    pipeline: { opportunities: [], total_value: 0 }
                },
                loading: {
                    summary: true,
                    progress: true,
                    paymentGap: true,
                    health: true,
                    cashFlow: true,
                    expenses: true,
                    pipeline: true
                },

                async init() {
                    await this.loadProjects();
                    await this.loadSummary();
                    await this.loadGlobalDashboards();
                    await this.loadAggregateDashboards();
                },

                async loadProjects() {
                    try {
                        const response = await fetch('/api/projects', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.projects = await response.json();
                    } catch (error) {
                        console.error('Error loading projects:', error);
                    }
                },

                async loadSummary() {
                    this.loading.summary = true;
                    try {
                        const response = await fetch('/api/dashboard/summary', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.summary = await response.json();
                    } catch (error) {
                        console.error('Error loading summary:', error);
                    } finally {
                        this.loading.summary = false;
                    }
                },

                async loadGlobalDashboards() {
                    // Load dashboards that don't require project selection
                    await Promise.all([
                        this.loadCashFlow(),
                        this.loadUpcomingExpenses(),
                        this.loadSalesPipeline()
                    ]);
                },

                async loadDashboards() {
                    if (!this.selectedProject) {
                        return;
                    }

                    // Load project-specific dashboards
                    await Promise.all([
                        this.loadProjectProgress(),
                        this.loadPaymentGap(),
                        this.loadProjectHealth()
                    ]);
                },

                async loadProjectProgress() {
                    this.loading.progress = true;
                    try {
                        const response = await fetch(`/api/projects/${this.selectedProject}/progress`, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.progress = await response.json();
                    } catch (error) {
                        console.error('Error loading progress:', error);
                    } finally {
                        this.loading.progress = false;
                    }
                },

                async loadPaymentGap() {
                    this.loading.paymentGap = true;
                    try {
                        const response = await fetch(`/api/projects/${this.selectedProject}/payment-gap`, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.paymentGap = await response.json();
                    } catch (error) {
                        console.error('Error loading payment gap:', error);
                    } finally {
                        this.loading.paymentGap = false;
                    }
                },

                async loadProjectHealth() {
                    this.loading.health = true;
                    try {
                        const response = await fetch(`/api/projects/${this.selectedProject}/health`, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.health = await response.json();
                    } catch (error) {
                        console.error('Error loading health:', error);
                    } finally {
                        this.loading.health = false;
                    }
                },

                async loadCashFlow() {
                    this.loading.cashFlow = true;
                    try {
                        // Use project-specific endpoint if project is selected
                        const url = this.selectedProject 
                            ? `/api/projects/${this.selectedProject}/cash-flow`
                            : '/api/finance/cash-flow';
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.cashFlow = await response.json();
                    } catch (error) {
                        console.error('Error loading cash flow:', error);
                    } finally {
                        this.loading.cashFlow = false;
                    }
                },

                async loadUpcomingExpenses() {
                    this.loading.expenses = true;
                    try {
                        const response = await fetch('/api/finance/expenses/upcoming', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.expenses = await response.json();
                    } catch (error) {
                        console.error('Error loading expenses:', error);
                    } finally {
                        this.loading.expenses = false;
                    }
                },

                async loadSalesPipeline() {
                    this.loading.pipeline = true;
                    try {
                        const response = await fetch('/api/sales/pipeline', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.pipeline = await response.json();
                    } catch (error) {
                        console.error('Error loading pipeline:', error);
                    } finally {
                        this.loading.pipeline = false;
                    }
                },

                async loadAggregateDashboards() {
                    // Load aggregate data from backend APIs
                    await Promise.all([
                        this.loadAggregateProgress(),
                        this.loadAggregatePaymentGap(),
                        this.loadAggregateHealth()
                    ]);
                },

                async loadAggregateProgress() {
                    this.loading.progress = true;
                    try {
                        const response = await fetch('/api/dashboard/aggregate/progress', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.progressAggregate = await response.json();
                    } catch (error) {
                        console.error('Error loading aggregate progress:', error);
                        this.dashboards.progressAggregate = null;
                    } finally {
                        this.loading.progress = false;
                    }
                },

                async loadAggregatePaymentGap() {
                    this.loading.paymentGap = true;
                    try {
                        const response = await fetch('/api/dashboard/aggregate/payment-gap', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.paymentGapAggregate = await response.json();
                    } catch (error) {
                        console.error('Error loading aggregate payment gap:', error);
                        this.dashboards.paymentGapAggregate = null;
                    } finally {
                        this.loading.paymentGap = false;
                    }
                },

                async loadAggregateHealth() {
                    this.loading.health = true;
                    try {
                        const response = await fetch('/api/dashboard/aggregate/health', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        this.dashboards.healthAggregate = await response.json();
                    } catch (error) {
                        console.error('Error loading aggregate health:', error);
                        this.dashboards.healthAggregate = null;
                    } finally {
                        this.loading.health = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
