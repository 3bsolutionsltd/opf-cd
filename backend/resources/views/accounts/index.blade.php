<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accounts - OPF-CD</title>
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
    <div class="min-h-screen p-8" x-data="accountsIndex()">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Accounts</h1>
                    <p class="text-gray-400 mt-1">Manage financial accounts (bank, mobile money, cash)</p>
                </div>
                <div class="flex gap-4">
                    <a href="/" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Back to Dashboard
                    </a>
                    <a href="/accounts/create" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all">
                        Create Account
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-7xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading accounts...</p>
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

        <!-- Accounts Table -->
        <div x-show="!loading && !error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Account Name</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Currency</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Opening Balance</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <template x-for="account in accounts" :key="account.id">
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium" x-text="account.name"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full capitalize" 
                                          :class="{
                                              'bg-blue-500/20 text-blue-400': account.type === 'bank',
                                              'bg-purple-500/20 text-purple-400': account.type === 'mobile_money',
                                              'bg-green-500/20 text-green-400': account.type === 'cash'
                                          }"
                                          x-text="account.type.replace('_', ' ')">
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-300" x-text="account.currency"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-indigo-400" x-text="formatCurrency(account.opening_balance, account.currency)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2 justify-end">
                                        <a :href="`/accounts/${account.id}/edit`" 
                                           class="px-3 py-1 text-sm rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all">
                                            Edit
                                        </a>
                                        <button @click="deleteAccount(account.id)"
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
                <div x-show="accounts.length === 0" class="text-center py-12">
                    <p class="text-gray-400">No accounts found.</p>
                    <a href="/accounts/create" class="inline-block mt-4 px-4 py-2 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/30 transition-all">
                        Create your first account
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function accountsIndex() {
            return {
                accounts: [],
                loading: true,
                error: null,
                deleteError: '',
                deleteSuccess: '',

                async init() {
                    await this.fetchAccounts();
                },

                async fetchAccounts() {
                    try {
                        this.loading = true;
                        this.error = null;

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
                        this.error = err.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async deleteAccount(accountId) {
                    if (!confirm('Are you sure you want to delete this account? This will fail if transactions exist.')) {
                        return;
                    }

                    // Clear previous messages
                    this.deleteError = '';
                    this.deleteSuccess = '';

                    try {
                        const response = await fetch(`/api/accounts/${accountId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const result = await response.json();

                        if (!result.success) {
                            this.deleteError = result.message || 'Failed to delete account';
                            // Scroll to top to show error
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                            return;
                        }

                        this.deleteSuccess = result.message;
                        setTimeout(() => this.deleteSuccess = '', 3000);
                        await this.fetchAccounts();
                    } catch (err) {
                        this.deleteError = err.message || 'An error occurred while deleting the account';
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },

                formatCurrency(value, currency) {
                    return currency + ' ' + parseFloat(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            };
        }
    </script>
</body>
</html>
