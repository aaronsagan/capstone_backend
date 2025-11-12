# Admin Fund Tracking Enhancements

## Overview
The admin fund tracking system has been enhanced to provide comprehensive insights beyond just donation information. The charts and statistics now include donor engagement, fund usage analytics, refund tracking, recurring donations, and growth metrics.

---

## Enhanced Statistics Endpoint

### `GET /api/admin/fund-tracking/statistics`

**Query Parameters:**
- `days` (optional, default: 30) - Time period in days

**Returns comprehensive metrics including:**

### Financial Metrics
- `total_donations` - Total completed donation amount
- `total_disbursements` - Total fund usage/expenses
- `net_flow` - Net cash flow (donations - disbursements)
- `transaction_count` - Total number of transactions
- `average_donation` - Average donation amount
- `pending_amount` - Amount pending approval
- `donation_growth` - Growth percentage compared to previous period

### Donor Metrics
- `unique_donors` - Number of unique donors in period
- `new_donors` - New donors registered in period
- `active_charities` - Number of charities receiving donations

### Donation Status
- `completed_donations_count` - Number of completed donations
- `rejected_donations_count` - Number of rejected donations
- `pending_donations_count` - Number of pending donations

### Refund Metrics
- `total_refunds` - Total amount refunded
- `pending_refunds_count` - Pending refund requests
- `refund_requests_count` - Total refund requests

### Recurring Donations
- `active_recurring_donations` - Number of active recurring donations
- `recurring_revenue` - Total revenue from recurring donations

---

## New Endpoints

### 1. Fund Usage Category Breakdown
**`GET /api/admin/fund-tracking/fund-usage-categories`**

Shows how funds are being spent by category (supplies, staffing, transport, operations, other).

**Query Parameters:**
- `days` (optional, default: 30)

**Returns:**
```json
{
  "breakdown": [
    {
      "category": "Supplies",
      "total_amount": 15000.00,
      "usage_count": 25,
      "percentage": 45.50
    }
  ],
  "total_amount": 33000.00
}
```

---

### 2. Donor Engagement Metrics
**`GET /api/admin/fund-tracking/donor-engagement`**

Analyzes donor behavior and retention.

**Query Parameters:**
- `days` (optional, default: 30)

**Returns:**
```json
{
  "retention_rate": 65.5,
  "returning_donors": 120,
  "new_donors": 45,
  "total_unique_donors": 165,
  "top_donors": [
    {
      "donor_name": "John Doe",
      "donor_email": "john@example.com",
      "total_donated": 50000.00,
      "donation_count": 15
    }
  ],
  "anonymous_donations": 45,
  "identified_donations": 120
}
```

**Metrics Explained:**
- **Retention Rate** - Percentage of donors who donated in both current and previous period
- **Returning Donors** - Donors who donated in previous period and current period
- **New Donors** - First-time donors in the period
- **Top Donors** - List of top 10 donors by total amount

---

### 3. Refund Tracking
**`GET /api/admin/fund-tracking/refund-tracking`**

Tracks refund requests and their status.

**Query Parameters:**
- `days` (optional, default: 30)

**Returns:**
```json
{
  "refunds_by_status": [
    {
      "status": "approved",
      "count": 5,
      "total_amount": 2500.00
    },
    {
      "status": "pending",
      "count": 3,
      "total_amount": 1500.00
    }
  ],
  "recent_refunds": [
    {
      "id": 123,
      "donor_name": "Jane Smith",
      "charity_name": "Hope Foundation",
      "amount": 500.00,
      "status": "pending",
      "reason": "Duplicate payment",
      "requested_at": "2025-11-07T10:30:00Z"
    }
  ],
  "total_refunds": 8,
  "total_refund_amount": 4000.00,
  "pending_count": 3,
  "approved_count": 5,
  "denied_count": 0
}
```

---

### 4. Recurring Donation Statistics
**`GET /api/admin/fund-tracking/recurring-stats`**

Analyzes recurring donation patterns.

