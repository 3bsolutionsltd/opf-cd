<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cash Transactions - OPF-CD</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 32px;
            color: #1f2937;
            font-weight: 700;
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
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .card {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .card-body {
            padding: 30px;
        }

        .filter-section {
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .filter-section label {
            font-weight: 600;
            color: #374151;
        }

        .filter-section select {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
        }

        tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-inflow {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-outflow {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-bank {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-mobile {
            background: #e9d5ff;
            color: #6b21a8;
        }

        .badge-cash {
            background: #d1fae5;
            color: #065f46;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: #374151;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
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
<body x-data="transactionsIndex()" x-init="init()">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Dashboard</a>
            <span>→</span>
            <span style="opacity: 1;">Cash Transactions</span>
        </div>
        <div class="page-header">
            <h1>💰 Cash Transactions</h1>
            <a href="/cash-transactions/create" class="btn btn-primary">+ Record Transaction</a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="filter-section">
                    <label for="account_filter">Filter by Account:</label>
                    <select id="account_filter" x-model="filterAccountId" @change="fetchTransactions">
                        <option value="">All Accounts</option>
                        <template x-for="account in accounts" :key="account.id">
                            <option :value="account.id" x-text="`${account.name} (${account.type.replace('_', ' ')})`"></option>
                        </template>
                    </select>
                </div>

                <div x-show="loading" class="loading">Loading transactions...</div>

                <template x-if="!loading && transactions.length > 0">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Account</th>
                                <th>Type</th>
                                <th>Transaction Type</th>
                                <th>Amount</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="transaction in transactions" :key="transaction.id">
                                <tr>
                                    <td x-text="transaction.transaction_date"></td>
                                    <td>
                                        <div x-text="transaction.account_name"></div>
                                        <span 
                                            class="badge"
                                            :class="{
                                                'badge-bank': transaction.account_type === 'bank',
                                                'badge-mobile': transaction.account_type === 'mobile_money',
                                                'badge-cash': transaction.account_type === 'cash'
                                            }"
                                            x-text="transaction.account_type.replace('_', ' ')"
                                        ></span>
                                    </td>
                                    <td>
                                        <span 
                                            class="badge"
                                            :class="{
                                                'badge-inflow': transaction.type === 'inflow',
                                                'badge-outflow': transaction.type === 'outflow'
                                            }"
                                            x-text="transaction.type === 'inflow' ? '💰 Inflow' : '💸 Outflow'"
                                        ></span>
                                    </td>
                                    <td x-text="transaction.type.toUpperCase()"></td>
                                    <td x-text="formatCurrency(transaction.amount, transaction.currency)"></td>
                                    <td>
                                        <div x-text="formatSourceType(transaction.source_type)"></div>
                                        <div style="font-size: 12px; color: #6b7280;" x-text="'ID: ' + transaction.source_id"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>

                <template x-if="!loading && transactions.length === 0">
                    <div class="empty-state">
                        <h3>No Transactions Found</h3>
                        <p>Record your first cash transaction to start tracking.</p>
                        <a href="/cash-transactions/create" class="btn btn-primary" style="margin-top: 20px;">Record Transaction</a>
                    </div>
                </template>
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

                async init() {
                    await Promise.all([
                        this.fetchAccounts(),
                        this.fetchTransactions()
                    ]);
                },

                async fetchAccounts() {
                    try {
                        const response = await fetch('/api/accounts');
                        this.accounts = await response.json();
                    } catch (error) {
                        console.error('Error fetching accounts:', error);
                    }
                },

                async fetchTransactions() {
                    this.loading = true;
                    try {
                        const url = this.filterAccountId 
                            ? `/api/cash-transactions?account_id=${this.filterAccountId}`
                            : '/api/cash-transactions';
                        
                        const response = await fetch(url);
                        this.transactions = await response.json();
                    } catch (error) {
                        console.error('Error fetching transactions:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                formatCurrency(value, currency) {
                    return `${currency} ${parseFloat(value).toLocaleString()}`;
                },

                formatSourceType(sourceType) {
                    const labels = {
                        'project': 'Project',
                        'expense': 'Expense',
                        'milestone': 'Payment Milestone',
                        'opportunity': 'Opportunity',
                        'manual': 'Manual Entry',
                        // Legacy values from before dropdown fix
                        'project_payment': 'Project Payment',
                        'client_invoice': 'Client Invoice'
                    };
                    return labels[sourceType] || sourceType;
                }
            }
        }
    </script>
</body>
</html>
