<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Milestone - OPF-CD</title>
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
    <div class="min-h-screen p-8" x-data="milestoneEdit({{ $milestoneId }})">
        <!-- Header -->
        <div class="max-w-3xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Edit Milestone</h1>
                    <p class="text-gray-400 mt-1">Update milestone details</p>
                    <div x-show="isPaid && !loading" class="mt-2 px-4 py-2 rounded-lg bg-amber-500/10 border border-amber-500/30">
                        <p class="text-amber-400 text-sm">🔒 This milestone is paid and cannot be edited.</p>
                    </div>
                </div>
                <a href="javascript:history.back()" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                    Cancel
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-3xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading milestone...</p>
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
                <!-- Milestone Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Milestone Name *</label>
                    <input type="text" x-model="form.name" required :disabled="isPaid"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <p x-show="errors.name" class="mt-1 text-sm text-red-400" x-text="errors.name"></p>
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Amount *</label>
                    <input type="number" x-model="form.amount" required min="0" step="0.01" :disabled="isPaid"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <p x-show="errors.amount" class="mt-1 text-sm text-red-400" x-text="errors.amount"></p>
                </div>

                <!-- Currency -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Currency *</label>
                    <select x-model="form.currency" required :disabled="isPaid"
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Select Currency</option>
                        <option value="UGX">UGX - Ugandan Shilling</option>
                        <option value="USD">USD - US Dollar</option>
                    </select>
                    <p x-show="errors.currency" class="mt-1 text-sm text-red-400" x-text="errors.currency"></p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                    <select x-model="form.status" :disabled="isPaid"
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="pending">Pending</option>
                        <option value="invoiced">Invoiced</option>
                        <option value="paid">Paid</option>
                    </select>
                    <p class="mt-1 text-xs text-amber-400">⚠️ Note: Once marked as paid, the milestone becomes immutable.</p>
                    <p x-show="errors.status" class="mt-1 text-sm text-red-400" x-text="errors.status"></p>
                </div>

                <!-- Due Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Due Date *</label>
                    <input type="date" x-model="form.due_date" required :disabled="isPaid"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <p x-show="errors.due_date" class="mt-1 text-sm text-red-400" x-text="errors.due_date"></p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <a href="javascript:history.back()" class="px-6 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Cancel
                    </a>
                    <button type="submit" :disabled="submitting || isPaid"
                            class="px-6 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Update Milestone</span>
                        <span x-show="submitting">Updating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function milestoneEdit(milestoneId) {
            return {
                milestoneId: milestoneId,
                projectId: null,
                isPaid: false,
                loading: true,
                form: {
                    name: '',
                    amount: '',
                    currency: '',
                    status: 'pending',
                    due_date: ''
                },
                errors: {},
                submitting: false,
                successMessage: '',
                errorMessage: '',

                async init() {
                    await this.fetchMilestone();
                },

                async fetchMilestone() {
                    try {
                        const response = await fetch(`/api/milestones/${this.milestoneId}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('Failed to fetch milestone');
                        }
                        const data = await response.json();
                        const milestone = data.milestone;
                        
                        this.projectId = milestone.project_id;
                        this.isPaid = milestone.is_paid;
                        this.form.name = milestone.name;
                        this.form.amount = milestone.amount;
                        this.form.currency = milestone.currency;
                        this.form.status = milestone.status;
                        this.form.due_date = milestone.due_date;
                    } catch (e) {
                        this.errorMessage = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async submitUpdate() {
                    if (this.isPaid) {
                        this.errorMessage = 'Cannot edit paid milestones. Financial records are immutable.';
                        return;
                    }

                    this.submitting = true;
                    this.errors = {};
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        const response = await fetch(`/api/milestones/${this.milestoneId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.successMessage = data.message;
                            setTimeout(() => {
                                window.location.href = `/projects/${this.projectId}/milestones`;
                            }, 1500);
                        } else {
                            if (data.errors) {
                                this.errors = data.errors;
                            }
                            this.errorMessage = data.message || 'Failed to update milestone.';
                        }
                    } catch (e) {
                        this.errorMessage = 'Error updating milestone: ' + e.message;
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
