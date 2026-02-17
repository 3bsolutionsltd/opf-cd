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
    <title>Payment Gap - OPF-CD</title>
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
        paymentGap: null,
        projectId: {{ $projectId }},
        loading: true
    }" x-init="
        fetch(`/api/projects/${projectId}/payment-gap`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            paymentGap = data;
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
                <span class="text-gray-200">Payment Gap</span>
            </div>

            <h1 class="text-4xl font-bold">💰 Payment Gap</h1>
            <p class="text-gray-400 mt-2">Work delivered vs payments received</p>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-4xl mx-auto text-center py-20">
            <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-2 border-yellow-500"></div>
            <p class="mt-4 text-gray-400">Loading payment data...</p>
        </div>

        <!-- Content -->
        <div x-show="!loading && paymentGap" class="max-w-4xl mx-auto">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-12 text-center">
                <div class="text-7xl font-black mb-4"
                     :class="{
                         'text-green-400': paymentGap.gap > 0,
                         'text-red-400': paymentGap.gap < 0,
                         'text-blue-400': paymentGap.gap === 0
                     }">
                    <span x-text="paymentGap.currency"></span> <span x-text="Math.abs(paymentGap.gap).toLocaleString()"></span>
                </div>
                
                <div class="text-2xl mb-12"
                     :class="{
                         'text-green-300': paymentGap.gap > 0,
                         'text-red-300': paymentGap.gap < 0,
                         'text-blue-300': paymentGap.gap === 0
                     }">
                    <span x-show="paymentGap.gap > 0">✓ Payment ahead of work</span>
                    <span x-show="paymentGap.gap < 0">⚠ Work ahead of payment</span>
                    <span x-show="paymentGap.gap === 0">= Perfectly balanced</span>
                </div>

                <div class="grid grid-cols-2 gap-8 max-w-2xl mx-auto">
                    <div class="rounded-lg bg-white/5 p-6">
                        <div class="text-sm text-gray-400 mb-2">Work Delivered</div>
                        <div class="text-3xl font-bold" x-text="paymentGap.work_delivered?.toLocaleString()"></div>
                    </div>
                    <div class="rounded-lg bg-white/5 p-6">
                        <div class="text-sm text-gray-400 mb-2">Payments Received</div>
                        <div class="text-3xl font-bold" x-text="paymentGap.payments_received?.toLocaleString()"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