**Returns:**
```json
{
  "by_interval": [
    {
      "interval": "monthly",
      "count": 45,
      "total_amount": 125000.00,
      "active_count": 40
    },
    {
      "interval": "weekly",
      "count": 20,
      "total_amount": 35000.00,
      "active_count": 18
    }
  ],
  "by_status": [
    {
      "status": "active",
      "count": 58,
      "total_amount": 160000.00
    },
    {
      "status": "paused",
      "count": 5,
      "total_amount": 5000.00
    }
  ],
  "total_recurring": 65,
  "active_recurring": 58,
  "total_recurring_revenue": 165000.00
}
```

---

### 5. Donation Purpose Breakdown
**`GET /api/admin/fund-tracking/donation-purpose`**

Shows distribution of donations by purpose (general, project, emergency).

**Query Parameters:**
- `days` (optional, default: 30)

**Returns:**
```json
{
  "breakdown": [
    {
      "purpose": "Emergency",
      "total_amount": 45000.00,
      "donation_count": 150
    },
    {
      "purpose": "Project",
      "total_amount": 35000.00,
      "donation_count": 80
    }
  ]
}
```

---

### 6. Overfunded Campaigns Analytics
**`GET /api/admin/fund-tracking/overfunded-campaigns`**

Tracks campaigns that reached 100% of their target but continue receiving donations.

**Query Parameters:**
- `days` (optional, default: null for all time)
- `limit` (optional, default: 20)

**Returns:**
```json
{
  "summary": {
    "total_overfunded_campaigns": 15,
    "total_excess_funds": 125000.00,
    "average_overfunding_percentage": 23.5,
    "highest_overfunding_percentage": 187.5,
    "total_donations_after_100": 234,
    "total_amount_after_100": 85000.00
  },
  "campaigns": [
    {
      "campaign_id": 42,
      "campaign_title": "Medical Equipment for Rural Clinic",
      "charity_name": "Hope Medical Foundation",
      "target_amount": 50000.00,
      "current_amount": 93750.00,
      "excess_amount": 43750.00,
      "funding_percentage": 187.5,
      "overfunded_by_percent": 87.5,
      "date_reached_100_percent": "2025-10-15T14:30:00Z",
      "days_since_100_percent": 23,
      "donations_after_100_count": 45,
      "amount_received_after_100": 43750.00,
      "total_donors": 89,
      "status": "published"
    }
  ]
}
```

**Key Metrics:**
- **excess_amount** - Funds received above target
- **overfunded_by_percent** - Percentage above 100%
- **date_reached_100_percent** - When campaign hit its goal
- **donations_after_100_count** - Donations received after reaching 100%
- **amount_received_after_100** - Total donated after goal reached

---

### 7. Campaign Donation Timeline
**`GET /api/admin/fund-tracking/campaign-timeline/{campaignId}`**

Shows complete donation history for a specific campaign with progress tracking.

**Returns:**
```json
{
  "campaign": {
    "id": 42,
    "title": "Medical Equipment",
    "charity_name": "Hope Medical Foundation",
    "target_amount": 50000.00,
    "current_amount": 93750.00,
    "total_donors": 89,
    "status": "published"
  },
  "timeline": [
    {
      "id": 1523,
      "donor_name": "John Doe",
      "amount": 5000.00,
      "running_total": 5000.00,
      "progress_percentage": 10.0,
      "status": "completed",
      "donated_at": "2025-09-15T10:30:00Z",
      "is_after_100_percent": false,
      "milestone_reached": null
    },
    {
      "id": 1678,
      "donor_name": "Maria Santos",
      "amount": 3000.00,
      "running_total": 50000.00,
      "progress_percentage": 100.0,
      "donated_at": "2025-10-15T14:30:00Z",
      "is_after_100_percent": false,
      "milestone_reached": "100% Funded!"
    }
  ],
  "total_donations": 89,
  "date_reached_100_percent": "2025-10-15T14:30:00Z"
}
```

**Features:**
- Running total tracking
- Progress percentage at each donation
- Milestone detection (100% reached)
- Post-100% donation flagging
- Chronological ordering

---

## Existing Endpoints (Still Available)

### Chart Data
**`GET /api/admin/fund-tracking/chart-data`**
- Time series data for donations vs disbursements
- Groups by day or week depending on period

