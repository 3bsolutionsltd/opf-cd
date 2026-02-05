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
    <title>Project Health - OPF-CD</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f7fafc;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
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
        .health-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .status-green { background: #48bb78; color: white; }
        .status-amber { background: #ed8936; color: white; }
        .status-red { background: #f56565; color: white; }
        .score-display {
            text-align: center;
            padding: 30px 0;
        }
        .score-value {
            font-size: 4rem;
            font-weight: 700;
            color: #667eea;
        }
        .score-label {
            font-size: 1.1rem;
            color: #718096;
            margin-top: 10px;
        }
        .signals-section {
            background: #f7fafc;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .signals-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }
        .signals-content {
            color: #4a5568;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .reasons-list {
            list-style: none;
        }
        .reasons-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }
        .reasons-list li:last-child { border-bottom: none; }
        .reasons-list li:before {
            content: '•';
            color: #667eea;
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
            margin-right: 0.5em;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="breadcrumb"><a href="/">← Back to Dashboard</a></div>
        <h1>Project Health Index</h1>
    </div>

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
            })
            .catch(error => {
                console.error('Error:', error);
                loading = false;
            });
    ">
        <div x-show="loading" class="loading">Loading health data...</div>
        
        <div x-show="!loading">
            <div class="health-card">
                <div class="score-display">
                    <div class="score-value" x-text="score"></div>
                    <div class="score-label">Health Score</div>
                </div>
                
                <div style="text-align: center; margin: 20px 0;">
                    <span class="status-badge" 
                          :class="{
                              'status-green': health_status === 'green',
                              'status-amber': health_status === 'amber',
                              'status-red': health_status === 'red'
                          }"
                          x-text="health_status"></span>
                </div>
                
                <div class="signals-section">
                    <div class="signals-title">Signals</div>
                    <div class="signals-content" x-text="JSON.stringify(signals, null, 2)"></div>
                </div>
                
                <div class="signals-section">
                    <div class="signals-title">Reasons</div>
                    <ul class="reasons-list">
                        <template x-for="reason in reasons" :key="reason">
                            <li x-text="reason"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
