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
        <div x-show="!loading && health" class="max-w-4xl mx-auto">
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
                
                <div class="text-3xl font-bold mb-4 capitalize"
                     :class="{
                         'text-green-300': health.status === 'healthy',
                         'text-yellow-300': health.status === 'at-risk',
                         'text-red-300': health.status === 'critical'
                     }"
                     x-text="health.status?.replace('-', ' ')"></div>
                
                <div class="text-gray-400 text-lg">Current Project Status</div>
            </div>
        </div>
    </div>
</body>
</html>
