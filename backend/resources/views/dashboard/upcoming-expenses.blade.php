{{-- 
STRICT VIEW RULE:
This view renders data only.
No calculations.
No decisions.
No service calls.
--}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upcoming Expenses - OPF-CD</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f7fafc;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { font-size: 2rem; color: #2d3748; margin-bottom: 10px; }
        .breadcrumb { color: #718096; font-size: 0.9rem; }
        .breadcrumb a { color: #667eea; text-decoration: none; }
        .loading {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            color: #718096;
        }
        .expenses-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .expense-item {
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
        }
        .expense-item:last-child { border-bottom: none; }
        .expense-info h3 {
            font-size: 1.1rem;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .expense-meta {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: #718096;
        }
        .expense-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .expense-amount {
            text-align: right;
        }
        .amount-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }
        .currency {
            font-size: 0.85rem;
            color: #718096;
        }
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #edf2f7;
            color: #4a5568;
        }
        .type-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .type-recurring { background: #c6f6d5; color: #22543d; }
        .type-oneoff { background: #fed7d7; color: #742a2a; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="breadcrumb"><a href="/">← Back to Dashboard</a></div>
        <h1>Upcoming Expenses</h1>
    </div>

    <div x-data="{
        expenses: [],
        loading: true
    }" x-init="
        fetch('/api/finance/expenses/upcoming')
            .then(response => response.json())
            .then(data => {
                expenses = data;
                loading = false;
            })
            .catch(error => {
                console.error('Error:', error);
                loading = false;
            });
    ">
        <div x-show="loading" class="loading">Loading expenses...</div>
        
        <div x-show="!loading" class="expenses-list">
            <template x-for="expense in expenses" :key="expense.expense_id">
                <div class="expense-item">
                    <div class="expense-info">
                        <h3 x-text="expense.name"></h3>
                        <div class="expense-meta">
                            <span class="category-badge" x-text="expense.category"></span>
                            <span class="type-badge" :class="expense.type === 'recurring' ? 'type-recurring' : 'type-oneoff'" x-text="expense.type"></span>
                            <span>📅 <span x-text="expense.due_date"></span></span>
                            <span>📍 <span x-text="expense.source"></span></span>
                        </div>
                    </div>
                    <div class="expense-amount">
                        <div class="amount-value" x-text="expense.amount"></div>
                        <div class="currency" x-text="expense.currency"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
</body>
</html>
