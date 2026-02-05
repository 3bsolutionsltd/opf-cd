<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Expenses - OPF-CD</title>
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
    <div class="min-h-screen p-8" x-data="expensesIndex()">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Expenses</h1>
                    <p class="text-gray-400 mt-1">Manage company and project expenses</p>
                    <div x-show="summary && summary.currencies" class="mt-3 space-y-2">
                        <template x-for="(data, currency) in summary.currencies" :key="currency">
                            <div class="flex gap-6 text-sm">
                                <span class="font-semibold text-gray-400" x-text="currency + ':'"></span>
                                <p class="text-gray-500">
                                    Due: <span class="font-semibold text-yellow-400" x-text="formatAmount(data.due)"></span>
                                </p>
                                <p class="text-gray-500">
                                    Paid: <span class="font-semibold text-green-400" x-text="formatAmount(data.paid)"></span>
                                </p>
                                <p class="text-gray-500">
                                    Overdue: <span class="font-semibold text-red-400" x-text="formatAmount(data.overdue)"></span>
                                </p>
                                <p class="text-gray-500">
                                    Total: <span class="font-semibold text-indigo-400" x-text="formatAmount(data.total)"></span>
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex gap-4">
                    <a href="/" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Back to Dashboard
                    </a>
                    <a href="/expenses/create" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all">
                        Create Expense
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-7xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading expenses...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-6">
                <p class="text-red-400" x-text="error"></p>
            </div>
        </div>

        <!-- Expenses Table -->
        <div x-show="!loading && !error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <template x-for="expense in expenses" :key="expense.id">
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium" x-text="expense.name"></div>
                                </td>
                                <td class="px-6 py-4 text-gray-300" x-text="expense.category"></td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-indigo-400" x-text="expense.currency + ' ' + expense.amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                          :class="{
                                              'bg-purple-500/20 text-purple-400': expense.type === 'recurring',
                                              'bg-gray-500/20 text-gray-400': expense.type === 'one_off'
                                          }">
                                        <span x-text="expense.type === 'recurring' ? expense.frequency : 'one-off'"></span>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                          :class="{
                                              'bg-yellow-500/20 text-yellow-400': expense.status === 'due',
                                              'bg-green-500/20 text-green-400': expense.status === 'paid',
                                              'bg-red-500/20 text-red-400': expense.status === 'overdue'
                                          }"
                                          x-text="expense.status"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-300" x-text="expense.due_date"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <template x-if="!expense.is_paid">
                                            <a :href="'/expenses/' + expense.id + '/edit'" class="px-3 py-1 text-sm rounded-lg bg-indigo-500/20 hover:bg-indigo-500/30 border border-indigo-500/30 text-indigo-400 transition-all">
                                                Edit
                                            </a>
                                        </template>
                                        <template x-if="expense.is_paid">
                                            <span class="px-3 py-1 text-sm rounded-lg bg-gray-500/10 border border-gray-500/20 text-gray-500 cursor-not-allowed" title="Paid expenses cannot be edited">
                                                Locked
                                            </span>
                                        </template>
                                        <template x-if="!expense.is_paid">
                                            <button @click="deleteExpense(expense.id)" class="px-3 py-1 text-sm rounded-lg bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 text-red-400 transition-all">
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
                <div x-show="expenses.length === 0" class="text-center py-12 text-gray-400">
                    <p>No expenses found</p>
                    <a href="/expenses/create" class="inline-block mt-4 text-indigo-400 hover:text-indigo-300">
                        Create your first expense
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function expensesIndex() {
            return {
                expenses: [],
                summary: null,
                loading: true,
                error: '',

                async init() {
                    await Promise.all([
                        this.fetchExpenses(),
                        this.fetchSummary()
                    ]);
                },

                async fetchExpenses() {
                    this.loading = true;
                    this.error = '';

                    try {
                        const response = await fetch('/api/expenses', {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('Failed to fetch expenses');
                        }
                        const data = await response.json();
                        this.expenses = data.expenses;
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async fetchSummary() {
                    try {
                        const response = await fetch('/api/expenses/summary', {
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

                async deleteExpense(expenseId) {
                    if (!confirm('Are you sure you want to delete this expense?')) {
                        return;
                    }

                    try {
                        const response = await fetch(`/api/expenses/${expenseId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            await Promise.all([
                                this.fetchExpenses(),
                                this.fetchSummary()
                            ]);
                        } else {
                            alert(data.message || 'Failed to delete expense');
                        }
                    } catch (e) {
                        alert('Error deleting expense: ' + e.message);
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
