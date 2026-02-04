<div x-data="{
    gap_amount: null,
    gap_percentage: null,
    progress: null,
    earned_value: null,
    received_value: null,
    contract_value: null,
    projectId: {{ $projectId }},
    loading: true
}" x-init="
    fetch(`/projects/${projectId}/payment-gap`)
        .then(response => response.json())
        .then(data => {
            gap_amount = data.gap_amount;
            gap_percentage = data.gap_percentage;
            progress = data.progress;
            earned_value = data.earned_value;
            received_value = data.received_value;
            contract_value = data.contract_value;
            loading = false;
        });
">
    <div x-show="loading">Loading...</div>
    
    <div x-show="!loading">
        <div>
            <span>Gap Amount:</span>
            <span x-text="gap_amount"></span>
        </div>
        
        <div>
            <span>Gap Percentage:</span>
            <span x-text="gap_percentage + '%'"></span>
        </div>
        
        <div>
            <span>Progress:</span>
            <span x-text="progress + '%'"></span>
        </div>
        
        <div>
            <span>Earned Value:</span>
            <span x-text="earned_value"></span>
        </div>
        
        <div>
            <span>Received Value:</span>
            <span x-text="received_value"></span>
        </div>
        
        <div>
            <span>Contract Value:</span>
            <span x-text="contract_value"></span>
        </div>
    </div>
</div>
