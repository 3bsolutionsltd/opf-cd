@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Project Templates</h1>
            <p class="text-gray-600 mt-2">Manage professional project templates for consistent task breakdowns</p>
        </div>
        <button onclick="openModal('createTemplateModal')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition-colors">
            + New Template
        </button>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <h4 class="font-bold">Errors:</h4>
            <ul class="list-disc list-inside mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Templates Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Template</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tasks</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Avg Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($templates as $template)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $template->name }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $template->description }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded text-sm font-semibold">
                                {{ $template->category }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-800 font-semibold">
                            {{ $template->task_count }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-800">
                            {{ $template->average_duration_days ?? 'N/A' }} days
                        </td>
                        <td class="px-6 py-4">
                            @if ($template->is_active)
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded text-xs font-semibold">Active</span>
                            @else
                                <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded text-xs font-semibold">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <button onclick="openEditModal({{ $template->id }})" class="text-blue-600 hover:text-blue-800 font-semibold">Edit</button>
                            <button onclick="openTasksModal({{ $template->id }})" class="text-green-600 hover:text-green-800 font-semibold">Tasks</button>
                            <button onclick="deleteTemplate({{ $template->id }})" class="text-red-600 hover:text-red-800 font-semibold">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center">
                            <p class="text-gray-600">No templates created yet.</p>
                            <button onclick="openModal('createTemplateModal')" class="mt-3 text-blue-600 hover:text-blue-800 font-semibold">
                                Create your first template →
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Template Modal -->
<div id="createTemplateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Create New Template</h3>
            <button onclick="closeModal('createTemplateModal')" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="createTemplateForm" method="POST" action="#" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Template Name</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                <select name="category" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                    <option value="">Select category...</option>
                    <option value="Web App">Web Application</option>
                    <option value="Mobile App">Mobile Application</option>
                    <option value="E-Commerce">E-Commerce</option>
                    <option value="Integration">System Integration</option>
                    <option value="Maintenance">Maintenance & Support</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Average Duration (days)</label>
                <input type="number" name="average_duration_days" min="1" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-blue-500">
            </div>

            <div class="flex gap-2 pt-4">
                <button type="button" onclick="closeModal('createTemplateModal')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded font-semibold hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700">
                    Create Template
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template Tasks Modal -->
<div id="tasksModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-y-auto">
        <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 bg-white">
            <h3 id="tasksTitle" class="text-lg font-bold text-gray-800">Template Tasks</h3>
            <button onclick="closeModal('tasksModal')" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div id="tasksList" class="p-6 space-y-3">
            <!-- Tasks will be loaded here -->
        </div>

        <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex gap-2">
            <button onclick="closeModal('tasksModal')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded font-semibold hover:bg-gray-100">
                Close
            </button>
            <button onclick="openAddTaskModal()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded font-semibold hover:bg-green-700">
                + Add Task
            </button>
        </div>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function openEditModal(templateId) {
        alert('Edit template ' + templateId);
        // TODO: Implement edit modal
    }

    function openTasksModal(templateId) {
        document.getElementById('tasksTitle').textContent = 'Template Tasks (ID: ' + templateId + ')';
        
        // Load tasks via API
        fetch(`/api/admin/templates/${templateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const template = data.data.template;
                    const tasks = data.data.tasks;

                    let tasksHtml = '';
                    if (tasks.length === 0) {
                        tasksHtml = '<p class="text-gray-600 text-center py-4">No tasks in this template</p>';
                    } else {
                        tasks.forEach(task => {
                            tasksHtml += `
                                <div class="flex items-start justify-between pb-3 border-b border-gray-200 last:border-b-0">
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-2">
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-bold">Phase ${task.phase_number}</span>
                                            <h4 class="font-semibold text-gray-800">${task.name}</h4>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">${task.description || ''}</p>
                                        <p class="text-xs text-gray-600 mt-1"><strong>${task.weight}%</strong> weight${task.estimated_duration_days ? `, ~${task.estimated_duration_days} days` : ''}</p>
                                    </div>
                                    <button onclick="deleteTask(${task.id})" class="text-red-600 hover:text-red-800 text-xs font-semibold">Delete</button>
                                </div>
                            `;
                        });
                    }

                    document.getElementById('tasksList').innerHTML = tasksHtml;
                    openModal('tasksModal');
                }
            });
    }

    function openAddTaskModal() {
        alert('Add new task to template');
        // TODO: Implement add task modal
    }

    function deleteTemplate(templateId) {
        if (confirm('Are you sure you want to delete this template? This cannot be undone.')) {
            fetch(`/api/admin/templates/${templateId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Template deleted successfully');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }

    function deleteTask(taskId) {
        if (confirm('Delete this task from the template?')) {
            fetch(`/api/admin/templates/tasks/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Task deleted');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }

    // Form submission
    document.getElementById('createTemplateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('/api/admin/templates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(Object.fromEntries(formData))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Template created successfully');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });
</script>
@endsection
