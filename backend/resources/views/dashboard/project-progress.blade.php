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
    <title>Project Progress - OPF-CD</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f7fafc;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 10px;
        }
        .breadcrumb {
            color: #718096;
            font-size: 0.9rem;
        }
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
            font-size: 1.1rem;
            color: #718096;
        }
        .progress-value {
            font-size: 4rem;
            font-weight: 700;
            color: #667eea;
            margin: 20px 0;
        }
        .progress-label {
            font-size: 1.2rem;
            color: #4a5568;
            margin-bottom: 10px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 30px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="breadcrumb">
            <a href="/">← Back to Dashboard</a>
        </div>
        <h1>Project Progress</h1>
    </div>

    <div x-data="{
        progress: null,
        projectId: {{ $projectId }},
        loading: true
    }" x-init="
        fetch(`/api/projects/${projectId}/progress`)
            .then(response => response.json())
            .then(data => {
                progress = data;
                loading = false;
            })
            .catch(error => {
                console.error('Error:', error);
                loading = false;
            });
    ">
        <div x-show="loading" class="card loading">
            Loading project data...
        </div>
        
        <div x-show="!loading" class="card">
            <div class="progress-label">Current Progress</div>
            <div class="progress-value">
                <span x-text="progress"></span>%
            </div>
            <div class="progress-bar">
                <div class="progress-fill" :style="`width: ${progress}%`"></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
