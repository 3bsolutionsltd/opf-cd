<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Task - OPF-CD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        option { background-color: rgb(15 23 42); color: rgb(243 244 246); }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-gray-100">
    <div class="min-h-screen p-8" x-data="taskEdit({{ $taskId }})">
        <!-- Header -->
        <div class="max-w-3xl mx-auto mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Edit Task</h1>
                    <p class="text-gray-400 mt-1">Update task details</p>
                    <p class="text-sm mt-2" x-show="!loading">
                        <span class="text-gray-400">Current Weight Sum (excluding this task):</span>
                        <span class="font-semibold text-indigo-400" x-text="currentWeightSum"></span>/100
                        <span class="text-gray-400 ml-2">Available:</span>
                        <span class="font-semibold" :class="availableWeight >= 0 ? 'text-green-400' : 'text-red-400'" x-text="availableWeight.toFixed(1)"></span>
                    </p>
                </div>
                <a href="javascript:history.back()" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                    Cancel
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="max-w-3xl mx-auto text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            <p class="mt-4 text-gray-400">Loading task...</p>
        </div>

        <!-- Success Message -->
        <div x-show="successMessage" class="max-w-3xl mx-auto mb-6">
            <div class="rounded-xl bg-green-500/10 border border-green-500/30 p-4">
                <p class="text-green-400" x-text="successMessage"></p>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="errorMessage" class="max-w-3xl mx-auto mb-6">
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4">
                <p class="text-red-400" x-text="errorMessage"></p>
            </div>
        </div>

        <!-- Form -->
        <div x-show="!loading" class="max-w-3xl mx-auto">
            <form @submit.prevent="submitUpdate" class="rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-8 space-y-6">
                <!-- Task Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Task Name *</label>
                    <input type="text" x-model="form.name" required
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.name" class="mt-1 text-sm text-red-400" x-text="errors.name"></p>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Category (Optional)</label>
                    <input type="text" x-model="form.category" placeholder="e.g., Development, Design, Testing"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.category" class="mt-1 text-sm text-red-400" x-text="errors.category"></p>
                </div>

                <!-- Weight -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Weight (%) *</label>
                    <input type="number" x-model="form.weight" required min="0" max="100" step="0.1"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p class="mt-1 text-xs" :class="availableWeight - form.weight + originalWeight >= 0 ? 'text-gray-400' : 'text-red-400'">
                        After this change, remaining weight will be: <span x-text="(availableWeight - form.weight + originalWeight).toFixed(1)"></span>%
                    </p>
                    <p x-show="errors.weight" class="mt-1 text-sm text-red-400" x-text="errors.weight"></p>
                </div>

                <!-- Progress -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Progress (%)</label>
                    <input type="number" x-model="form.progress" min="0" max="100" step="0.1"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.progress" class="mt-1 text-sm text-red-400" x-text="errors.progress"></p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                    <select x-model="form.status"
                            class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="todo">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                    <p x-show="errors.status" class="mt-1 text-sm text-red-400" x-text="errors.status"></p>
                </div>

                <!-- Assigned To -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Assigned To (User ID) (Optional)</label>
                    <input type="number" x-model="form.assigned_to" placeholder="User ID"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.assigned_to" class="mt-1 text-sm text-red-400" x-text="errors.assigned_to"></p>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Start Date (Optional)</label>
                    <input type="date" x-model="form.start_date"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.start_date" class="mt-1 text-sm text-red-400" x-text="errors.start_date"></p>
                </div>

                <!-- Due Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Due Date (Optional)</label>
                    <input type="date" x-model="form.due_date"
                           class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-100 focus:outline-none focus:border-indigo-500 transition-colors">
                    <p x-show="errors.due_date" class="mt-1 text-sm text-red-400" x-text="errors.due_date"></p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <a href="javascript:history.back()" class="px-6 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                        Cancel
                    </a>
                    <button type="submit" :disabled="submitting"
                            class="px-6 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Update Task</span>
                        <span x-show="submitting">Updating...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function taskEdit(taskId) {
            return {
                taskId: taskId,
                projectId: null,
                currentWeightSum: 0,
                originalWeight: 0,
                loading: true,
                form: {
                    name: '',
                    category: '',
                    weight: 0,
                    progress: 0,
                    status: 'todo',
                    assigned_to: '',
                    start_date: '',
                    due_date: ''
                },
                errors: {},
                submitting: false,
                successMessage: '',
                errorMessage: '',

                async init() {
                    await this.fetchTask();
                    if (this.projectId) {
                        await this.fetchWeightSum();
                    }
                },

                get availableWeight() {
                    return 100 - this.currentWeightSum;
                },

                async fetchTask() {
                    try {
                        const response = await fetch(`/api/tasks/${this.taskId}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('Failed to fetch task');
                        }
                        const task = await response.json();
                        
                        this.projectId = task.project_id;
                        this.originalWeight = parseFloat(task.weight);
                        this.form.name = task.name;
                        this.form.category = task.category || '';
                        this.form.weight = parseFloat(task.weight);
                        this.form.progress = parseFloat(task.progress);
                        this.form.status = task.status;
                        this.form.assigned_to = task.assigned_to || '';
                        this.form.start_date = task.start_date || '';
                        this.form.due_date = task.due_date || '';
                    } catch (e) {
                        this.errorMessage = e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async fetchWeightSum() {
                    try {
                        const response = await fetch(`/api/projects/${this.projectId}/tasks/weight-sum`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (response.ok) {
                            const data = await response.json();
                            // Weight sum includes current task, so subtract it for display
                            this.currentWeightSum = data.weight_sum - this.originalWeight;
                        }
                    } catch (e) {
                        console.error('Failed to fetch weight sum:', e);
                    }
                },

                async submitUpdate() {
                    this.submitting = true;
                    this.errors = {};
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        const response = await fetch(`/api/tasks/${this.taskId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.successMessage = data.message;
                            setTimeout(() => {
                                window.location.href = `/projects/${this.projectId}/tasks`;
                            }, 1500);
                        } else {
                            if (data.errors) {
                                this.errors = data.errors;
                            } else {
                                this.errorMessage = data.message || 'Failed to update task';
                            }
                        }
                    } catch (e) {
                        this.errorMessage = 'Error updating task: ' + e.message;
                    } finally {
                        this.submitting = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
