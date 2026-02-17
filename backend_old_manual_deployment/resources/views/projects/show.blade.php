<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Project Details - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen p-8" x-data="projectShow({{ $projectId }})">
        <!-- Header -->
        <div class="max-w-5xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold" x-text="project.name || 'Project Details'"></h1>
                    <p class="text-gray-400 mt-1">View project information</p>
                </div>
                <div class="flex gap-4">
                    <a href="/projects" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Back to Projects
                    </a>
                    <a :href="`/projects/${projectId}/tasks`" class="px-4 py-2 rounded-lg bg-purple-500/20 hover:bg-purple-500/30 border border-purple-500/30 text-purple-400 transition-all">
                        View Tasks
                    </a>
                    <a :href="`/projects/${projectId}/milestones`" class="px-4 py-2 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/30 text-emerald-400 transition-all">
                        View Milestones
                    </a>
                    <a :href="`/projects/${projectId}/edit`" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all">
                        Edit Project
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-5xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading project...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" class="max-w-5xl mx-auto">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-6">
                <p class="text-red-400" x-text="error"></p>
            </div>
        </div>

        <!-- Project Details -->
        <div x-show="!loading && !error" class="max-w-5xl mx-auto space-y-6">
            
            <!-- Basic Information -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
                <h2 class="text-xl font-semibold mb-4">Basic Information</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Project Name</p>
                        <p class="text-lg font-medium" x-text="project.name"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Client</p>
                        <p class="text-lg font-medium" x-text="project.client"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Status</p>
                        <span class="inline-block px-3 py-1 text-sm rounded-full" 
                              :class="{
                                  'bg-blue-500/20 text-blue-400': project.status === 'planned',
                                  'bg-green-500/20 text-green-400': project.status === 'active',
                                  'bg-yellow-500/20 text-yellow-400': project.status === 'on_hold',
                                  'bg-gray-500/20 text-gray-400': project.status === 'completed',
                                  'bg-red-500/20 text-red-400': project.status === 'cancelled'
                              }"
                              x-text="project.status"></span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Project Lead ID</p>
                        <p class="text-lg font-medium" x-text="project.project_lead_id || 'Not assigned'"></p>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
                <h2 class="text-xl font-semibold mb-4">Financial Information</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Contract Value</p>
                        <p class="text-2xl font-bold" x-text="project.contract_currency + ' ' + Number(project.contract_value).toLocaleString()"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Currency</p>
                        <p class="text-lg font-medium" x-text="project.contract_currency"></p>
                    </div>
                </div>
            </div>

            <!-- Timeline Information -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
                <h2 class="text-xl font-semibold mb-4">Timeline</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Start Date</p>
                        <p class="text-lg font-medium" x-text="project.start_date"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 mb-1">End Date</p>
                        <p class="text-lg font-medium" x-text="project.end_date"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Created At</p>
                        <p class="text-sm text-gray-300" x-text="project.created_at"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Last Updated</p>
                        <p class="text-sm text-gray-300" x-text="project.updated_at"></p>
                    </div>
                </div>
            </div>

            <!-- Related Sections (Placeholders for Phase 2.3) -->
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-6">
                <h2 class="text-xl font-semibold mb-4">Related Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-lg bg-white/5 border border-white/10">
                        <p class="text-sm text-gray-400 mb-2">Tasks</p>
                        <p class="text-sm text-gray-500">Available in Phase 2.3</p>
                    </div>
                    <div class="p-4 rounded-lg bg-white/5 border border-white/10">
                        <p class="text-sm text-gray-400 mb-2">Payment Milestones</p>
                        <p class="text-sm text-gray-500">Available in Phase 2.3</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function projectShow(projectId) {
            return {
                projectId: projectId,
                project: {},
                loading: true,
                error: '',

                async init() {
                    await this.fetchProject();
                },

                async fetchProject() {
                    this.loading = true;
                    this.error = '';

                    try {
                        const response = await fetch(`/api/projects/${this.projectId}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('Failed to fetch project');
                        }
                        this.project = await response.json();
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
