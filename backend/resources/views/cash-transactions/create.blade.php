<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Record Cash Transaction - OPF-CD</title>
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
    <div class="min-h-screen p-8" x-data="transactionCreate()">
        <!-- Header -->
        <div class="max-w-3xl mx-auto mb-8">
            <!-- Breadcrumb -->
            <div class="mb-4 text-sm text-gray-400">
                <a href="/" class="hover:text-gray-200 transition-colors">Dashboard</a>
                <span class="mx-2">→</span>
                <a href="/cash-transactions" class="hover:text-gray-200 transition-colors">Cash Transactions</a>
                <span class="mx-2">→</span>
                <span class="text-gray-200">Record Transaction</span>
            </div>

            <div>
                <h1 class="text-3xl font-bold">Record Cash Transaction</h1>
                <p class="text-gray-400 mt-1">Record inflows (receipts) or outflows (payments)</p>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-3xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading accounts...</p>
        </div>

        <!-- Error Message -->
        <div x-show="error" class="max-w-3xl mx-auto mb-6">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4">
                <p class="text-red-400" x-text="error"></p>
            </div>
        </div>

        <!-- Success Message -->
        <div x-show="success" class="max-w-3xl mx-auto mb-6">
            <div class="rounded-xl bg-green-500/10 border border-green-500/30 p-4">
                <p class="text-green-400" x-text="success"></p>
            </div>
        </div>

        <!-- Form -->
        <div x-show="!loading" class="max-w-3xl mx-auto">
            <form @submit.prevent="submitTransaction" class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-8">
                <!-- Account -->
                <div class="mb-6">
                    <label for="account_id" class="block text-sm font-medium text-gray-300 mb-2">Account *</label>
                    <select id="account_id" 
                            x-model="form.account_id" 
                            @change="updateCurrency" 
                            required
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                        <option value="">Select Account</option>
                        <template x-for="account in accounts" :key="account.id">
                            <option :value="account.id" x-text="`${account.name} (${account.type.replace('_', ' ')})`"></option>
                        </template>
                    </select>
                </div>

                <!-- Currency (Auto-filled) -->
                <div class="mb-6">
                    <label for="currency" class="block text-sm font-medium text-gray-300 mb-2">Currency *</label>
                    <select id="currency" 
                            x-model="form.currency" 
                            required
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                        <option value="">Select Currency</option>
                        <option value="UGX">UGX - Ugandan Shilling</option>
                        <option value="USD">USD - US Dollar</option>
                    </select>
                </div>

                <!-- Transaction Date -->
                <div class="mb-6">
                    <label for="transaction_date" class="block text-sm font-medium text-gray-300 mb-2">Transaction Date *</label>
                    <input type="date" 
                           id="transaction_date" 
                           x-model="form.transaction_date" 
                           required
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>

                <!-- Transaction Type (Inflow/Outflow) -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Transaction Type *</label>
                    <div class="flex gap-4">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" 
                                   name="type" 
                                   value="inflow" 
                                   x-model="form.type" 
                                   required 
                                   class="sr-only peer">
                            <div class="px-4 py-3 rounded-lg border-2 border-white/10 peer-checked:border-green-500 peer-checked:bg-green-500/10 transition-all text-center">
                                <span class="font-medium">💰 Inflow (Receipt)</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" 
                                   name="type" 
                                   value="outflow" 
                                   x-model="form.type" 
                                   required 
                                   class="sr-only peer">
                            <div class="px-4 py-3 rounded-lg border-2 border-white/10 peer-checked:border-red-500 peer-checked:bg-red-500/10 transition-all text-center">
                                <span class="font-medium">💸 Outflow (Payment)</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Amount -->
                <div class="mb-6">
                    <label for="amount" class="block text-sm font-medium text-gray-300 mb-2">Amount *</label>
                    <input type="number" 
                           id="amount" 
                           x-model="form.amount" 
                           required 
                           min="0" 
                           step="0.01"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                           placeholder="0.00">
                </div>

                <!-- Source Type -->
                <div class="mb-6">
                    <label for="source_type" class="block text-sm font-medium text-gray-300 mb-2">Source Type *</label>
                    <select id="source_type" 
                            x-model="form.source_type" 
                            @change="onSourceTypeChange" 
                            required
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                        <option value="">Select Source Type</option>
                        <option value="project">Project</option>
                        <option value="expense">Expense</option>
                        <option value="milestone">Payment Milestone</option>
                        <option value="opportunity">Opportunity</option>
                        <option value="manual">Manual Entry</option>
                    </select>
                </div>

                <!-- Source ID (Smart Dropdown) -->
                <div class="mb-6" x-show="form.source_type && form.source_type !== 'manual'">
                    <label for="source_id" class="block text-sm font-medium text-gray-300 mb-2">
                        <span x-text="sourceLabel"></span> *
                    </label>
                    <select id="source_id" 
                            x-model.number="form.source_id" 
                            :required="form.source_type !== 'manual'"
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                        <option value="">Select <span x-text="sourceLabel"></span></option>
                        <template x-for="option in sourceOptions" :key="option.id">
                            <option :value="option.id" x-text="option.label"></option>
                        </template>
                    </select>
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="description" 
                              x-model="form.description" 
                              rows="3"
                              class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors resize-none"
                              placeholder="Optional transaction notes"></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex gap-4">
                    <button type="button" 
                            @click="window.location.href='/cash-transactions'"
                            class="flex-1 px-6 py-3 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all font-medium">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            class="flex-1 px-6 py-3 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all font-medium">
                        <span x-show="!submitting">Record Transaction</span>
                        <span x-show="submitting">Recording...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function transactionCreate() {
            return {
                accounts: [],
                sourceOptions: [],
                form: {
                    account_id: '',
                    currency: '',
                    transaction_date: new Date().toISOString().split('T')[0],
                    type: '',
                    amount: '',
                    source_type: '',
                    source_id: '',
                    description: ''
                },
                loading: true,
                submitting: false,
                error: '',
                success: '',
                sourceLabel: '',

                async init() {
                    await this.fetchAccounts();
                },

                async fetchAccounts() {
                    try {
                        this.loading = true;
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

                updateCurrency() {
                    const account = this.accounts.find(a => a.id == this.form.account_id);
                    if (account) {
                        this.form.currency = account.currency;
                    }
                },

                async onSourceTypeChange() {
                    this.form.source_id = '';
                    this.sourceOptions = [];
                    
                    if (!this.form.source_type || this.form.source_type === 'manual') {
                        return;
                    }

                    try {
                        let endpoint = '';
                        switch(this.form.source_type) {
                            case 'project':
                                endpoint = '/api/projects';
                                this.sourceLabel = 'Project';
                                break;
                            case 'expense':
                                endpoint = '/api/expenses';
                                this.sourceLabel = 'Expense';
                                break;
                            case 'milestone':
                                endpoint = '/api/milestones';
                                this.sourceLabel = 'Milestone';
                                break;
                            case 'opportunity':
                                endpoint = '/api/opportunities';
                                this.sourceLabel = 'Opportunity';
                                break;
                        }

                        const response = await fetch(endpoint, {
                            headers: {
                                'Accept': 'application/json',
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to fetch options');
                        }

                        const data = await response.json();
                        
                        this.sourceOptions = data.map(item => {
                            let label = '';
                            switch(this.form.source_type) {
                                case 'project':
                                    label = `${item.name} (${item.client})`;
                                    break;
                                case 'expense':
                                    label = `${item.description} - ${item.amount} ${item.currency}`;
                                    break;
                                case 'milestone':
                                    label = `${item.title} - ${item.amount} ${item.currency}`;
                                    break;
                                case 'opportunity':
                                    label = `${item.client} - ${item.description}`;
                                    break;
                            }
                            return { id: item.id, label: label };
                        });
                    } catch (err) {
                        console.error('Error fetching source options:', err);
                    }
                },

                async submitTransaction() {
                    this.submitting = true;
                    this.error = '';
                    this.success = '';

                    try {
                        const response = await fetch('/api/cash-transactions', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const result = await response.json();

                        if (!result.success) {
                            this.error = result.message || 'Failed to record transaction';
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                            return;
                        }

                        this.success = result.message;
                        window.scrollTo({ top: 0, behavior: 'smooth' });

                        // Reset form
                        this.form = {
                            account_id: '',
                            currency: '',
                            transaction_date: new Date().toISOString().split('T')[0],
                            type: '',
                            amount: '',
                            source_type: '',
                            source_id: '',
                            description: ''
                        };

                        // Redirect after 2 seconds
                        setTimeout(() => {
                            window.location.href = '/cash-transactions';
                        }, 2000);
                    } catch (err) {
                        this.error = err.message || 'An error occurred while recording the transaction';
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
