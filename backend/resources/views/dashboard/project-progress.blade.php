<div x-data="{
    progress: null,
    projectId: {{ $projectId }},
    loading: true
}" x-init="
    fetch(`/projects/${projectId}/progress`)
        .then(response => response.json())
        .then(data => {
            progress = data;
            loading = false;
        });
">
    <div x-show="loading">Loading...</div>
    
    <div x-show="!loading">
        <span>Project Progress:</span>
        <span x-text="progress + '%'"></span>
    </div>
</div>
