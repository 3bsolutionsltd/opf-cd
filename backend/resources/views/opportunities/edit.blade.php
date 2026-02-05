<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Opportunity - OPF-CD</title>
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
    <div class="min-h-screen p-8" x-data="opportunityEdit({{ $opportunityId }})">
        <!-- Header -->
        <div class="max-w-3xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Edit Opportunity</h1>
                    <p class="text-gray-400 mt-1">Update opportunity details</p>
                </div>
                <a href="/opportunities" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                    Cancel
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-3xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading opportunity...</p>
        </div>

        <!-- Success Message -->
        <div x-show="successMessage" class="max-w-3xl mx-auto mb-6">
            <div class="rounded-xl bg-green-500/10 border border-green-500/30 p-4">
                <p class="text-green-400" x-text="successMessage"></p>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="errorMessage" class="max-w-3xl mx-auto mb-6">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4">
                <p class="text-red-400" x-text="errorMessage"></p>
            </div>
        </div>

        <!-- Form -->
        <div x-show="!loading" class="max-w-3xl mx-auto">
            <form @submit.prevent="submitUpdate" class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-8 space-y-6">
                <!-- Client -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Client Name *</label>
                    <input type="text" x-model="form.client" required placeholder="e.g., ABC Corporation"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.client" class="mt-1 text-sm text-red-400" x-text="errors.client"></p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Description *</label>
                    <input type="text" x-model="form.description" required placeholder="e.g., Website redesign project"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.description" class="mt-1 text-sm text-red-400" x-text="errors.description"></p>
                </div>

                <!-- Estimated Value -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Estimated Value (UGX) *</label>
                    <input type="number" x-model="form.estimated_value" required min="0" step="0.01" placeholder="0.00"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.estimated_value" class="mt-1 text-sm text-red-400" x-text="errors.estimated_value"></p>
                </div>

                <!-- Probability -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Probability: <span x-text="form.probability + '%'" class="text-indigo-400 font-semibold"></span>
                    </label>
                    <input type="range" x-model="form.probability" min="0" max="100" step="5"
                           class="w-full h-2 rounded-lg appearance-none cursor-pointer"
                           style="background: linear-gradient(to right, #6366f1, #a855f7);">
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>0%</span>
                        <span>25%</span>
                        <span>50%</span>
                        <span>75%</span>
                        <span>100%</span>
                    </div>
                    <p x-show="errors.probability" class="mt-1 text-sm text-red-400" x-text="errors.probability"></p>
                </div>

                <!-- Stage -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Stage *</label>
                    <select x-model="form.stage" required
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="">Select Stage</option>
                        <option value="lead">Lead</option>
                        <option value="qualified">Qualified</option>
                        <option value="proposal">Proposal</option>
                        <option value="negotiation">Negotiation</option>
                        <option value="won">Won</option>
                        <option value="lost">Lost</option>
                    </select>
                    <p x-show="errors.stage" class="mt-1 text-sm text-red-400" x-text="errors.stage"></p>
                </div>

                <!-- Source -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Source *</label>
                    <input type="text" x-model="form.source" required placeholder="e.g., Referral, LinkedIn, Cold Call"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.source" class="mt-1 text-sm text-red-400" x-text="errors.source"></p>
                </div>

                <!-- Owner -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Owner *</label>
                    <select x-model="form.owner" required
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="">Select Owner</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" x-text="user.email"></option>
                        </template>
                    </select>
                    <p x-show="errors.owner" class="mt-1 text-sm text-red-400" x-text="errors.owner"></p>
                </div>

                <!-- Expected Close Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Expected Close Date *</label>
                    <input type="date" x-model="form.expected_close_date" required
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.expected_close_date" class="mt-1 text-sm text-red-400" x-text="errors.expected_close_date"></p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <a href="/opportunities" class="px-6 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Cancel
                    </a>
                    <button type="submit" :disabled="submitting"
                            class="px-6 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Update Opportunity</span>
                        <span x-show="submitting">Updating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function opportunityEdit(opportunityId) {
            return {
                opportunityId: opportunityId,
                form: {
                    client: '',
                    description: '',
                    estimated_value: '',
                    probability: 50,
                    stage: '',
                    source: '',
                    owner: '',
                    expected_close_date: ''
                },
                users: [],
                errors: {},
                loading: true,
                submitting: false,
                successMessage: '',
                errorMessage: '',

                async init() {
                    await Promise.all([
                        this.fetchOpportunity(),
                        this.fetchUsers()
                    ]);
                },

                async fetchOpportunity() {
                    try {
                        const response = await fetch(`/api/opportunities/${this.opportunityId}`, {
                            headers: { 'Accept': 'application/json' }
                        });

                        if (!response.ok) {
                            throw new Error('Opportunity not found');
                        }

                        const data = await response.json();
                        
                        this.form = {
                            client: data.client || '',
                            description: data.description || '',
                            estimated_value: data.estimated_value || '',
                            probability: data.probability || 50,
                            stage: data.stage || '',
                            source: data.source || '',
                            owner: data.owner || '',
                            expected_close_date: data.expected_close_date || ''
                        };
                    } catch (error) {
                        this.errorMessage = error.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async fetchUsers() {
                    try {
                        const response = await fetch('/api/users', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (response.ok) {
                            this.users = await response.json();
                        }
                    } catch (e) {
                        console.error('Failed to fetch users:', e);
                    }
                },

                async submitUpdate() {
                    this.submitting = true;
                    this.errors = {};
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        const response = await fetch(`/api/opportunities/${this.opportunityId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            if (data.errors) {
                                this.errors = data.errors;
                            }
                            this.errorMessage = data.message || 'Failed to update opportunity';
                            return;
                        }

                        this.successMessage = data.message || 'Opportunity updated successfully!';
                        
                        // Redirect after 2 seconds
                        setTimeout(() => {
                            window.location.href = '/opportunities';
                        }, 2000);
                    } catch (error) {
                        this.errorMessage = 'An unexpected error occurred. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
