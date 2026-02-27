<!-- Template Task List Component -->
<div class="space-y-2">
    @forelse ($tasks as $task)
        <div class="flex items-start space-x-3 pb-3 border-b border-gray-100 last:border-b-0">
            <div class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center font-bold text-xs">
                {{ $task->phase_number }}
            </div>
            <div class="flex-grow">
                <h4 class="font-semibold text-gray-800 text-sm">{{ $task->name }}</h4>
                @if ($task->description)
                    <p class="text-gray-600 text-xs mt-1">{{ $task->description }}</p>
                @endif
                <div class="flex items-center space-x-2 mt-2">
                    <span class="inline-block bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs font-semibold">
                        {{ $task->weight }}%
                    </span>
                    @if ($task->estimated_duration_days)
                        <span class="inline-block text-gray-600 text-xs">
                            ~{{ $task->estimated_duration_days }} days
                        </span>
                    @endif
                    @if ($task->dependencies)
                        <span class="inline-block text-yellow-600 text-xs">
                            Depends: Phase {{ $task->dependencies }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <p class="text-gray-500 text-sm p-4 text-center">No tasks in this template</p>
    @endforelse
</div>
