{{-- 
STRICT VIEW RULE:
This view renders data only.
No calculations.
No decisions.
No service calls.
--}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Pipeline - OPF-CD</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f7fafc;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { font-size: 2rem; color: #2d3748; margin-bottom: 10px; }
        .breadcrumb { color: #718096; font-size: 0.9rem; }
        .breadcrumb a { color: #667eea; text-decoration: none; }
        .loading {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            color: #718096;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-label {
            font-size: 0.85rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .summary-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
        }
        .highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .highlight .summary-label { color: rgba(255,255,255,0.9); }
        .highlight .summary-value { color: white; }
        .stages-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .stages-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 25px;
        }
        .stage-card {
            background: #f7fafc;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #667eea;
        }
        .stage-card:last-child { margin-bottom: 0; }
        .stage-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }
        .stage-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        .stage-metric {
            text-align: center;
        }
        .stage-metric-label {
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .stage-metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="breadcrumb"><a href="/">← Back to Dashboard</a></div>
        <h1>Sales Pipeline Forecast</h1>
    </div>

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
            })
            .catch(error => {
                console.error('Error:', error);
                loading = false;
            });
    ">
        <div x-show="loading" class="loading">Loading pipeline data...</div>
        
        <div x-show="!loading">
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Total Pipeline Value</div>
                    <div class="summary-value" x-text="total_pipeline_value"></div>
                </div>
                
                <div class="summary-card highlight">
                    <div class="summary-label">Weighted Pipeline</div>
                    <div class="summary-value" x-text="weighted_pipeline_value"></div>
                </div>
                
                <div class="summary-card">
                    <div class="summary-label">Opportunities</div>
                    <div class="summary-value" x-text="opportunity_count"></div>
                </div>
            </div>
            
            <div class="stages-section">
                <div class="stages-title">Pipeline by Stage</div>
                <template x-for="stage in by_stage" :key="stage.stage">
                    <div class="stage-card">
                        <div class="stage-name" x-text="stage.stage"></div>
                        <div class="stage-metrics">
                            <div class="stage-metric">
                                <div class="stage-metric-label">Count</div>
                                <div class="stage-metric-value" x-text="stage.count"></div>
                            </div>
                            <div class="stage-metric">
                                <div class="stage-metric-label">Total Value</div>
                                <div class="stage-metric-value" x-text="stage.total_value"></div>
                            </div>
                            <div class="stage-metric">
                                <div class="stage-metric-label">Weighted Value</div>
                                <div class="stage-metric-value" x-text="stage.weighted_value"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
</body>
</html>
