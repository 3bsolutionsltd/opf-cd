{{-- 
STRICT VIEW RULE:
This view renders data only.
No calculations.
No decisions.
No service calls.
--}}

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upcoming Expenses - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen p-8" x-data="{
        expenses: null,
        loading: true
    }" x-init="
        fetch('/api/finance/expenses/upcoming', {
            headers: { 'Accept': 'application/json' }
        })
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
        <!-- Header -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="mb-4 text-sm text-gray-400">
                <a href="/" class="hover:text-gray-200 transition-colors">Home</a>
                <span class="mx-2">→</span>
                <a href="/dashboard" class="hover:text-gray-200 transition-colors">Dashboard</a>
                <span class="mx-2">→</span>
                <span class="text-gray-200">Upcoming Expenses</span>
            </div>

            <h1 class="text-4xl font-bold">💳 Upcoming Expenses</h1>
            <p class="text-gray-400 mt-2">Next 30 days expense forecast</p>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-4xl mx-auto text-center py-20">
            <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-2 border-red-500"></div>
            <p class="mt-4 text-gray-400">Loading expenses...</p>
        </div>

        <!-- Content -->
        <div x-show="!loading && expenses" class="max-w-4xl mx-auto space-y-6">
            <!-- Summary -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-12 text-center">
                <div class="text-8xl font-black text-red-400 mb-4">
                    <span x-text="expenses.expenses?.length || 0"></span>
                </div>
                <div class="text-2xl text-gray-300 mb-8">Pending Expenses</div>
                
                <div class="text-5xl font-bold text-gray-200 mb-2">
                    <span x-text="expenses.total_amount?.toLocaleString()"></span> <span x-text="expenses.currency"></span>
                </div>
                <div class="text-gray-400">Total Amount Due</div>
            </div>

            <!-- Expense List -->
            <div x-show="expenses.expenses && expenses.expenses.length > 0" class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Expense Breakdown</h3>
                <div class="space-y-3">
                    <template x-for="expense in expenses.expenses" :key="expense.expense_id">
                        <div class="flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/5 hover:bg-white/10 transition-all">
                            <div class="flex-1">
                                <div class="font-medium" x-text="expense.name"></div>
                                <div class="text-sm text-gray-400">
                                    <span x-text="expense.due_date"></span>
                                    <span class="ml-2 px-2 py-0.5 rounded text-xs"
                                          :class="expense.source === 'original' ? 'bg-blue-500/20 text-blue-300' : 'bg-purple-500/20 text-purple-300'"
                                          x-text="expense.source"></span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1 capitalize">
                                    <span x-text="expense.category"></span> • 
                                    <span x-text="expense.type"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-red-400">
                                    <span x-text="expense.amount?.toLocaleString()"></span> <span x-text="expense.currency"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
