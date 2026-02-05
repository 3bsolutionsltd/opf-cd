<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Projects - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen p-8" x-data="projectsIndex()">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Projects</h1>
                    <p class="text-gray-400 mt-1">Manage your projects</p>
                </div>
                <div class="flex gap-4">
                    <a href="/" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Back to Dashboard
                    </a>
                    <a href="/projects/create" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all">
                        Create Project
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-7xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading projects...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-6">
                <p class="text-red-400" x-text="error"></p>
            </div>
        </div>

        <!-- Delete Error Message -->
        <div x-show="deleteError" class="max-w-7xl mx-auto mb-6">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4">
                <p class="text-red-400" x-text="deleteError"></p>
            </div>
        </div>

        <!-- Delete Success Message -->
        <div x-show="deleteSuccess" class="max-w-7xl mx-auto mb-6">
            <div class="rounded-xl bg-green-500/10 border border-green-500/30 p-4">
                <p class="text-green-400" x-text="deleteSuccess"></p>
            </div>
        </div>

        <!-- Projects Table -->
        <div x-show="!loading && !error" class="max-w-7xl mx-auto">
            <div class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Contract Value</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Dates</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <template x-for="project in projects" :key="project.id">
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <a :href="'/projects/' + project.id" class="font-medium text-indigo-400 hover:text-indigo-300 transition-colors" x-text="project.name"></a>
                                </td>
                                <td class="px-6 py-4 text-gray-300" x-text="project.client"></td>
                                <td class="px-6 py-4">
                                    <span x-text="project.contract_currency + ' ' + Number(project.contract_value).toLocaleString()"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-300">
                                    <div x-text="project.start_date"></div>
                                    <div class="text-xs text-gray-400" x-text="'to ' + project.end_date"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                          :class="{
                                              'bg-blue-500/20 text-blue-400': project.status === 'planned',
                                              'bg-green-500/20 text-green-400': project.status === 'active',
                                              'bg-yellow-500/20 text-yellow-400': project.status === 'on_hold',
                                              'bg-gray-500/20 text-gray-400': project.status === 'completed',
                                              'bg-red-500/20 text-red-400': project.status === 'cancelled'
                                          }"
                                          x-text="project.status"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2" x-data="{ open: false }">
                                        <a :href="'/projects/' + project.id + '/tasks'" class="px-3 py-1 text-sm rounded-lg bg-purple-500/20 hover:bg-purple-500/30 border border-purple-500/30 text-purple-400 transition-all">
                                            Tasks
                                        </a>
                                        <a :href="'/projects/' + project.id + '/milestones'" class="px-3 py-1 text-sm rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/30 text-emerald-400 transition-all">
                                            Milestones
                                        </a>
                                        <div class="relative">
                                            <button @click="open = !open" @click.outside="open = false" class="px-3 py-1 text-sm rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-all">
                                                ⋯
                                            </button>
                                            <div x-show="open" 
                                                 x-transition
                                                 class="absolute right-0 mt-2 w-32 rounded-lg bg-slate-800 border border-white/10 shadow-xl overflow-hidden z-10">
                                                <a :href="'/projects/' + project.id + '/edit'" class="block px-4 py-2 text-sm text-gray-300 hover:bg-white/10 transition-colors">
                                                    Edit
                                                </a>
                                                <button @click="confirmDelete(project.id, project.name); open = false" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-white/10 transition-colors">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="projects.length === 0" class="text-center py-12 text-gray-400">
                    <p>No projects found</p>
                    <a href="/projects/create" class="inline-block mt-4 text-indigo-400 hover:text-indigo-300">
                        Create your first project
                    </a>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/70 transition-opacity" 
                 @click="showDeleteModal = false"></div>
            
            <!-- Modal -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-slate-900 rounded-xl shadow-2xl border border-white/10 max-w-md w-full p-6"
                     @click.stop>
                    <div class="mb-4">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-500/20">
                            <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-100">Delete Project</h3>
                        <p class="mt-2 text-sm text-gray-400">
                            Are you sure you want to delete <span class="font-semibold text-gray-200" x-text="deleteTarget?.name"></span>? 
                            This action cannot be undone.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" 
                                class="flex-1 px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-gray-300">
                            Cancel
                        </button>
                        <button @click="executeDelete()" 
                                class="flex-1 px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 transition-all text-white font-medium">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function projectsIndex() {
            return {
                projects: [],
                loading: true,
                error: '',                deleteError: '',
                deleteSuccess: '',
                async init() {
                    await this.fetchProjects();
                },

                async fetchProjects() {
                    this.loading = true;
                    this.error = '';

                    try {
                        const response = await fetch('/api/projects', {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('Failed to fetch projects');
                        }
                        this.projects = await response.json();
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                confirmDelete(projectId, projectName) {
                    this.deleteTarget = { id: projectId, name: projectName };
                    this.showDeleteModal = true;
                },

                async executeDelete() {
                    if (!this.deleteTarget) return;
                    
                    this.showDeleteModal = false;
                    const projectId = this.deleteTarget.id;
                    this.deleteTarget = null;

                    this.deleteError = '';
                    this.deleteSuccess = '';

                    try {
                        const response = await fetch(`/api/projects/${projectId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.deleteSuccess = data.message || 'Project deleted successfully';
                            setTimeout(() => this.deleteSuccess = '', 3000);
                            await this.fetchProjects();
                        } else {
                            this.deleteError = data.message || 'Failed to delete project';
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    } catch (e) {
                        this.deleteError = 'Error deleting project: ' + e.message;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            }
        }
    </script>
</body>
</html>
