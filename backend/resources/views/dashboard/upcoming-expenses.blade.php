{{-- 
STRICT VIEW RULE:
This view renders data only.
No calculations.
No decisions.
No service calls.
--}}

<div x-data="{
    expenses: [],
    loading: true
}" x-init="
    fetch('/api/finance/expenses/upcoming')
        .then(response => response.json())
        .then(data => {
            expenses = data;
            loading = false;
        });
">
    <div x-show="loading">Loading...</div>
    
    <div x-show="!loading">
        <template x-for="expense in expenses" :key="expense.expense_id">
            <div>
                <div>
                    <span>Expense ID:</span>
                    <span x-text="expense.expense_id"></span>
                </div>
                
                <div>
                    <span>Name:</span>
                    <span x-text="expense.name"></span>
                </div>
                
                <div>
                    <span>Category:</span>
                    <span x-text="expense.category"></span>
                </div>
                
                <div>
                    <span>Amount:</span>
                    <span x-text="expense.amount"></span>
                </div>
                
                <div>
                    <span>Currency:</span>
                    <span x-text="expense.currency"></span>
                </div>
                
                <div>
                    <span>Due Date:</span>
                    <span x-text="expense.due_date"></span>
                </div>
                
                <div>
                    <span>Type:</span>
                    <span x-text="expense.type"></span>
                </div>
                
                <div>
                    <span>Source:</span>
                    <span x-text="expense.source"></span>
                </div>
            </div>
        </template>
    </div>
</div>
