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
    <title>Cash Flow - OPF-CD</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f7fafc;
            padding: 20px;
        }
        .container { max-width: 1100px; margin: 0 auto; }
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .metric-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        .icon-green { background: #c6f6d5; }
        .icon-blue { background: #bee3f8; }
        .icon-red { background: #fed7d7; }
        .icon-purple { background: #e9d8fd; }
        .metric-label {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 8px;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
        }
        .highlight-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .highlight-card .metric-label { color: rgba(255,255,255,0.9); }
        .highlight-card .metric-value { color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="breadcrumb"><a href="/">← Back to Dashboard</a></div>
        <h1>Cash Flow Analysis</h1>
    </div>

    <div x-data="{
        cash_at_hand: null,
        total_inflows: null,
        total_outflows: null,
        net_cash_flow: null,
        average_monthly_burn: null,
        cash_runway_months: null,
        loading: true
    }" x-init="
        fetch('/api/finance/cash-flow')
            .then(response => response.json())
            .then(data => {
                cash_at_hand = data.cash_at_hand;
                total_inflows = data.total_inflows;
                total_outflows = data.total_outflows;
                net_cash_flow = data.net_cash_flow;
                average_monthly_burn = data.average_monthly_burn;
                cash_runway_months = data.cash_runway_months;
                loading = false;
            })
            .catch(error => {
                console.error('Error:', error);
                loading = false;
            });
    ">
        <div x-show="loading" class="loading">Loading cash flow data...</div>
        
        <div x-show="!loading" class="metrics-grid">
            <div class="metric-card highlight-card">
                <div class="metric-icon icon-green">💵</div>
                <div class="metric-label">Cash at Hand</div>
                <div class="metric-value" x-text="cash_at_hand"></div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon icon-blue">⬆️</div>
                <div class="metric-label">Total Inflows</div>
                <div class="metric-value" x-text="total_inflows"></div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon icon-red">⬇️</div>
                <div class="metric-label">Total Outflows</div>
                <div class="metric-value" x-text="total_outflows"></div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon icon-purple">📊</div>
                <div class="metric-label">Net Cash Flow</div>
                <div class="metric-value" x-text="net_cash_flow"></div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon icon-red">🔥</div>
                <div class="metric-label">Avg Monthly Burn</div>
                <div class="metric-value" x-text="average_monthly_burn"></div>
            </div>
            
            <div class="metric-card highlight-card">
                <div class="metric-icon icon-green">⏱️</div>
                <div class="metric-label">Cash Runway (Months)</div>
                <div class="metric-value" x-text="cash_runway_months"></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
