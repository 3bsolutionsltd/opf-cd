<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Opportunity Projects - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        option { background-color: rgb(15 23 42); color: rgb(243 244 246); }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen p-8" x-data="opportunityProjects({{ $opportunityId }})">
        <!-- Header -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Projects</h1>
                    <p class="text-gray-400 mt-1" x-show="opportunity">
                        <span x-text="'Opportunity: ' + (opportunity?.client || '')"></span>
                    </p>
                </div>
                <div class="flex gap-4">
                    <a href="/opportunities" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Back to Opportunities
                    </a>
                    <button @click="showCreateModal = true" 
                            class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all">
                        Create Project (Phase)
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-6xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading projects...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" class="max-w-6xl mx-auto mb-6">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-6">
                <p class="text-red-400" x-text="error"></p>
            </div>
        </div>

        <!-- Success Message -->
        <div x-show="successMessage" class="max-w-6xl mx-auto mb-6">
            <div class="rounded-xl bg-green-500/10 border border-green-500/30 p-4">
                <p class="text-green-400" x-text="successMessage"></p>
            </div>
        </div>

        <!-- Opportunity Info Card -->
        <div x-show="opportunity && !loading" class="max-w-6xl mx-auto mb-6">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Client</p>
                        <p class="text-lg font-semibold text-gray-100 mt-1" x-text="opportunity?.client"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Estimated Value</p>
                        <p class="text-lg font-semibold text-indigo-400 mt-1" x-text="formatCurrency(opportunity?.estimated_value, opportunity?.currency)"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Stage</p>
                        <span class="inline-block mt-1 px-3 py-1 text-sm rounded-full capitalize" 
                              :class="{
                                  'bg-gray-500/20 text-gray-400': opportunity?.stage === 'lead',
                                  'bg-blue-500/20 text-blue-400': opportunity?.stage === 'qualified',
                                  'bg-yellow-500/20 text-yellow-400': opportunity?.stage === 'proposal',
                                  'bg-orange-500/20 text-orange-400': opportunity?.stage === 'negotiation',
                                  'bg-green-500/20 text-green-400': opportunity?.stage === 'won',
                                  'bg-red-500/20 text-red-400': opportunity?.stage === 'lost'
                              }"
                              x-text="opportunity?.stage">
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Total Projects</p>
                        <p class="text-lg font-semibold text-gray-100 mt-1" x-text="projects.length"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects List -->
        <div x-show="!loading && !error" class="max-w-6xl mx-auto">
            <!-- Projects Table -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Phase / Name</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Contract Value</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Start Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">End Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Project Lead</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <template x-for="(project, index) in projects" :key="project.id">
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium" x-text="project.name"></div>
                                    <div class="text-xs text-gray-500 mt-1">ID: <span x-text="project.id"></span></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-indigo-400" x-text="formatCurrency(project.contract_value, project.contract_currency)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full capitalize" 
                                          :class="{
                                              'bg-gray-500/20 text-gray-400': project.status === 'planned',
                                              'bg-green-500/20 text-green-400': project.status === 'active',
                                              'bg-yellow-500/20 text-yellow-400': project.status === 'on_hold',
                                              'bg-blue-500/20 text-blue-400': project.status === 'completed',
                                              'bg-red-500/20 text-red-400': project.status === 'cancelled'
                                          }"
                                          x-text="project.status.replace('_', ' ')">
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-300" x-text="project.start_date"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-300" x-text="project.end_date || 'Ongoing'"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-300" x-text="project.project_lead_id ? 'Assigned' : 'Unassigned'"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-400 text-sm" x-text="formatDate(project.created_at)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="projects.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-400">No projects yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Create your first project phase for this opportunity.</p>
                    <button @click="showCreateModal = true" 
                            class="inline-block mt-4 px-4 py-2 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all">
                        Create Project
                    </button>
                </div>
            </div>

            <!-- Info Note -->
            <div class="mt-6 rounded-xl bg-blue-500/10 border border-blue-500/30 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-blue-400">Multi-Phase Opportunities</p>
                        <p class="mt-1 text-sm text-blue-300/80">You can create multiple projects from this opportunity. Each project represents a phase or milestone in the overall deal. Projects maintain their own lifecycle independent of the opportunity status.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Project Modal -->
        <div x-show="showCreateModal" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/70 transition-opacity" 
                 @click="showCreateModal = false"></div>
            
            <!-- Modal -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-slate-900 rounded-xl shadow-2xl border border-white/10 max-w-2xl w-full p-6"
                     @click.stop>
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-gray-100">Create Project Phase</h3>
                        <p class="mt-1 text-sm text-gray-400">Add a new project linked to this opportunity</p>
                    </div>

                    <form @submit.prevent="createProject" class="space-y-4">
                        <!-- Project Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Project Name *</label>
                            <input type="text" 
                                   x-model="projectForm.name" 
                                   required 
                                   placeholder="e.g., Phase 1: Mobile App Development"
                                   class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                        </div>

                        <!-- Contract Value -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Contract Value *</label>
                                <input type="number" 
                                       x-model="projectForm.contract_value" 
                                       required 
                                       min="0" 
                                       step="0.01"
                                       placeholder="0.00"
                                       class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Currency *</label>
                                <select x-model="projectForm.contract_currency" 
                                        required
                                        class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                                    <option value="UGX">UGX</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Start Date *</label>
                                <input type="date" 
                                       x-model="projectForm.start_date" 
                                       required
                                       class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">End Date (Optional)</label>
                                <input type="date" 
                                       x-model="projectForm.end_date"
                                       class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                            <select x-model="projectForm.status"
                                    class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                                <option value="planned">Planned</option>
                                <option value="active">Active</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button type="button" 
                                    @click="showCreateModal = false" 
                                    class="flex-1 px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    :disabled="creating"
                                    class="flex-1 px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all text-white font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!creating">Create Project</span>
                                <span x-show="creating">Creating...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function opportunityProjects(opportunityId) {
            return {
                opportunityId: opportunityId,
                opportunity: null,
                projects: [],
                loading: true,
                error: null,
                successMessage: '',
                showCreateModal: false,
                creating: false,
                projectForm: {
                    name: '',
                    contract_value: '',
                    contract_currency: 'USD',
                    start_date: '',
                    end_date: '',
                    status: 'planned',
                    project_lead_id: null
                },

                async init() {
                    await this.fetchOpportunity();
                    await this.fetchProjects();
                },

                async fetchOpportunity() {
                    try {
                        const response = await fetch(`/api/opportunities/${this.opportunityId}`, {
                            headers: { 'Accept': 'application/json' }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to fetch opportunity');
                        }

                        this.opportunity = await response.json();
                        
                        // Set default currency from opportunity
                        this.projectForm.contract_currency = this.opportunity.currency || 'USD';
                    } catch (err) {
                        this.error = err.message;
                    }
                },

                async fetchProjects() {
                    try {
                        this.loading = true;
                        this.error = null;

                        const response = await fetch(`/api/opportunities/${this.opportunityId}/projects`, {
                            headers: { 'Accept': 'application/json' }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to fetch projects');
                        }

                        const result = await response.json();
                        this.projects = result.projects || [];
                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async createProject() {
                    try {
                        this.creating = true;
                        this.error = null;

                        const response = await fetch(`/api/opportunities/${this.opportunityId}/projects`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.projectForm)
                        });

                        const result = await response.json();

                        if (!result.success) {
                            throw new Error(result.message || 'Failed to create project');
                        }

                        this.successMessage = `Project created successfully! (ID: ${result.project_id})`;
                        setTimeout(() => this.successMessage = '', 5000);
                        
                        this.showCreateModal = false;
                        this.resetForm();
                        await this.fetchProjects();
                    } catch (err) {
                        this.error = err.message;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } finally {
                        this.creating = false;
                    }
                },

                resetForm() {
                    this.projectForm = {
                        name: '',
                        contract_value: '',
                        contract_currency: this.opportunity?.currency || 'USD',
                        start_date: '',
                        end_date: '',
                        status: 'planned',
                        project_lead_id: null
                    };
                },

                formatCurrency(value, currency = 'UGX') {
                    if (!value) return currency + ' 0.00';
                    return currency + ' ' + parseFloat(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                }
            };
        }
    </script>
</body>
</html>
