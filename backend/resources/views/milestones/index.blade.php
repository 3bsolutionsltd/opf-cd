<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Milestones - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen p-8" x-data="milestonesIndex({{ $projectId }})">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Payment Milestones</h1>
                    <p class="text-gray-400 mt-1">Manage payment milestones for this project</p>
                    <div x-show="summary && summary.currencies" class="mt-3 space-y-2">
                        <template x-for="(data, currency) in summary.currencies" :key="currency">
                            <div class="flex gap-6 text-sm">
                                <span class="font-semibold text-gray-400" x-text="currency + ':'"></span>
                                <p class="text-gray-500">
                                    Pending: <span class="font-semibold text-yellow-400" x-text="formatAmount(data.pending)"></span>
                                </p>
                                <p class="text-gray-500">
                                    Invoiced: <span class="font-semibold text-blue-400" x-text="formatAmount(data.invoiced)"></span>
                                </p>
                                <p class="text-gray-500">
                                    Paid: <span class="font-semibold text-green-400" x-text="formatAmount(data.paid)"></span>
                                </p>
                                <p class="text-gray-500">
                                    Total: <span class="font-semibold text-indigo-400" x-text="formatAmount(data.total)"></span>
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex gap-4">
                    <a href="/projects" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Back to Projects
                    </a>
                    <a :href="'/projects/' + projectId + '/milestones/create'" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all">
                        Create Milestone
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-7xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading milestones...</p>
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

        <!-- Milestones Table -->
        <div x-show="!loading && !error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <template x-for="milestone in milestones" :key="milestone.id">
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium" x-text="milestone.name"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-indigo-400" x-text="milestone.currency + ' ' + milestone.amount.toFixed(2)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                          :class="{
                                              'bg-yellow-500/20 text-yellow-400': milestone.status === 'pending',
                                              'bg-blue-500/20 text-blue-400': milestone.status === 'invoiced',
                                              'bg-green-500/20 text-green-400': milestone.status === 'paid'
                                          }"
                                          x-text="milestone.status"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-300" x-text="milestone.due_date"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <template x-if="!milestone.is_paid">
                                            <a :href="'/milestones/' + milestone.id + '/edit'" class="px-3 py-1 text-sm rounded-lg bg-indigo-500/20 hover:bg-indigo-500/30 border border-indigo-500/30 text-indigo-400 transition-all">
                                                Edit
                                            </a>
                                        </template>
                                        <template x-if="milestone.is_paid">
                                            <span class="px-3 py-1 text-sm rounded-lg bg-gray-500/10 border border-gray-500/20 text-gray-500 cursor-not-allowed" title="Paid milestones cannot be edited">
                                                Locked
                                            </span>
                                        </template>
                                        <template x-if="!milestone.is_paid">
                                            <button @click="confirmDelete(milestone.id, milestone.title)" class="px-3 py-1 text-sm rounded-lg bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 text-red-400 transition-all">
                                                Delete
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="milestones.length === 0" class="text-center py-12 text-gray-400">
                    <p>No milestones found</p>
                    <a :href="'/projects/' + projectId + '/milestones/create'" class="inline-block mt-4 text-indigo-400 hover:text-indigo-300">
                        Create your first milestone
                    </a>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/70 transition-opacity" 
                 @click="showDeleteModal = false"></div>
            
            <!-- Modal -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-slate-900 rounded-xl shadow-2xl border border-white/10 max-w-md w-full p-6"
                     @click.stop>
                    <div class="mb-4">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-500/20">
                            <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-100">Delete Milestone</h3>
                        <p class="mt-2 text-sm text-gray-400">
                            Are you sure you want to delete <span class="font-semibold text-gray-200" x-text="deleteTarget?.name"></span>? 
                            This action cannot be undone.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" 
                                class="flex-1 px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-gray-300">
                            Cancel
                        </button>
                        <button @click="executeDelete()" 
                                class="flex-1 px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 transition-all text-white font-medium">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function milestonesIndex(projectId) {
            return {
                projectId: projectId,
                milestones: [],
                summary: null,
                loading: true,
                error: '',
                deleteError: '',
                deleteSuccess: '',
                showDeleteModal: false,
                deleteTarget: null,

                async init() {
                    await Promise.all([
                        this.fetchMilestones(),
                        this.fetchSummary()
                    ]);
                },

                async fetchMilestones() {
                    this.loading = true;
                    this.error = '';

                    try {
                        const response = await fetch(`/api/projects/${this.projectId}/milestones`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('Failed to fetch milestones');
                        }
                        const data = await response.json();
                        this.milestones = data.milestones;
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async fetchSummary() {
                    try {
                        const response = await fetch(`/api/projects/${this.projectId}/milestones/summary`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (response.ok) {
                            const data = await response.json();
                            this.summary = data.summary;
                        }
                    } catch (e) {
                        console.error('Failed to fetch summary:', e);
                    }
                },

                confirmDelete(milestoneId, milestoneName) {
                    this.deleteTarget = { id: milestoneId, name: milestoneName };
                    this.showDeleteModal = true;
                },

                async executeDelete() {
                    if (!this.deleteTarget) return;
                    
                    this.showDeleteModal = false;
                    const milestoneId = this.deleteTarget.id;
                    this.deleteTarget = null;

                    this.deleteError = '';
                    this.deleteSuccess = '';

                    try {
                        const response = await fetch(`/api/milestones/${milestoneId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.deleteSuccess = data.message || 'Milestone deleted successfully';
                            setTimeout(() => this.deleteSuccess = '', 3000);
                            await Promise.all([
                                this.fetchMilestones(),
                                this.fetchSummary()
                            ]);
                        } else {
                            this.deleteError = data.message || 'Failed to delete milestone';
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    } catch (e) {
                        this.deleteError = 'Error deleting milestone: ' + e.message;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                formatAmount(amount) {
                    return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            };
        }
    </script>
</body>
</html>
