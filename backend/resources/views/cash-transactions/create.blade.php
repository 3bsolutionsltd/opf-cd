<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Record Cash Transaction - OPF-CD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .card-body {
            padding: 40px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
        }

        .required {
            color: #ef4444;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 20px;
        }

        .radio-option {
            flex: 1;
            position: relative;
        }

        .radio-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .radio-option label {
            display: block;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            font-weight: 600;
        }

        .radio-option input[type="radio"]:checked + label {
            border-color: #10b981;
            background: #f0fdf4;
            color: #059669;
        }

        .radio-option label:hover {
            border-color: #10b981;
        }

        .help-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background: #efe;
            color: #060;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .breadcrumb {
            margin-bottom: 20px;
            text-align: center;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            font-size: 14px;
        }

        .breadcrumb a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        .breadcrumb span {
            color: white;
            opacity: 0.6;
            margin: 0 8px;
        }
    </style>
</head>
<body x-data="transactionCreate()" x-init="init()">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Dashboard</a>
            <span>→</span>
            <a href="/cash-transactions">Cash Transactions</a>
            <span>→</span>
            <span style="opacity: 1;">Record Transaction</span>
        </div>
        <div class="card">
            <div class="card-header">
                <h1>Record Cash Transaction</h1>
                <p>Record inflows (receipts) or outflows (payments)</p>
            </div>

            <div class="card-body">
                <div x-show="loading" class="loading">Loading accounts...</div>

                <template x-if="!loading">
                    <div>
                        <div x-show="error" x-text="error" class="error-message"></div>
                        <div x-show="success" x-text="success" class="success-message"></div>

                        <form @submit.prevent="submitTransaction">
                            <div class="form-group">
                                <label for="account_id">Account <span class="required">*</span></label>
                                <select id="account_id" x-model="form.account_id" @change="updateCurrency" required>
                                    <option value="">-- Select Account --</option>
                                    <template x-for="account in accounts" :key="account.id">
                                        <option :value="account.id" x-text="`${account.name} (${account.type.replace('_', ' ')} - ${account.currency})`"></option>
                                    </template>
                                </select>
                                <div class="help-text">Which account this transaction affects</div>
                            </div>

                            <div class="form-group">
                                <label>Transaction Type <span class="required">*</span></label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="type_inflow" name="type" value="inflow" x-model="form.type" required>
                                        <label for="type_inflow">💰 Inflow (Receipt)</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="type_outflow" name="type" value="outflow" x-model="form.type" required>
                                        <label for="type_outflow">💸 Outflow (Payment)</label>
                                    </div>
                                </div>
                                <div class="help-text">Inflow increases balance, outflow decreases balance</div>
                            </div>

                            <div class="form-group">
                                <label for="amount">Amount <span class="required">*</span></label>
                                <input 
                                    type="number" 
                                    id="amount" 
                                    x-model.number="form.amount" 
                                    step="0.01"
                                    min="0.01"
                                    required
                                >
                                <div class="help-text">Transaction amount (must be greater than 0)</div>
                            </div>

                            <div class="form-group">
                                <label for="currency">Currency <span class="required">*</span></label>
                                <select id="currency" x-model="form.currency" required>
                                    <option value="">-- Select Currency --</option>
                                    <option value="UGX">UGX (Ugandan Shilling)</option>
                                    <option value="USD">USD (US Dollar)</option>
                                </select>
                                <div class="help-text">Should match the account currency</div>
                            </div>

                            <div class="form-group">
                                <label for="source_type">Source Type <span class="required">*</span></label>
                                <select id="source_type" x-model="form.source_type" @change="onSourceTypeChange" required>
                                    <option value="">-- Select Source Type --</option>
                                    <option value="project">Project</option>
                                    <option value="expense">Expense</option>
                                    <option value="milestone">Payment Milestone</option>
                                    <option value="opportunity">Opportunity</option>
                                    <option value="manual">Manual Entry (Other)</option>
                                </select>
                                <div class="help-text">What type of record is this transaction related to?</div>
                            </div>

                            <div class="form-group" x-show="form.source_type && form.source_type !== 'manual'">
                                <label for="source_id">Source <span class="required">*</span></label>
                                <select id="source_id" x-model.number="form.source_id" :required="form.source_type !== 'manual'">
                                    <option value="">-- Select <span x-text="sourceTypeLabel"></span> --</option>
                                    <template x-for="source in sourceOptions" :key="source.id">
                                        <option :value="source.id" x-text="source.label"></option>
                                    </template>
                                </select>
                                <div class="help-text" x-show="loadingSourceOptions">Loading options...</div>
                                <div class="help-text" x-show="!loadingSourceOptions" x-text="'Select the ' + sourceTypeLabel.toLowerCase() + ' this transaction is related to'"></div>
                            </div>

                            <div class="form-group" x-show="form.source_type === 'manual'">
                                <label for="source_id_manual">Reference Number <span class="required">*</span></label>
                                <input 
                                    type="number" 
                                    id="source_id_manual" 
                                    x-model.number="form.source_id" 
                                    placeholder="Enter reference number"
                                    :required="form.source_type === 'manual'"
                                >
                                <div class="help-text">Enter any reference number for manual tracking</div>
                            </div>

                            <div class="form-group">
                                <label for="transaction_date">Transaction Date <span class="required">*</span></label>
                                <input 
                                    type="date" 
                                    id="transaction_date" 
                                    x-model="form.transaction_date" 
                                    required
                                >
                                <div class="help-text">Date when the transaction occurred</div>
                            </div>

                            <div class="button-group">
                                <a href="/cash-transactions" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary" :disabled="submitting">
                                    <span x-show="!submitting">Record Transaction</span>
                                    <span x-show="submitting">Recording...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function transactionCreate() {
            return {
                accounts: [],
                sourceOptions: [],
                loadingSourceOptions: false,
                form: {
                    account_id: '',
                    type: '',
                    amount: 0,
                    currency: '',
                    source_type: '',
                    source_id: '',
                    transaction_date: new Date().toISOString().split('T')[0]
                },
                loading: true,
                submitting: false,
                error: '',
                success: '',

                get sourceTypeLabel() {
                    const labels = {
                        'project': 'Project',
                        'expense': 'Expense',
                        'milestone': 'Payment Milestone',
                        'opportunity': 'Opportunity',
                        'manual': 'Manual Entry'
                    };
                    return labels[this.form.source_type] || 'Source';
                },

                async init() {
                    await this.fetchAccounts();
                },

                async fetchAccounts() {
                    try {
                        const response = await fetch('/api/accounts');
                        this.accounts = await response.json();
                    } catch (error) {
                        console.error('Error fetching accounts:', error);
                        this.error = 'Failed to load accounts. Please refresh the page.';
                    } finally {
                        this.loading = false;
                    }
                },

                updateCurrency() {
                    // Auto-fill currency when account is selected
                    const selectedAccount = this.accounts.find(a => a.id == this.form.account_id);
                    if (selectedAccount) {
                        this.form.currency = selectedAccount.currency;
                    }
                },

                async onSourceTypeChange() {
                    this.form.source_id = '';
                    this.sourceOptions = [];

                    if (!this.form.source_type || this.form.source_type === 'manual') {
                        return;
                    }

                    this.loadingSourceOptions = true;
                    try {
                        let endpoint = '';
                        switch(this.form.source_type) {
                            case 'project':
                                endpoint = '/api/projects';
                                break;
                            case 'expense':
                                endpoint = '/api/expenses';
                                break;
                            case 'milestone':
                                endpoint = '/api/milestones';
                                break;
                            case 'opportunity':
                                endpoint = '/api/opportunities';
                                break;
                        }

                        const response = await fetch(endpoint);
                        const data = await response.json();

                        // Format options based on source type
                        this.sourceOptions = data.map(item => {
                            let label = '';
                            switch(this.form.source_type) {
                                case 'project':
                                    label = `${item.name} (${item.status})`;
                                    break;
                                case 'expense':
                                    label = `${item.category} - ${item.currency} ${item.amount.toLocaleString()}`;
                                    break;
                                case 'milestone':
                                    label = `${item.title} - ${item.currency} ${item.amount.toLocaleString()}`;
                                    break;
                                case 'opportunity':
                                    label = `${item.client} - ${item.description}`;
                                    break;
                            }
                            return { id: item.id, label: label };
                        });
                    } catch (error) {
                        console.error('Error fetching source options:', error);
                        this.error = 'Failed to load source options. Please try again.';
                    } finally {
                        this.loadingSourceOptions = false;
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
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.success = data.message;
                            setTimeout(() => {
                                window.location.href = '/cash-transactions';
                            }, 1500);
                        } else {
                            this.error = data.message || 'Failed to record transaction. Please try again.';
                        }
                    } catch (error) {
                        console.error('Error recording transaction:', error);
                        this.error = 'An error occurred. Please try again.';
                    } finally {
                        this.submitting = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
