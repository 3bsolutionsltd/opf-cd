@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Apply Template to Project</h1>
        <p class="text-gray-600 mt-2">Add professional task breakdown to your project</p>
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

    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-6">
        <p class="font-semibold">💡 Note:</p>
        <p class="text-sm mt-1">Templates can only be applied to projects without existing tasks. If your project already has tasks, delete them first or create a new project from an opportunity.</p>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <!-- Templates Selection -->
        <div class="col-span-2">
            <div class="space-y-4">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Available Templates</h3>

                @forelse ($templates as $template)
                    <form method="POST" action="#" class="template-apply-form" data-template-id="{{ $template->id }}" data-project-id="{{ $projectId }}">
                        @csrf
                        <input type="hidden" name="template_id" value="{{ $template->id }}">

                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow border-2 border-transparent hover:border-blue-500 p-6">
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
                                <div class="col-span-2 flex gap-2">
                                    <button type="button" class="preview-btn flex-1 text-blue-600 hover:text-blue-800 font-semibold py-2 px-4 border-2 border-blue-600 rounded transition-colors">
                                        Preview
                                    </button>
                                    <button type="submit" class="submit-btn flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition-colors">
                                        Apply Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
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
    <div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-96 overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 id="previewTitle" class="text-xl font-bold text-gray-800">Template Preview</h3>
                <button id="closePreview" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div id="previewContent" class="p-6 space-y-3">
                <!-- Tasks will be loaded here -->
            </div>

            <div class="bg-gray-50 border-t border-gray-200 px-6 py-4">
                <p class="text-sm text-gray-600">These tasks will be added to your project</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Template preview handler
    document.querySelectorAll('.preview-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const templateId = this.closest('.template-apply-form').dataset.templateId;
            loadTemplatePreview(templateId);
        });
    });

    // Form submission handler
    document.querySelectorAll('.template-apply-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const projectId = this.dataset.projectId;
            const templateId = this.dataset.templateId;
            
            // Submit via fetch for better UX
            fetch(`/api/projects/${projectId}/apply-template`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    template_id: parseInt(templateId)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ ${data.data.tasks_count} tasks added from '${data.data.template_name}' template!`);
                    window.location.href = `/projects/${projectId}`;
                } else {
                    alert(`❌ Error: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to apply template');
            });
        });
    });

    // Preview modal handlers
    document.getElementById('closePreview').addEventListener('click', () => {
        document.getElementById('previewModal').classList.add('hidden');
    });

    function loadTemplatePreview(templateId) {
        fetch(`/api/templates/${templateId}/preview`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const template = data.data.template;
                    const tasks = data.data.tasks;

                    document.getElementById('previewTitle').textContent = `${template.name} - Task Breakdown`;

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
                console.error('Error:', error);
                alert('Failed to load template preview');
            });
    }
</script>
@endsection