### Distribution Data
**`GET /api/admin/fund-tracking/distribution`**
- Pie chart data for donations vs disbursements

### Charity Breakdown
**`GET /api/admin/fund-tracking/charity-breakdown`**
- Top 10 charities by donations and spending

### Campaign Type Breakdown
**`GET /api/admin/fund-tracking/campaign-type-breakdown`**
- Donations grouped by campaign type

### Transactions
**`GET /api/admin/fund-tracking/transactions`**
- Detailed list of all transactions (donations and disbursements)

### Export Data
**`GET /api/admin/fund-tracking/export`**
- Export data as CSV

**`GET /api/admin/fund-tracking/export-pdf`**
- Export comprehensive platform report as PDF

---

## Usage Examples

### Get comprehensive statistics for last 90 days
```bash
GET /api/admin/fund-tracking/statistics?days=90
```

### Get donor engagement metrics for last 30 days
```bash
GET /api/admin/fund-tracking/donor-engagement?days=30
```

### Get fund usage category breakdown for last week
```bash
GET /api/admin/fund-tracking/fund-usage-categories?days=7
```

### Get all overfunded campaigns
```bash
GET /api/admin/fund-tracking/overfunded-campaigns
```

### Get top 10 most overfunded campaigns
```bash
GET /api/admin/fund-tracking/overfunded-campaigns?limit=10
```

### Get overfunding activity in last 30 days
```bash
GET /api/admin/fund-tracking/overfunded-campaigns?days=30
```

### Get donation timeline for specific campaign
```bash
GET /api/admin/fund-tracking/campaign-timeline/42
```

---

## Implementation Notes

1. **Time Periods**: All endpoints support the `days` query parameter to adjust the analysis period
2. **Growth Calculations**: Growth metrics compare current period with previous period of same length
3. **Authentication**: All endpoints require admin authentication
4. **Performance**: Queries are optimized with proper indexing
5. **Data Freshness**: Real-time data from database

---

## Frontend Integration Suggestions

### Dashboard Cards
- Show total donations, disbursements, and net flow
- Display unique donors and new donors
- Show donation growth percentage with trend indicator
- Display pending refunds alert
- Show active recurring donations count

### Charts
1. **Time Series Chart** - Donations vs Disbursements over time
2. **Pie Chart** - Fund usage by category
3. **Bar Chart** - Donation purposes breakdown
4. **Line Chart** - Donor retention and growth trends
5. **Donut Chart** - Recurring donation intervals
6. **Overfunding Leaderboard** - Horizontal bar chart showing most overfunded campaigns
7. **Campaign Timeline** - Line chart with milestone markers showing progress to 100% and beyond
8. **Excess Funds Distribution** - Pie chart showing total excess funds by campaign

### Tables
- Top donors list
- Recent refund requests
- Charity performance comparison
- Transaction history
- Overfunded campaigns table with metrics (excess amount, days since 100%, donations after goal)

---

## Database Changes

### Campaign-Donation Relationship
✅ **Foreign Key Already Exists**

The `donations` table has a proper foreign key to `campaigns`:
```php
$t->foreignId('campaign_id')->nullable()->constrained();
```

**This means:**
- All donations are automatically linked to campaigns via `campaign_id`
- When a donor donates to a campaign, the relationship is tracked
- Campaign totals (`current_amount`) are calculated in real-time from related donations
- The `Campaign` model has a `donations()` relationship for easy querying

**Usage:**
```php
$campaign->donations; // Get all donations
$campaign->current_amount; // Auto-calculated total
$campaign->donors_count; // Auto-calculated unique donors
```

### Minimum Amount Validation
Both `donations` and `fund_usage_logs` tables now enforce minimum amount of 1 peso:
- Database-level CHECK constraints
- Model-level validation in `Donation` and `FundUsageLog` models
- Any attempt to create/update with amount < 1 will be rejected

---

## Future Enhancements

Consider adding:
- Predictive analytics for donation trends
- Donor segmentation analysis
- Charity performance scores
- Automated alerts for anomalies
- Budget vs actual spending comparison
- Seasonal trend analysis
