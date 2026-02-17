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
    <title>Project Health - OPF-CD</title>
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
        health: null,
        projectId: {{ $projectId }},
        loading: true
    }" x-init="
        fetch(`/api/projects/${projectId}/health`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            health = data;
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
                <span class="text-gray-200">Project Health</span>
            </div>

            <h1 class="text-4xl font-bold">🏥 Project Health</h1>
            <p class="text-gray-400 mt-2">Overall project status and risk assessment</p>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-4xl mx-auto text-center py-20">
            <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-2 border-green-500"></div>
            <p class="mt-4 text-gray-400">Loading health data...</p>
        </div>

        <!-- Content -->
        <div x-show="!loading && health" class="max-w-4xl mx-auto space-y-6">
            <!-- Main Health Card -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-12 text-center">
                <div class="text-9xl mb-6"
                     :class="{
                         'text-green-400': health.status === 'healthy',
                         'text-yellow-400': health.status === 'at-risk',
                         'text-red-400': health.status === 'critical'
                     }">
                    <span x-show="health.status === 'healthy'">✓</span>
                    <span x-show="health.status === 'at-risk'">⚠</span>
                    <span x-show="health.status === 'critical'">✗</span>
                </div>
                
                <div class="text-4xl font-bold mb-2"
                     :class="{
                         'text-green-300': health.status === 'healthy',
                         'text-yellow-300': health.status === 'at-risk',
                         'text-red-300': health.status === 'critical'
                     }"
                     x-text="health.status_label || health.status?.replace('-', ' ')"></div>
                
                <div class="text-gray-400 text-xl mb-4" x-text="health.status_description"></div>
                
                <div class="text-6xl font-bold"
                     :class="{
                         'text-green-400': health.score >= 80,
                         'text-yellow-400': health.score >= 50 && health.score < 80,
                         'text-red-400': health.score < 50
                     }">
                    <span x-text="health.score"></span><span class="text-gray-500 text-4xl">/100</span>
                </div>
            </div>
            
            <!-- Key Metrics Grid -->
            <div x-show="health.signals" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-lg bg-white/5 border border-white/10 p-4 text-center">
                    <div class="text-sm text-gray-400 mb-1">Progress</div>
                    <div class="text-2xl font-bold text-indigo-400" x-text="health.signals?.project_progress + '%'"></div>
                </div>
                <div class="rounded-lg bg-white/5 border border-white/10 p-4 text-center">
                    <div class="text-sm text-gray-400 mb-1">Payment Gap</div>
                    <div class="text-2xl font-bold"
                         :class="{
                             'text-green-400': health.signals?.payment_gap_percentage <= 0,
                             'text-yellow-400': health.signals?.payment_gap_percentage > 0 && health.signals?.payment_gap_percentage <= 20,
                             'text-red-400': health.signals?.payment_gap_percentage > 20
                         }"
                         x-text="Math.abs(health.signals?.payment_gap_percentage || 0) + '%'"></div>
                </div>
                <div class="rounded-lg bg-white/5 border border-white/10 p-4 text-center">
                    <div class="text-sm text-gray-400 mb-1">Earned Value</div>
                    <div class="text-2xl font-bold text-blue-400" x-text="(health.signals?.earned_value || 0).toLocaleString()"></div>
                </div>
                <div class="rounded-lg bg-white/5 border border-white/10 p-4 text-center">
                    <div class="text-sm text-gray-400 mb-1">Received</div>
                    <div class="text-2xl font-bold text-purple-400" x-text="(health.signals?.received_value || 0).toLocaleString()"></div>
                </div>
            </div>
            
            <!-- Health Analysis -->
            <div x-show="health.details && health.details.length > 0" 
                 class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-8">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <span>📊</span> Health Analysis
                </h3>
                <ul class="space-y-3">
                    <template x-for="(detail, index) in health.details" :key="index">
                        <li class="flex items-start text-gray-300">
                            <span class="text-indigo-400 mr-3 mt-1">•</span>
                            <span class="text-lg" x-text="detail"></span>
                        </li>
                    </template>
                </ul>
            </div>
            
            <!-- Recommendations -->
            <div x-show="health.recommendations && health.recommendations.length > 0" 
                 class="rounded-xl bg-blue-500/10 backdrop-blur-xl border border-blue-500/30 p-8">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2 text-blue-300">
                    <span>💡</span> Recommended Actions
                </h3>
                <ul class="space-y-3">
                    <template x-for="(rec, index) in health.recommendations" :key="index">
                        <li class="flex items-start text-blue-200">
                            <span class="text-blue-400 mr-3 mt-1">→</span>
                            <span class="text-lg" x-text="rec"></span>
                        </li>
                    </template>
                </ul>
            </div>
            
            <!-- Technical Reasons (Collapsible) -->
            <div x-show="health.reasons && health.reasons.length > 0" 
                 class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6"
                 x-data="{ expanded: false }">
                <button @click="expanded = !expanded" 
                        class="w-full text-left flex items-center justify-between text-gray-400 hover:text-gray-200 transition-colors">
                    <span class="text-sm font-semibold">Technical Details</span>
                    <span x-text="expanded ? '▼' : '▶'" class="text-xs"></span>
                </button>
                <div x-show="expanded" class="mt-4 space-y-2" x-transition>
                    <template x-for="(reason, index) in health.reasons" :key="index">
                        <div class="inline-block bg-red-500/20 text-red-300 px-3 py-1 rounded-full text-sm mr-2 mb-2" x-text="reason"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
