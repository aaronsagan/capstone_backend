# Charity Analytics API - Quick Reference

## Base URL
All endpoints require authentication and `charity_admin` role.

---

## 1. Dashboard Analytics
**`GET /api/charity/analytics/dashboard`**

Returns main dashboard metrics.

### Response
```json
{
  "summary": {
    "total_campaigns": 5,
    "verified_donations": 45,
    "total_raised": 150000.00,
    "avg_donation": 3333.33,
    "avg_goal_percent": 75.00,
    "pending_donations": 3,
    "unique_donors": 32
  },
  "campaigns_breakdown": {
    "published": 3,
    "draft": 1,
    "closed": 1,
    "archived": 0
  }
}
```

---

## 2. Donation Reports
**`GET /api/charity/analytics/donations?days=30`**

Returns donation reports for specified period.

### Query Params
- `days` (optional, default: 30) - Number of days to analyze

### Response
```json
{
  "period_days": 30,
  "summary": {
    "total_donations": 45,
    "total_amount": 150000.00,
    "pending_donations": 3,
    "average_donation": 3333.33
  },
  "daily_breakdown": [...],
  "by_campaign": [...]
}
```

---

## 3. Campaign Analytics
**`GET /api/charity/analytics/campaigns`**

Returns all campaigns with overfunded analysis.

### Response
```json
{
  "campaigns": [
    {
      "id": 1,
      "title": "Medical Equipment",
      "status": "published",
      "target_amount": 50000.00,
      "total_received": 67500.00,
      "funding_percent": 135.00,
      "donors_count": 45,
      "is_overfunded": true
    }
  ],
  "summary": {
    "total_campaigns": 5,
    "active_campaigns": 3,
    "total_raised": 150000.00,
    "overfunded_count": 2
  },
  "overfunded_campaigns": {
    "summary": {
      "count": 2,
      "total_excess_amount": 25000.00,
      "total_donations_after_100": 15,
      "average_overfunding_percent": 35.00
    },
    "campaigns": [...]
  }
}
```

---

## 4. Overfunded Campaigns List
**`GET /api/charity/analytics/overfunded`**

Returns detailed overfunded campaigns analysis.

### Response
```json
{
  "summary": {
    "count": 2,
    "total_excess_amount": 25000.00,
    "total_donations_after_100": 15,
    "total_amount_after_100": 17500.00,
    "average_overfunding_percent": 35.00
  },
  "campaigns": [
    {
      "campaign_id": 1,
      "title": "Medical Equipment",
      "target_amount": 50000.00,
      "total_received": 67500.00,
      "excess_amount": 17500.00,
      "funding_percent": 135.00,
      "additional_percent_over_100": 35.00,
      "date_reached_100": "2025-10-15 14:30:00",
      "days_since_100": 23,
      "donations_after_100": 10,
      "amount_after_100": 17500.00,
      "total_donors": 45
    }
  ]
}
```

---

## 5. Campaign Timeline
**`GET /api/charity/analytics/overfunded/{campaignId}/timeline`**

Returns donation timeline showing progress to 100% and beyond.

### Path Params
- `campaignId` (required) - Campaign ID

### Response
```json
{
  "campaign": {
    "id": 1,
    "title": "Medical Equipment",
    "target_amount": 50000.00,
    "total_received": 67500.00,
    "funding_percent": 135.00
  },
  "timeline": [
    {
      "donation_id": 145,
      "donor_name": "Maria Santos",
      "amount": 3000.00,
      "running_total": 50000.00,
      "progress_percent": 100.00,
      "donated_at": "2025-10-15 14:30:00",
      "is_after_100": false,
      "milestone": "100% Funded!"
    },
    {
      "donation_id": 146,
      "donor_name": "Pedro Cruz",
      "amount": 2500.00,
      "running_total": 52500.00,
      "progress_percent": 105.00,
      "donated_at": "2025-10-16 09:15:00",
      "is_after_100": true,
      "milestone": null
    }
  ],
  "date_reached_100": "2025-10-15 14:30:00",
  "total_donations": 45
}
```

---

## Frontend Examples

### Fetch Dashboard Data
```javascript
fetch('/api/charity/analytics/dashboard', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(res => res.json())
.then(data => {
  // Display:
  // - Total Campaigns: data.summary.total_campaigns
  // - Verified Donations: data.summary.verified_donations
  // - Total Raised: ₱data.summary.total_raised
  // - Avg Donation: ₱data.summary.avg_donation
  // - Avg Goal %: data.summary.avg_goal_percent%
});
```

### Fetch Overfunded Campaigns
```javascript
fetch('/api/charity/analytics/overfunded', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(res => res.json())
.then(data => {
  console.log(`${data.summary.count} campaigns are overfunded`);
  console.log(`Total excess: ₱${data.summary.total_excess_amount}`);
  
  data.campaigns.forEach(campaign => {
    console.log(`${campaign.title}: +${campaign.additional_percent_over_100}%`);
  });
});
```

### Display Campaign Timeline
```javascript
function showTimeline(campaignId) {
  fetch(`/api/charity/analytics/overfunded/${campaignId}/timeline`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => {
    // Chart showing:
    // - Running total line
    // - Mark where 100% was reached
    // - Highlight donations after 100%
    renderTimelineChart(data.timeline);
  });
}
```

---

## Error Handling

### No Charity Found (404)
```json
{
  "message": "No charity found"
}
```

### Invalid Campaign (404)
```json
{
  "message": "Campaign not found"
}
```

### No Target Amount (400)
```json
{
  "message": "Campaign has no target amount"
}
```

---

## Key Metrics Explained

| Metric | Description |
|--------|-------------|
| `total_campaigns` | Total number of campaigns |
| `verified_donations` | Completed donations count |
| `total_raised` | Sum of all completed donations |
| `avg_donation` | Average amount per donation |
| `avg_goal_percent` | Average funding % across campaigns |
| `excess_amount` | Funds received above target |
| `additional_percent_over_100` | Percentage points above 100% |
| `date_reached_100` | When campaign hit 100% |
| `donations_after_100` | Count of donations after goal |
| `amount_after_100` | Total donated after reaching goal |

---

## Migration from Old Endpoints

| Old Endpoint | New Endpoint | Notes |
|--------------|--------------|-------|
| `/api/charity/dashboard` | `/api/charity/analytics/dashboard` | Fixed zero values |
| N/A | `/api/charity/analytics/donations` | NEW donation reports |
| N/A | `/api/charity/analytics/campaigns` | NEW with overfunded analysis |
| N/A | `/api/charity/analytics/overfunded` | NEW overfunded list |
| N/A | `/api/charity/analytics/overfunded/{id}/timeline` | NEW timeline view |

**The old `/api/charity/dashboard` endpoint still works but use the new endpoints for accurate data.**
