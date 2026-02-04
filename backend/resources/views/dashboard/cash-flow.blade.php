<div x-data="{
    cash_at_hand: null,
    net_cash_flow: null,
    average_monthly_burn: null,
    cash_runway_months: null,
    loading: true
}" x-init="
    fetch('/finance/cash-flow')
        .then(response => response.json())
        .then(data => {
            cash_at_hand = data.cash_at_hand;
            net_cash_flow = data.net_cash_flow;
            average_monthly_burn = data.average_monthly_burn;
            cash_runway_months = data.cash_runway_months;
            loading = false;
        });
">
    <div x-show="loading">Loading...</div>
    
    <div x-show="!loading">
        <div>
            <span>Cash at Hand:</span>
            <span x-text="cash_at_hand"></span>
        </div>
        
        <div>
            <span>Net Cash Flow:</span>
            <span x-text="net_cash_flow"></span>
        </div>
        
        <div>
            <span>Average Monthly Burn:</span>
            <span x-text="average_monthly_burn"></span>
        </div>
        
        <div>
            <span>Cash Runway Months:</span>
            <span x-text="cash_runway_months"></span>
        </div>
    </div>
</div>
