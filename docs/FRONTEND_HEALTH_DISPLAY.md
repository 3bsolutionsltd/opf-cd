# Frontend: Displaying Project Health Details

## Problem
Analytics Dashboard shows "Healthy", "At Risk", or "Critical" with no explanation of why or what to do about it.

## Solution
The API now returns rich health data including `details` and `recommendations` arrays. Frontend needs to display them.

---

## API Response Structure

### Endpoint
```
GET /api/projects/{id}/health
```

### Full Response
```json
{
  "project_id": 5,
  "health_status": "amber",
  "status_label": "At Risk",
  "status_description": "Project has some concerns that need attention.",
  "score": 75,
  "status": "at-risk",
  
  "signals": {
    "payment_gap_percentage": 34.0,
    "payment_gap_amount": 340000.0,
    "earned_value": 850000.0,
    "received_value": 510000.0,
    "project_progress": 85.0,
    "project_status": "active",
    "weighted_pipeline_value": 210000.0,
    "expenses_next_30_days": 1
  },
  
  "reasons": [
    "Payment gap exceeds 20% of earned value"
  ],
  
  "details": [
    "Payment behind schedule: Client owes 34% of earned value (340,000 out of 850,000 earned).",
    "Project 85% complete and on track.",
    "Pipeline value: 210,000 (healthy)",
    "1 expense(s) due in next 30 days."
  ],
  
  "recommendations": [
    "Follow up with client on outstanding payment"
  ]
}
```

---

## Frontend Implementation

### Minimal Update (Quick Fix)
Just show the details as a list:

```jsx
// In your Health component/card
<div className="health-status">
  <StatusBadge status={health.health_status}>
    {health.status_label}
  </StatusBadge>
  <span className="score">Score: {health.score}/100</span>
</div>

{/* ADD THIS - Show details */}
{health.details && health.details.length > 0 && (
  <div className="health-details">
    <ul>
      {health.details.map((detail, index) => (
        <li key={index}>{detail}</li>
      ))}
    </ul>
  </div>
)}

{/* ADD THIS - Show recommendations */}
{health.recommendations && health.recommendations.length > 0 && (
  <div className="health-recommendations">
    <h4>💡 Recommended Actions:</h4>
    <ul>
      {health.recommendations.map((rec, index) => (
        <li key={index}>{rec}</li>
      ))}
    </ul>
  </div>
)}
```

### Enhanced UI (Recommended)

```jsx
function ProjectHealthCard({ projectId }) {
  const [health, setHealth] = useState(null);
  
  useEffect(() => {
    fetch(`/api/projects/${projectId}/health`)
      .then(res => res.json())
      .then(data => setHealth(data));
  }, [projectId]);
  
  if (!health) return <Loading />;
  
  // Determine color scheme
  const getColorScheme = (status) => {
    switch(status) {
      case 'green': return { bg: '#e8f5e9', text: '#2e7d32', icon: '✓' };
      case 'amber': return { bg: '#fff8e1', text: '#f57c00', icon: '⚠️' };
      case 'red': return { bg: '#ffebee', text: '#c62828', icon: '✕' };
      default: return { bg: '#f5f5f5', text: '#666', icon: '?' };
    }
  };
  
  const colors = getColorScheme(health.health_status);
  
  return (
    <Card style={{ backgroundColor: colors.bg }}>
      {/* Header with status */}
      <Header>
        <Icon>{colors.icon}</Icon>
        <Title style={{ color: colors.text }}>
          {health.status_label}
        </Title>
        <Score>
          <CircularProgress value={health.score} />
          <span>{health.score}/100</span>
        </Score>
      </Header>
      
      {/* Status description */}
      <Description>{health.status_description}</Description>
      
      {/* Key metrics */}
      <MetricsGrid>
        <Metric>
          <Label>Progress</Label>
          <Value>{health.signals.project_progress}%</Value>
        </Metric>
        <Metric>
          <Label>Payment Gap</Label>
          <Value>
            {formatCurrency(health.signals.payment_gap_amount)}
            <Small>({health.signals.payment_gap_percentage}%)</Small>
          </Value>
        </Metric>
        <Metric>
          <Label>Earned</Label>
          <Value>{formatCurrency(health.signals.earned_value)}</Value>
        </Metric>
        <Metric>
          <Label>Received</Label>
          <Value>{formatCurrency(health.signals.received_value)}</Value>
        </Metric>
      </MetricsGrid>
      
      {/* Detailed analysis */}
      {health.details && health.details.length > 0 && (
        <Section>
          <SectionTitle>Health Analysis</SectionTitle>
          <DetailsList>
            {health.details.map((detail, idx) => (
              <DetailItem key={idx}>
                <Bullet>•</Bullet>
                <DetailText>{detail}</DetailText>
              </DetailItem>
            ))}
          </DetailsList>
        </Section>
      )}
      
      {/* Actionable recommendations */}
      {health.recommendations && health.recommendations.length > 0 && (
        <Section highlight>
          <SectionTitle>
            <Icon>💡</Icon> Recommended Actions
          </SectionTitle>
          <RecommendationsList>
            {health.recommendations.map((rec, idx) => (
              <RecommendationItem key={idx}>
                <Checkbox />
                <RecommendationText>{rec}</RecommendationText>
              </RecommendationItem>
            ))}
          </RecommendationsList>
        </Section>
      )}
      
      {/* Optional: Show technical reasons */}
      {health.reasons && health.reasons.length > 0 && (
        <TechnicalDetails collapsed>
          <ToggleButton>Show technical details</ToggleButton>
          <ReasonsList>
            {health.reasons.map((reason, idx) => (
              <ReasonBadge key={idx}>{reason}</ReasonBadge>
            ))}
          </ReasonsList>
        </TechnicalDetails>
      )}
    </Card>
  );
}
```

