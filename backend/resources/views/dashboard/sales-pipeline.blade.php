{{-- 
STRICT VIEW RULE:
This view renders data only.
No calculations.
No decisions.
No service calls.
--}}

<div x-data="{
    total_pipeline_value: null,
    weighted_pipeline_value: null,
    opportunity_count: null,
    by_stage: [],
    loading: true
}" x-init="
    fetch('/api/sales/pipeline')
        .then(response => response.json())
        .then(data => {
            total_pipeline_value = data.total_pipeline_value;
            weighted_pipeline_value = data.weighted_pipeline_value;
            opportunity_count = data.opportunity_count;
            by_stage = data.by_stage;
            loading = false;
        });
">
    <div x-show="loading">Loading...</div>
    
    <div x-show="!loading">
        <div>
            <span>Total Pipeline Value:</span>
            <span x-text="total_pipeline_value"></span>
        </div>
        
        <div>
            <span>Weighted Pipeline Value:</span>
            <span x-text="weighted_pipeline_value"></span>
        </div>
        
        <div>
            <span>Opportunity Count:</span>
            <span x-text="opportunity_count"></span>
        </div>
        
        <div>
            <span>By Stage:</span>
            <template x-for="stage in by_stage" :key="stage.stage">
                <div>
                    <div>
                        <span>Stage:</span>
                        <span x-text="stage.stage"></span>
                    </div>
                    
                    <div>
                        <span>Count:</span>
                        <span x-text="stage.count"></span>
                    </div>
                    
                    <div>
                        <span>Total Value:</span>
                        <span x-text="stage.total_value"></span>
                    </div>
                    
                    <div>
                        <span>Weighted Value:</span>
                        <span x-text="stage.weighted_value"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
