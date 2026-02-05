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
    <title>Payment Gap - OPF-CD</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f7fafc;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
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
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .metric-label {
            font-size: 0.85rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
        }
        .gap-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .gap-highlight .metric-label { color: rgba(255,255,255,0.9); }
        .gap-highlight .metric-value { color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="breadcrumb"><a href="/">← Back to Dashboard</a></div>
        <h1>Payment Gap Analysis</h1>
    </div>

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
        fetch(`/api/projects/${projectId}/payment-gap`)
            .then(response => response.json())
            .then(data => {
                gap_amount = data.gap_amount;
                gap_percentage = data.gap_percentage;
                progress = data.progress;
                earned_value = data.earned_value;
                received_value = data.received_value;
                contract_value = data.contract_value;
                loading = false;
            })
            .catch(error => {
                console.error('Error:', error);
                loading = false;
            });
    ">
        <div x-show="loading" class="loading">Loading payment data...</div>
        
        <div x-show="!loading">
            <div class="metrics-grid">
                <div class="metric-card gap-highlight">
                    <div class="metric-label">Gap Amount</div>
                    <div class="metric-value" x-text="gap_amount"></div>
                </div>
                
                <div class="metric-card gap-highlight">
                    <div class="metric-label">Gap Percentage</div>
                    <div class="metric-value" x-text="gap_percentage + '%'"></div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Progress</div>
                    <div class="metric-value" x-text="progress + '%'"></div>
                </div>
            </div>
            
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Earned Value</div>
                    <div class="metric-value" x-text="earned_value"></div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Received Value</div>
                    <div class="metric-value" x-text="received_value"></div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Contract Value</div>
                    <div class="metric-value" x-text="contract_value"></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
