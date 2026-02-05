<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Opportunities - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen p-8" x-data="opportunitiesIndex()">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Opportunities</h1>
                    <p class="text-gray-400 mt-1">Manage sales opportunities and pipeline</p>
                </div>
                <div class="flex gap-4">
                    <a href="/" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Back to Dashboard
                    </a>
                    <a href="/opportunities/create" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all">
                        Create Opportunity
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-7xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading opportunities...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-6">
                <p class="text-red-400" x-text="error"></p>
            </div>
        </div>

        <!-- Delete Error Message -->
        <div x-show="deleteError" class="max-w-7xl mx-auto mb-6">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4">
                <p class="text-red-400" x-text="deleteError"></p>
            </div>
        </div>

        <!-- Delete Success Message -->
        <div x-show="deleteSuccess" class="max-w-7xl mx-auto mb-6">
            <div class="rounded-xl bg-green-500/10 border border-green-500/30 p-4">
                <p class="text-green-400" x-text="deleteSuccess"></p>
            </div>
        </div>

        <!-- Opportunities Table -->
        <div x-show="!loading && !error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Value</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Stage</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Probability</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Expected Close</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Owner</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <template x-for="opportunity in opportunities" :key="opportunity.id">
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium" x-text="opportunity.client"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-300 truncate max-w-xs" x-text="opportunity.description"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-indigo-400" x-text="formatCurrency(opportunity.estimated_value)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full capitalize" 
                                          :class="{
                                              'bg-gray-500/20 text-gray-400': opportunity.stage === 'lead',
                                              'bg-blue-500/20 text-blue-400': opportunity.stage === 'qualified',
                                              'bg-yellow-500/20 text-yellow-400': opportunity.stage === 'proposal',
                                              'bg-orange-500/20 text-orange-400': opportunity.stage === 'negotiation',
                                              'bg-green-500/20 text-green-400': opportunity.stage === 'won',
                                              'bg-red-500/20 text-red-400': opportunity.stage === 'lost'
                                          }"
                                          x-text="opportunity.stage">
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-300" x-text="opportunity.probability + '%'"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-300" x-text="opportunity.expected_close_date"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-300" x-text="opportunity.owner_email"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2 justify-end">
                                        <a :href="`/opportunities/${opportunity.id}/edit`" 
                                           class="px-3 py-1 text-sm rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all">
                                            Edit
                                        </a>
                                        <button @click="deleteOpportunity(opportunity.id)"
                                                class="px-3 py-1 text-sm rounded-lg bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-all">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="opportunities.length === 0" class="text-center py-12">
                    <p class="text-gray-400">No opportunities found.</p>
                    <a href="/opportunities/create" class="inline-block mt-4 px-4 py-2 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all">
                        Create your first opportunity
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function opportunitiesIndex() {
            return {
                opportunities: [],
                loading: true,
                error: null,
                deleteError: '',
                deleteSuccess: '',

                async init() {
                    await this.fetchOpportunities();
                },

                async fetchOpportunities() {
                    try {
                        this.loading = true;
                        this.error = null;

                        const response = await fetch('/api/opportunities', {
                            headers: {
                                'Accept': 'application/json',
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to fetch opportunities');
                        }

                        this.opportunities = await response.json();
                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async deleteOpportunity(opportunityId) {
                    if (!confirm('Are you sure you want to delete this opportunity?')) {
                        return;
                    }

                    this.deleteError = '';
                    this.deleteSuccess = '';

                    try {
                        const response = await fetch(`/api/opportunities/${opportunityId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const result = await response.json();

                        if (!result.success) {
                            this.deleteError = result.message || 'Failed to delete opportunity';
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                            return;
                        }

                        this.deleteSuccess = result.message;
                        setTimeout(() => this.deleteSuccess = '', 3000);
                        await this.fetchOpportunities();
                    } catch (err) {
                        this.deleteError = err.message || 'An error occurred while deleting the opportunity';
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                formatCurrency(value) {
                    return 'UGX ' + parseFloat(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            };
        }
    </script>
</body>
</html>
