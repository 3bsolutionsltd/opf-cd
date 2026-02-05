<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Expense - OPF-CD</title>
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
    <div class="min-h-screen p-8" x-data="expenseEdit({{ $expenseId }})">
        <!-- Header -->
        <div class="max-w-3xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Edit Expense</h1>
                    <p class="text-gray-400 mt-1">Update expense details</p>
                    <div x-show="isPaid && !loading" class="mt-2 px-4 py-2 rounded-lg bg-amber-500/10 border border-amber-500/30">
                        <p class="text-amber-400 text-sm">🔒 This expense is paid and cannot be edited.</p>
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
            <p class="mt-4 text-gray-400">Loading expense...</p>
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
                <!-- Expense Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Expense Name *</label>
                    <input type="text" x-model="form.name" required :disabled="isPaid"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <p x-show="errors.name" class="mt-1 text-sm text-red-400" x-text="errors.name"></p>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Category *</label>
                    <input type="text" x-model="form.category" required :disabled="isPaid"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <p x-show="errors.category" class="mt-1 text-sm text-red-400" x-text="errors.category"></p>
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

                <!-- Expense Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Expense Type *</label>
                    <select x-model="form.type" required :disabled="isPaid" @change="handleTypeChange"
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Select Type</option>
                        <option value="one_off">One-off</option>
                        <option value="recurring">Recurring</option>
                    </select>
                    <p x-show="errors.type" class="mt-1 text-sm text-red-400" x-text="errors.type"></p>
                </div>

                <!-- Frequency (only for recurring) -->
                <div x-show="form.type === 'recurring'">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Frequency *</label>
                    <select x-model="form.frequency" :required="form.type === 'recurring'" :disabled="isPaid"
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Select Frequency</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="annual">Annual</option>
                    </select>
                    <p x-show="errors.frequency" class="mt-1 text-sm text-red-400" x-text="errors.frequency"></p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                    <select x-model="form.status" :disabled="isPaid"
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="due">Due</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                    <p class="mt-1 text-xs text-amber-400">⚠️ Note: Once marked as paid, the expense becomes immutable.</p>
                    <p x-show="errors.status" class="mt-1 text-sm text-red-400" x-text="errors.status"></p>
                </div>

                <!-- Project Association (Optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Project (Optional)</label>
                    <input type="number" x-model="form.project_id" :disabled="isPaid"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <p x-show="errors.project_id" class="mt-1 text-sm text-red-400" x-text="errors.project_id"></p>
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
                        <span x-show="!submitting">Update Expense</span>
                        <span x-show="submitting">Updating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function expenseEdit(expenseId) {
            return {
                expenseId: expenseId,
                isPaid: false,
                loading: true,
                form: {
                    name: '',
                    category: '',
                    amount: '',
                    currency: '',
                    type: '',
                    frequency: '',
                    status: 'due',
                    project_id: '',
                    due_date: ''
                },
                errors: {},
                submitting: false,
                successMessage: '',
                errorMessage: '',

                async init() {
                    await this.fetchExpense();
                },

                async fetchExpense() {
                    try {
                        const response = await fetch(`/api/expenses/${this.expenseId}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('Failed to fetch expense');
                        }
                        const data = await response.json();
                        const expense = data.expense;
                        
                        this.isPaid = expense.is_paid;
                        this.form.name = expense.name;
                        this.form.category = expense.category;
                        this.form.amount = expense.amount;
                        this.form.currency = expense.currency;
                        this.form.type = expense.type;
                        this.form.frequency = expense.frequency || '';
                        this.form.status = expense.status;
                        this.form.project_id = expense.project_id || '';
                        this.form.due_date = expense.due_date;
                    } catch (e) {
                        this.errorMessage = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                handleTypeChange() {
                    if (this.form.type !== 'recurring') {
                        this.form.frequency = '';
                    }
                },

                async submitUpdate() {
                    if (this.isPaid) {
                        this.errorMessage = 'Cannot edit paid expenses. Financial records are immutable.';
                        return;
                    }

                    this.submitting = true;
                    this.errors = {};
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        const response = await fetch(`/api/expenses/${this.expenseId}`, {
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
                                window.location.href = '/expenses';
                            }, 1500);
                        } else {
                            if (data.errors) {
                                this.errors = data.errors;
                            }
                            this.errorMessage = data.message || 'Failed to update expense.';
                        }
                    } catch (e) {
                        this.errorMessage = 'Error updating expense: ' + e.message;
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
