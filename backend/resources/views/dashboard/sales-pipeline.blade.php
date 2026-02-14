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
    <title>Sales Pipeline - OPF-CD</title>
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
        pipeline: null,
        loading: true
    }" x-init="
        fetch('/api/sales/pipeline', {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            pipeline = data;
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
                <span class="text-gray-200">Sales Pipeline</span>
            </div>

            <h1 class="text-4xl font-bold">🎯 Sales Pipeline</h1>
            <p class="text-gray-400 mt-2">Opportunity funnel and revenue forecast</p>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-4xl mx-auto text-center py-20">
            <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500"></div>
            <p class="mt-4 text-gray-400">Loading pipeline...</p>
        </div>

        <!-- Content -->
        <div x-show="!loading && pipeline" class="max-w-4xl mx-auto space-y-6">
            <!-- Summary -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-12 text-center">
                <div class="text-8xl font-black text-purple-400 mb-4">
                    <span x-text="pipeline.opportunity_count || 0"></span>
                </div>
                <div class="text-2xl text-gray-300 mb-8">Active Opportunities</div>
                
                <div class="text-5xl font-bold text-gray-200 mb-2">
                    <span x-text="pipeline.total_value?.toLocaleString()"></span>
                </div>
                <div class="text-gray-400">Total Pipeline Value</div>
                
                <div class="mt-6 text-3xl font-bold text-purple-300">
                    <span x-text="pipeline.weighted_value?.toLocaleString()"></span>
                </div>
                <div class="text-sm text-gray-400">Weighted Pipeline Value</div>
            </div>

            <!-- Stage Breakdown -->
            <div x-show="pipeline.opportunities && pipeline.opportunities.length > 0" class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Pipeline Breakdown</h3>
                <div class="space-y-3">
                    <template x-for="stage in pipeline.opportunities" :key="stage.stage">
                        <div class="flex items-center justify-between p-4 rounded-lg bg-white/5 border border-white/5 hover:bg-white/10 transition-all">
                            <div class="flex-1">
                                <div class="font-medium capitalize" x-text="stage.stage"></div>
                                <div class="text-sm text-gray-400">
                                    <span x-text="stage.count"></span> opportunities
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-purple-400">
                                    <span x-text="stage.total_value?.toLocaleString()"></span>
                                </div>
                                <div class="text-xs text-gray-400">
                                    Weighted: <span x-text="stage.weighted_value?.toLocaleString()"></span>
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
