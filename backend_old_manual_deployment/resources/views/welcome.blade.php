<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OPF-CD - Operations, Projects & Finance Command Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen relative overflow-hidden">
        <!-- Animated Background Grid -->
        <div class="absolute inset-0 bg-[linear-gradient(rgba(99,102,241,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(99,102,241,0.03)_1px,transparent_1px)] bg-[size:50px_50px] [mask-image:radial-gradient(ellipse_80%_50%_at_50%_0%,#000_70%,transparent_110%)]"></div>
        
        <!-- Gradient Overlays -->
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 via-transparent to-purple-500/10"></div>
        
        <!-- Header -->
        <header class="relative z-10 border-b border-white/5 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center font-bold text-lg shadow-lg shadow-indigo-500/50">
                        O
                    </div>
                    <div>
                        <h1 class="text-lg font-bold">OPF-CD</h1>
                        <p class="text-xs text-gray-400">Command Dashboard</p>
                    </div>
                </div>
                
                <nav class="flex items-center gap-4">
                    @if(session()->has('user_id'))
                        <a href="/dashboard" class="px-4 py-2 rounded-lg bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20 transition-all text-indigo-400 font-medium">
                            Dashboard
                        </a>
                        <form method="POST" action="/logout" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-lg border border-white/10 hover:bg-white/5 transition-all text-gray-300">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="/login" class="px-4 py-2 rounded-lg border border-white/10 hover:bg-white/5 transition-all text-gray-300">
                            Login
                        </a>
                    @endif
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="relative z-10 max-w-7xl mx-auto px-6 py-20">
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-sm font-medium mb-8">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    System Online
                </div>
                
                <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                    <span class="bg-gradient-to-r from-white via-indigo-200 to-purple-200 bg-clip-text text-transparent">
                        Operations, Projects<br>& Finance Control
                    </span>
                </h1>
                
                <p class="text-xl text-gray-400 max-w-2xl mx-auto mb-12">
                    Centralized command center for managing operations, tracking projects, monitoring finances, and driving business growth.
                </p>

                <div class="flex items-center justify-center gap-4 flex-wrap">
                    @if(session()->has('user_id'))
                        <a href="/dashboard" class="px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all font-semibold text-lg shadow-lg shadow-indigo-500/50">
                            Open Dashboard
                        </a>
                        <a href="/projects" class="px-8 py-4 rounded-xl border border-white/10 hover:bg-white/5 transition-all font-semibold text-lg">
                            Manage Projects
                        </a>
                    @else
                        <a href="/login" class="px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all font-semibold text-lg shadow-lg shadow-indigo-500/50">
                            Get Started
                        </a>
                    @endif
                </div>
            </div>

            @if(session()->has('user_id'))
            <!-- Quick Stats -->
            <div class="mb-16" x-data="{
                stats: {
                    projects: { loading: true, count: 0 },
                    opportunities: { loading: true, count: 0 },
                    accounts: { loading: true, count: 0 }
                }
            }" x-init="
                fetch('/api/projects', { headers: { 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(data => { stats.projects = { loading: false, count: data.length } });
                
                fetch('/api/opportunities', { headers: { 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(data => { stats.opportunities = { loading: false, count: data.length } });
                
                fetch('/api/accounts', { headers: { 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(data => { stats.accounts = { loading: false, count: data.length } });
            ">
                <h2 class="text-2xl font-bold text-center mb-8">Quick Overview</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:bg-white/10 transition-all">
                        <div class="text-4xl mb-4">📊</div>
                        <div class="text-4xl font-bold mb-2">
                            <span x-show="stats.projects.loading" class="text-gray-500">--</span>
                            <span x-show="!stats.projects.loading" x-text="stats.projects.count"></span>
                        </div>
                        <div class="text-gray-400 text-sm font-medium uppercase tracking-wider">Active Projects</div>
                    </div>

                    <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:bg-white/10 transition-all">
                        <div class="text-4xl mb-4">💼</div>
                        <div class="text-4xl font-bold mb-2">
                            <span x-show="stats.opportunities.loading" class="text-gray-500">--</span>
                            <span x-show="!stats.opportunities.loading" x-text="stats.opportunities.count"></span>
                        </div>
                        <div class="text-gray-400 text-sm font-medium uppercase tracking-wider">Opportunities</div>
                    </div>

                    <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:bg-white/10 transition-all">
                        <div class="text-4xl mb-4">💰</div>
                        <div class="text-4xl font-bold mb-2">
                            <span x-show="stats.accounts.loading" class="text-gray-500">--</span>
                            <span x-show="!stats.accounts.loading" x-text="stats.accounts.count"></span>
                        </div>
                        <div class="text-gray-400 text-sm font-medium uppercase tracking-wider">Financial Accounts</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <h2 class="text-2xl font-bold text-center mb-8">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="/projects" class="group rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-indigo-500/30 hover:bg-white/10 transition-all">
                        <div class="text-4xl mb-4">📁</div>
                        <h3 class="text-xl font-bold mb-2 group-hover:text-indigo-400 transition-colors">Projects</h3>
                        <p class="text-gray-400 text-sm">Manage projects, tasks, and milestones</p>
                    </a>

                    <a href="/opportunities" class="group rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-green-500/30 hover:bg-white/10 transition-all">
                        <div class="text-4xl mb-4">🎯</div>
                        <h3 class="text-xl font-bold mb-2 group-hover:text-green-400 transition-colors">Opportunities</h3>
                        <p class="text-gray-400 text-sm">Track and manage sales pipeline</p>
                    </a>

                    <a href="/accounts" class="group rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-purple-500/30 hover:bg-white/10 transition-all">
                        <div class="text-4xl mb-4">🏦</div>
                        <h3 class="text-xl font-bold mb-2 group-hover:text-purple-400 transition-colors">Accounts</h3>
                        <p class="text-gray-400 text-sm">Manage financial accounts</p>
                    </a>

                    <a href="/cash-transactions" class="group rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-blue-500/30 hover:bg-white/10 transition-all">
                        <div class="text-4xl mb-4">💸</div>
                        <h3 class="text-xl font-bold mb-2 group-hover:text-blue-400 transition-colors">Cash Transactions</h3>
                        <p class="text-gray-400 text-sm">Record and track cash flow</p>
                    </a>

                    <a href="/expenses" class="group rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 hover:border-red-500/30 hover:bg-white/10 transition-all">
                        <div class="text-4xl mb-4">💳</div>
                        <h3 class="text-xl font-bold mb-2 group-hover:text-red-400 transition-colors">Expenses</h3>
                        <p class="text-gray-400 text-sm">Manage project expenses</p>
                    </a>

                    <a href="/dashboard" class="group rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 p-6 hover:from-indigo-500/30 hover:to-purple-500/30 transition-all">
                        <div class="text-4xl mb-4">📈</div>
                        <h3 class="text-xl font-bold mb-2 group-hover:text-indigo-300 transition-colors">Analytics Dashboard</h3>
                        <p class="text-gray-400 text-sm">View insights and metrics</p>
                    </a>
                </div>
            </div>
            @endif
        </main>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-white/5 mt-20">
            <div class="max-w-7xl mx-auto px-6 py-8 text-center text-gray-500 text-sm">
                <p>OPF-CD Command Dashboard &copy; {{ date('Y') }}</p>
            </div>
        </footer>
    </div>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
