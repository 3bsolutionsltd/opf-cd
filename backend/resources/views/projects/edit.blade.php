<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Project - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        option { background-color: rgb(15 23 42); color: rgb(243 244 246); }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen p-8" x-data="projectEdit({{ $projectId }})">
        <!-- Header -->
        <div class="max-w-3xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Edit Project</h1>
                    <p class="text-gray-400 mt-1">Update project details</p>
                </div>
                <a href="/projects" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                    Back to Projects
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-3xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading project...</p>
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

        <!-- Immutability Warning -->
        <div x-show="hasPayments && !loading" class="max-w-3xl mx-auto mb-6">
            <div class="rounded-xl bg-yellow-500/10 border border-yellow-500/30 p-4">
                <p class="text-yellow-400">⚠️ Contract value is locked because payments have been received for this project.</p>
            </div>
        </div>

        <!-- Form -->
        <div x-show="!loading" class="max-w-3xl mx-auto">
            <form @submit.prevent="submitUpdate" class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-8 space-y-6">
                
                <!-- Project Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Project Name *</label>
                    <input type="text" x-model="form.name" required
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.name" class="mt-1 text-sm text-red-400" x-text="errors.name"></p>
                </div>

                <!-- Client -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Client *</label>
                    <input type="text" x-model="form.client" required
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.client" class="mt-1 text-sm text-red-400" x-text="errors.client"></p>
                </div>

                <!-- Contract Value & Currency -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Contract Value *</label>
                        <input type="number" step="0.01" x-model="form.contract_value" required
                               :readonly="hasPayments"
                               :class="hasPayments ? 'bg-white/10 cursor-not-allowed' : ''"
                               class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                        <p x-show="errors.contract_value" class="mt-1 text-sm text-red-400" x-text="errors.contract_value"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Currency *</label>
                        <select x-model="form.contract_currency" required
                                class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                            <option value="UGX">UGX</option>
                            <option value="USD">USD</option>
                        </select>
                        <p x-show="errors.contract_currency" class="mt-1 text-sm text-red-400" x-text="errors.contract_currency"></p>
                    </div>
                </div>

                <!-- Start & End Date -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Start Date *</label>
                        <input type="date" x-model="form.start_date" required
                               class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                        <p x-show="errors.start_date" class="mt-1 text-sm text-red-400" x-text="errors.start_date"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">End Date *</label>
                        <input type="date" x-model="form.end_date" required
                               class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                        <p x-show="errors.end_date" class="mt-1 text-sm text-red-400" x-text="errors.end_date"></p>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Status *</label>
                    <select x-model="form.status" required
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="planned">Planned</option>
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <p x-show="errors.status" class="mt-1 text-sm text-red-400" x-text="errors.status"></p>
                </div>

                <!-- Project Lead -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Project Lead (Optional)</label>
                    <input type="number" x-model="form.project_lead_id" placeholder="User ID"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.project_lead_id" class="mt-1 text-sm text-red-400" x-text="errors.project_lead_id"></p>
                </div>

                <!-- Submit Button -->
                <div class="flex gap-4">
                    <button type="submit" :disabled="submitting"
                            class="px-6 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Update Project</span>
                        <span x-show="submitting">Updating...</span>
                    </button>
                    <a href="/projects" class="px-6 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function projectEdit(projectId) {
            return {
                projectId: projectId,
                form: {
                    name: '',
                    client: '',
                    contract_value: '',
                    contract_currency: 'UGX',
                    start_date: '',
                    end_date: '',
                    status: 'planned',
                    project_lead_id: ''
                },
                hasPayments: false,
                errors: {},
                loading: true,
                submitting: false,
                successMessage: '',
                errorMessage: '',

                async init() {
                    await this.fetchProject();
                    await this.checkPayments();
                },

                async fetchProject() {
                    try {
                        const response = await fetch(`/api/projects/${this.projectId}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('Failed to fetch project');
                        }
                        const project = await response.json();
                        
                        this.form.name = project.name;
                        this.form.client = project.client;
                        this.form.contract_value = project.contract_value;
                        this.form.contract_currency = project.contract_currency;
                        this.form.start_date = project.start_date;
                        this.form.end_date = project.end_date;
                        this.form.status = project.status;
                        this.form.project_lead_id = project.project_lead_id || '';
                    } catch (e) {
                        this.errorMessage = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async checkPayments() {
                    try {
                        const response = await fetch(`/api/projects/${this.projectId}/has-payments`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (response.ok) {
                            const data = await response.json();
                            this.hasPayments = data.has_payments;
                        }
                    } catch (e) {
                        console.error('Failed to check payments:', e);
                    }
                },

                async submitUpdate() {
                    this.submitting = true;
                    this.errors = {};
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        const response = await fetch(`/api/projects/${this.projectId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.successMessage = data.message;
                            setTimeout(() => {
                                window.location.href = '/projects';
                            }, 1500);
                        } else {
                            if (data.errors) {
                                this.errors = data.errors;
                            } else {
                                this.errorMessage = data.message || 'Failed to update project';
                            }
                        }
                    } catch (e) {
                        this.errorMessage = 'Error updating project: ' + e.message;
                    } finally {
                        this.submitting = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
