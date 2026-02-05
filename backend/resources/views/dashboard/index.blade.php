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

        <!-- Quick Links (Moved to Top) -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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
                        <span x-show="dashboards.paymentGap.gap > 0" class="text-green-400">Payment ahead of work</span>
                        <span x-show="dashboards.paymentGap.gap < 0" class="text-red-400">Work ahead of payment</span>
                        <span x-show="dashboards.paymentGap.gap === 0" class="text-blue-400">Perfectly balanced</span>
                    </div>
                </div>
                
                <div x-show="!loading.paymentGap && !selectedProject && dashboards.paymentGapAggregate !== null" class="text-center py-8">
                    <div class="text-5xl font-bold text-yellow-400 mb-2">
                        <span x-text="dashboards.paymentGapAggregate.currency || 'UGX'"></span> <span x-text="Math.abs(dashboards.paymentGapAggregate.gap || 0).toLocaleString()"></span>
                    </div>
                    <div class="text-gray-400">
                        Overall Payment Gap (All Projects)
                    </div>
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
                
                <div x-show="!loading.health && selectedProject && dashboards.health.status" class="text-center py-8">
                    <div class="text-6xl font-bold mb-2"
                         :class="{
                             'text-green-400': dashboards.health.status === 'healthy',
                             'text-yellow-400': dashboards.health.status === 'at-risk',
                             'text-red-400': dashboards.health.status === 'critical'
                         }">
                        <span x-show="dashboards.health.status === 'healthy'">✓</span>
                        <span x-show="dashboards.health.status === 'at-risk'">⚠</span>
                        <span x-show="dashboards.health.status === 'critical'">✗</span>
                    </div>
                    <div class="text-gray-400 capitalize" x-text="dashboards.health.status ? dashboards.health.status.replace('-', ' ') : ''"></div>
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
                
                <div x-show="!loading.cashFlow" class="py-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-400 mb-1">
                                <span x-text="dashboards.cashFlow.total_inflow?.toLocaleString() || 0"></span>
                            </div>
                            <div class="text-sm text-gray-400">Total Inflows</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-400 mb-1">
                                <span x-text="dashboards.cashFlow.total_outflow?.toLocaleString() || 0"></span>
                            </div>
                            <div class="text-sm text-gray-400">Total Outflows</div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <div class="text-xl font-bold mb-1"
                             :class="(dashboards.cashFlow.net_flow || 0) >= 0 ? 'text-green-400' : 'text-red-400'">
                            Net: <span x-text="dashboards.cashFlow.net_flow?.toLocaleString() || 0"></span>
                        </div>
                    </div>
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
                    progress: null,
                    progressAggregate: null,
                    paymentGap: { gap: 0, currency: 'UGX' },
                    paymentGapAggregate: null,
                    health: { status: null },
                    healthAggregate: null,
                    cashFlow: { total_inflow: 0, total_outflow: 0, net_flow: 0 },
                    expenses: { expenses: [], total_amount: 0, currency: 'UGX' },
                    pipeline: { opportunities: [], total_value: 0 }
                },
                loading: {
                    progress: true,
                    paymentGap: true,
                    health: true,
                    cashFlow: true,
                    expenses: true,
                    pipeline: true
                },

                async init() {
                    await this.loadProjects();
                    await this.loadGlobalDashboards();
                    await this.loadAggregateDashboards();
                },

                async loadProjects() {
                    try {
                        const response = await fetch('/api/projects', {
                            headers: { 'Accept': 'application/json' }
                        });
                        this.projects = await response.json();
                    } catch (error) {
                        console.error('Error loading projects:', error);
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
                            headers: { 'Accept': 'application/json' }
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
                            headers: { 'Accept': 'application/json' }
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
                            headers: { 'Accept': 'application/json' }
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
                        const response = await fetch('/api/finance/cash-flow', {
                            headers: { 'Accept': 'application/json' }
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
                            headers: { 'Accept': 'application/json' }
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
                            headers: { 'Accept': 'application/json' }
                        });
                        this.dashboards.pipeline = await response.json();
                    } catch (error) {
                        console.error('Error loading pipeline:', error);
                    } finally {
                        this.loading.pipeline = false;
                    }
                },

                async loadAggregateDashboards() {
                    // Load aggregate data for all projects
                    await Promise.all([
                        this.loadAggregateProgress(),
                        this.loadAggregatePaymentGap(),
                        this.loadAggregateHealth()
                    ]);
                },

                async loadAggregateProgress() {
                    this.loading.progress = true;
                    try {
                        let totalProgress = 0;
                        let count = 0;
                        
                        for (const project of this.projects) {
                            const response = await fetch(`/api/projects/${project.id}/progress`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const progress = await response.json();
                            if (typeof progress === 'number') {
                                totalProgress += progress;
                                count++;
                            }
                        }
                        
                        this.dashboards.progressAggregate = count > 0 ? Math.round(totalProgress / count) : null;
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
                        let totalGap = 0;
                        let currency = 'UGX';
                        
                        for (const project of this.projects) {
                            const response = await fetch(`/api/projects/${project.id}/payment-gap`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const gap = await response.json();
                            if (gap && gap.gap !== undefined) {
                                totalGap += gap.gap;
                                currency = gap.currency || currency;
                            }
                        }
                        
                        this.dashboards.paymentGapAggregate = { gap: totalGap, currency: currency };
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
                        let healthy = 0;
                        let atRisk = 0;
                        let critical = 0;
                        
                        for (const project of this.projects) {
                            const response = await fetch(`/api/projects/${project.id}/health`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const health = await response.json();
                            if (health && health.status) {
                                if (health.status === 'healthy') healthy++;
                                else if (health.status === 'at-risk') atRisk++;
                                else if (health.status === 'critical') critical++;
                            }
                        }
                        
                        this.dashboards.healthAggregate = { healthy, atRisk, critical };
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
