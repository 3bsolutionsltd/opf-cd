@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Create Project from Opportunity</h1>
        <p class="text-gray-600 mt-2">Select a project template to auto-generate a professional workplan</p>
    </div>

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

    <div class="grid grid-cols-3 gap-6">
        <!-- Opportunity Details (Left Sidebar) -->
        <div class="col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Opportunity Details</h3>
                
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600">Client</p>
                        <p class="font-semibold text-gray-800">{{ $opportunity->client }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Estimated Value</p>
                        <p class="font-semibold text-gray-800">
                            {{ $opportunity->currency }} {{ number_format($opportunity->estimated_value, 2) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">Stage</p>
                        <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded text-xs font-semibold">
                            {{ ucfirst($opportunity->stage) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-gray-600">Description</p>
                        <p class="text-gray-700 mt-1">{{ $opportunity->description ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates Selection (Main Content) -->
        <div class="col-span-2">
            <div class="space-y-4">
                @forelse ($templates as $template)
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer border-2 border-transparent hover:border-blue-500"
                         @click="selectedTemplate = {{ $template->id }}; loadTemplatePreview({{ $template->id }})">
                        
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">{{ $template->name }}</h3>
                                    <p class="text-gray-600 text-sm mt-1">{{ $template->description }}</p>
                                </div>
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded text-xs font-semibold">
                                    {{ $template->category }}
                                </span>
                            </div>

                            <div class="grid grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-gray-800">{{ $template->task_count }}</p>
                                    <p class="text-gray-600 text-xs">Tasks</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-gray-800">{{ $template->average_duration_days ?? '?' }}</p>
                                    <p class="text-gray-600 text-xs">Days Typical</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-xs text-gray-600 mb-2">Project Types Covered:</p>
                                    <p class="text-sm font-semibold text-gray-800">Professional workplan with phases</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-6 py-4 rounded">
                        <p class="font-semibold">No templates available</p>
                        <p class="text-sm mt-1">Contact administrator to create project templates</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Template Preview Modal -->
    @if ($templates->count() > 0)
    <div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 mt-8">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-96 overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 id="previewTitle" class="text-xl font-bold text-gray-800">Template Preview</h3>
                <button @click="showPreview = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div id="previewContent" class="p-6 space-y-3">
                <!-- Tasks will be loaded here -->
            </div>

            <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-between items-center">
                <p class="text-sm text-gray-600">Tasks that will be created automatically</p>
                <form id="createProjectForm" method="POST" action="{{ route('opportunities.create-project-with-template', $opportunity->id) }}" class="inline">
                    @csrf
                    <input type="hidden" id="selectedTemplateInput" name="template_id">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition-colors">
                        Create Project with This Template
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    const app = {
        selectedTemplate: null,
        showPreview: false,

        loadTemplatePreview(templateId) {
            this.selectedTemplate = templateId;
            this.showPreview = true;
            document.getElementById('selectedTemplateInput').value = templateId;

            // Fetch template preview via AJAX
            fetch(`/api/templates/${templateId}/preview`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const template = data.data.template;
                        const tasks = data.data.tasks;

                        // Update modal title
                        document.getElementById('previewTitle').textContent = `${template.name} - Task Breakdown`;

                        // Build tasks HTML
                        let tasksHtml = '';
                        tasks.forEach((task, index) => {
                            tasksHtml += `
                                <div class="flex items-start space-x-4 pb-3 border-b border-gray-200 last:border-b-0">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center font-semibold text-sm">
                                        ${task.phase_number}
                                    </div>
                                    <div class="flex-grow">
                                        <h4 class="font-semibold text-gray-800">${task.name}</h4>
                                        <p class="text-sm text-gray-600">${task.description || ''}</p>
                                        <div class="flex items-center space-x-4 mt-2 text-xs">
                                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded">${task.weight}% Weight</span>
                                            ${task.estimated_duration_days ? `<span class="text-gray-600">~${task.estimated_duration_days} days</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        document.getElementById('previewContent').innerHTML = tasksHtml;
                        document.getElementById('previewModal').classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading template preview:', error);
                    alert('Failed to load template preview');
                });
        }
    };

    // Alpine.js-style reactivity for template selection
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-template-id]').forEach(el => {
            el.addEventListener('click', function() {
                const templateId = this.dataset.templateId;
                app.loadTemplatePreview(templateId);
            });
        });
    });
</script>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
@endsection
