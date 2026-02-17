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
    <title>Cash Flow - OPF-CD</title>
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
        cashFlow: null,
        loading: true
    }" x-init="
        fetch('/api/finance/cash-flow', {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            cashFlow = data;
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
                <span class="text-gray-200">Cash Flow</span>
            </div>

            <h1 class="text-4xl font-bold">💸 Cash Flow</h1>
            <p class="text-gray-400 mt-2">Inflows and outflows tracking</p>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-4xl mx-auto text-center py-20">
            <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500"></div>
            <p class="mt-4 text-gray-400">Loading cash flow data...</p>
        </div>

        <!-- Content -->
        <div x-show="!loading && cashFlow" class="max-w-4xl mx-auto space-y-6">
            <!-- Currency Breakdown -->
            <template x-for="currency in cashFlow.currencies" :key="currency">
                <div class="space-y-6">
                    <div class="text-center">
                        <div class="inline-block px-4 py-2 rounded-lg bg-blue-500/20 border border-blue-500/30">
                            <span class="text-xl font-bold text-blue-300" x-text="currency"></span>
                        </div>
                    </div>
                    
                    <!-- Net Flow -->
                    <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-12 text-center">
                        <div class="text-sm text-gray-400 mb-2">Net Cash Flow</div>
                        <div class="text-7xl font-black mb-4"
                             :class="cashFlow.by_currency[currency].net_cash_flow >= 0 ? 'text-green-400' : 'text-red-400'">
                            <span x-text="cashFlow.by_currency[currency].net_cash_flow?.toLocaleString()"></span>
                        </div>
                        <div class="text-xl text-gray-300">
                            <span x-show="cashFlow.by_currency[currency].net_cash_flow >= 0" class="text-green-300">Positive Cash Flow</span>
                            <span x-show="cashFlow.by_currency[currency].net_cash_flow < 0" class="text-red-300">Negative Cash Flow</span>
                        </div>
                    </div>

                    <!-- Breakdown -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-blue-500/20 p-8 text-center">
                            <div class="text-sm text-gray-400 mb-2">Cash at Hand</div>
                            <div class="text-4xl font-bold text-blue-400 mb-2">
                                <span x-text="cashFlow.by_currency[currency].cash_at_hand?.toLocaleString()"></span>
                            </div>
                            <div class="text-gray-400">Available now</div>
                        </div>
                        
                        <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-green-500/20 p-8 text-center">
                            <div class="text-sm text-gray-400 mb-2">Total Inflows</div>
                            <div class="text-4xl font-bold text-green-400 mb-2">
                                <span x-text="cashFlow.by_currency[currency].total_inflows?.toLocaleString()"></span>
                            </div>
                            <div class="text-gray-400">Money received</div>
                        </div>

                        <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-red-500/20 p-8 text-center">
                            <div class="text-sm text-gray-400 mb-2">Total Outflows</div>
                            <div class="text-4xl font-bold text-red-400 mb-2">
                                <span x-text="cashFlow.by_currency[currency].total_outflows?.toLocaleString()"></span>
                            </div>
                            <div class="text-gray-400">Money spent</div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</body>
</html>