---

## Visual Example

### Before (Unclear)
```
┌─────────────────┐
│ ⚠️  At Risk     │
└─────────────────┘
```
User thinks: "Why? What do I do?"

### After (Clear)
```
┌───────────────────────────────────────────────────┐
│ ⚠️  AT RISK                          Score: 75/100│
│ Project has some concerns that need attention.    │
├───────────────────────────────────────────────────┤
│ Progress: 85%         Payment Gap: 340,000 (34%)  │
│ Earned: 850,000       Received: 510,000           │
├───────────────────────────────────────────────────┤
│ Health Analysis:                                  │
│ • Payment behind schedule: Client owes 34%        │
│   (340,000 of 850,000 earned)                     │
│ • Project 85% complete and on track               │
│ • Pipeline value: 210,000 (healthy)               │
│ • 1 expense(s) due in next 30 days                │
├───────────────────────────────────────────────────┤
│ 💡 Recommended Actions:                           │
│ ☐ Follow up with client on outstanding payment    │
└───────────────────────────────────────────────────┘
```
User knows: What's wrong, why, and what to do.

---

## CSS Styling Example

```css
.health-card {
  border-radius: 8px;
  padding: 20px;
  margin: 16px 0;
}

.health-card.green { 
  background: #e8f5e9; 
  border-left: 4px solid #2e7d32;
}

.health-card.amber { 
  background: #fff8e1; 
  border-left: 4px solid #f57c00;
}

.health-card.red { 
  background: #ffebee; 
  border-left: 4px solid #c62828;
}

.health-details ul {
  list-style: none;
  padding-left: 0;
}

.health-details li {
  padding: 8px 0;
  padding-left: 24px;
  position: relative;
}

.health-details li:before {
  content: "•";
  position: absolute;
  left: 8px;
  font-weight: bold;
}

.health-recommendations {
  margin-top: 16px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 4px;
}

.health-recommendations h4 {
  margin: 0 0 12px 0;
  color: #1976d2;
}

.health-recommendations li {
  padding: 8px 0;
  cursor: pointer;
}

.health-recommendations li:hover {
  background: rgba(25, 118, 210, 0.1);
  padding-left: 8px;
  margin-left: -8px;
  border-radius: 4px;
}
```

---

## Testing Checklist

### Step 1: Verify API Returns Data
Open browser console:
```javascript
fetch('/api/projects/5/health')
  .then(r => r.json())
  .then(data => {
    console.log('Has details?', data.details?.length > 0);
    console.log('Has recommendations?', data.recommendations?.length > 0);
    console.log('Full data:', data);
  });
```

Expected: Both should be `true` with arrays of strings.

### Step 2: Display in UI
- [ ] Health status shows label ("At Risk") not code ("amber")
- [ ] Score shows as number (75/100)
- [ ] Details array renders as list
- [ ] Recommendations array renders as list
- [ ] Details are human-readable (not technical)
- [ ] Recommendations are actionable

### Step 3: Test Different Projects
Test with multiple projects to see different health states:
- Healthy project (score 80+)
- At-risk project (score 50-79)
- Critical project (score < 50)

Each should show appropriate details and recommendations.

---

## Common Mistakes to Avoid

### ❌ Wrong: Only showing status
```jsx
<div>{health.status_label}</div>
```

### ✅ Right: Show status + context
```jsx
<div>
  <h3>{health.status_label} ({health.score}/100)</h3>
  <p>{health.status_description}</p>
  <ul>
    {health.details.map(d => <li>{d}</li>)}
  </ul>
</div>
```

### ❌ Wrong: Ignoring recommendations
```jsx
// Just showing details, no actions
```

### ✅ Right: Make recommendations prominent
```jsx
{health.recommendations.length > 0 && (
  <Alert type="info">
    <strong>💡 Next Steps:</strong>
    {health.recommendations.map(r => <div>{r}</div>)}
  </Alert>
)}
```

---

## Key Takeaways

1. **Always display `details` array** - This is what makes health meaningful
2. **Highlight `recommendations` array** - These are actionable next steps
3. **Use color coding** - Green/Amber/Red for quick visual scanning
4. **Show the score** - Gives quantitative measure of health
5. **Include key metrics** - Payment gap amount, earned vs received

The backend is now providing all the information. The frontend just needs to display it.
