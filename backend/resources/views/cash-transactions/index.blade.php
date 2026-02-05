<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cash Transactions - OPF-CD</title>
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
    <div class="min-h-screen p-8" x-data="transactionsIndex()">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <!-- Breadcrumb -->
            <div class="mb-4 text-sm text-gray-400">
                <a href="/" class="hover:text-gray-200 transition-colors">Dashboard</a>
                <span class="mx-2">→</span>
                <span class="text-gray-200">Cash Transactions</span>
            </div>

            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Cash Transactions</h1>
                    <p class="text-gray-400 mt-1">Track cash inflows and outflows</p>
                </div>
                <a href="/cash-transactions/create" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all">
                    Record Transaction
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-7xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading transactions...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-6">
                <p class="text-red-400" x-text="error"></p>
            </div>
        </div>

        <!-- Filter Section -->
        <div x-show="!loading && !error" class="max-w-7xl mx-auto mb-6">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
                <div class="flex items-center gap-4">
                    <label for="account_filter" class="text-sm font-medium text-gray-300">Filter by Account:</label>
                    <select id="account_filter" 
                            x-model="filterAccountId" 
                            @change="fetchTransactions"
                            class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Accounts</option>
                        <template x-for="account in accounts" :key="account.id">
                            <option :value="account.id" x-text="`${account.name} (${account.type.replace('_', ' ')})`"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div x-show="!loading && !error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Account</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Transaction Type</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <template x-for="transaction in transactions" :key="transaction.id">
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-gray-300" x-text="transaction.transaction_date"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium" x-text="transaction.account_name"></div>
                                    <span class="inline-block mt-1 px-2 py-1 text-xs rounded-full capitalize" 
                                          :class="{
                                              'bg-blue-500/20 text-blue-400': transaction.account_type === 'bank',
                                              'bg-purple-500/20 text-purple-400': transaction.account_type === 'mobile_money',
                                              'bg-green-500/20 text-green-400': transaction.account_type === 'cash'
                                          }"
                                          x-text="transaction.account_type.replace('_', ' ')">
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                          :class="{
                                              'bg-green-500/20 text-green-400': transaction.type === 'inflow',
                                              'bg-red-500/20 text-red-400': transaction.type === 'outflow'
                                          }"
                                          x-text="transaction.type === 'inflow' ? 'Inflow' : 'Outflow'">
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-300 uppercase text-sm" x-text="transaction.type"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-indigo-400" x-text="formatCurrency(transaction.amount, transaction.currency)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div x-text="formatSourceType(transaction.source_type)"></div>
                                    <div class="text-xs text-gray-500 mt-1" x-text="'ID: ' + transaction.source_id"></div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="transactions.length === 0" class="text-center py-12">
                    <p class="text-gray-400">No transactions found.</p>
                    <a href="/cash-transactions/create" class="inline-block mt-4 px-4 py-2 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all">
                        Record your first transaction
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function transactionsIndex() {
            return {
                transactions: [],
                accounts: [],
                filterAccountId: '',
                loading: true,
                error: null,

                async init() {
                    await this.fetchAccounts();
                    await this.fetchTransactions();
                },

                async fetchAccounts() {
                    try {
                        const response = await fetch('/api/accounts', {
                            headers: {
                                'Accept': 'application/json',
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to fetch accounts');
                        }

                        this.accounts = await response.json();
                    } catch (err) {
                        console.error('Error fetching accounts:', err);
                    }
                },

                async fetchTransactions() {
                    try {
                        this.loading = true;
                        this.error = null;

                        let url = '/api/cash-transactions';
                        if (this.filterAccountId) {
                            url += `?account_id=${this.filterAccountId}`;
                        }

                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to fetch transactions');
                        }

                        this.transactions = await response.json();
                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        this.loading = false;
                    }
                },

                formatCurrency(value, currency) {
                    return `${currency} ${parseFloat(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}`;
                },

                formatSourceType(sourceType) {
                    const types = {
                        'project': 'Project',
                        'expense': 'Expense',
                        'milestone': 'Payment Milestone',
                        'opportunity': 'Opportunity',
                        'manual': 'Manual Entry',
                        // Legacy values
                        'project_payment': 'Project Payment',
                        'client_invoice': 'Client Invoice'
                    };
                    return types[sourceType] || sourceType;
                }
            };
        }
    </script>
</body>
</html>
