{{-- 
STRICT VIEW RULE:
This view renders data only.
No calculations.
No decisions.
No service calls.
--}}

<div x-data="{
    project_id: null,
    health_status: null,
    score: null,
    signals: null,
    reasons: [],
    projectId: {{ $projectId }},
    loading: true
}" x-init="
    fetch(`/api/projects/${projectId}/health`)
        .then(response => response.json())
        .then(data => {
            project_id = data.project_id;
            health_status = data.health_status;
            score = data.score;
            signals = data.signals;
            reasons = data.reasons;
            loading = false;
        });
">
    <div x-show="loading">Loading...</div>
    
    <div x-show="!loading">
        <div>
            <span>Project ID:</span>
            <span x-text="project_id"></span>
        </div>
        
        <div>
            <span>Health Status:</span>
            <span x-text="health_status"></span>
        </div>
        
        <div>
            <span>Score:</span>
            <span x-text="score"></span>
        </div>
        
        <div>
            <span>Signals:</span>
            <span x-text="signals"></span>
        </div>
        
        <div>
            <span>Reasons:</span>
            <ul>
                <template x-for="reason in reasons" :key="reason">
                    <li x-text="reason"></li>
                </template>
            </ul>
        </div>
    </div>
</div>
