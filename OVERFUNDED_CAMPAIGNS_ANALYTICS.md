# Overfunded Campaigns Analytics

## Overview
New analytics endpoints to track and analyze campaigns that have reached or exceeded their 100% funding goal but continue to receive donations. This provides valuable insights into donor behavior and campaign performance beyond the initial target.

---

## Database Relationship Confirmation

✅ **Foreign Key Relationship EXISTS**

The `donations` table has a proper foreign key constraint to the `campaigns` table:

```php
$t->foreignId('campaign_id')->nullable()->constrained();
```

**This means:**
- All donations are automatically linked to their respective campaigns
- When a donor donates to a campaign, the donation is recorded with the `campaign_id`
- Campaign donation totals are automatically calculated in real-time
- Deleting a campaign will handle related donations appropriately (based on cascade rules)

### Campaign Model Relationship

The `Campaign` model has a `donations()` relationship:

```php
public function donations()
{
    return $this->hasMany(Donation::class);
}
```

This allows you to easily query all donations for a campaign:
```php
$campaign->donations // Get all donations
$campaign->donations()->where('status', 'completed')->get() // Get only completed
$campaign->current_amount // Automatically calculated attribute
```

---

## New Endpoints

### 1. Overfunded Campaigns Analytics

**`GET /api/admin/fund-tracking/overfunded-campaigns`**

Analyzes all campaigns that have reached or exceeded 100% of their target amount and continue receiving donations.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `days` | integer | null | Filter donations after 100% by this time period (null = all time) |
| `limit` | integer | 20 | Maximum number of campaigns to return |

#### Response Structure

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
      "date_reached_100_percent": "2025-10-15T14:30:00.000000Z",
      "days_since_100_percent": 23,
      "donations_after_100_count": 45,
      "amount_received_after_100": 43750.00,
      "total_donors": 89,
      "status": "published",
      "deadline_at": "2025-12-31T23:59:59.000000Z"
    }
  ],
  "period_days": null
}
```

#### Key Metrics Explained

- **excess_amount**: Total funds received above the target
- **overfunded_by_percent**: Percentage above 100% (e.g., 187.5% - 100% = 87.5%)
- **date_reached_100_percent**: Exact timestamp when campaign reached its goal
- **days_since_100_percent**: Number of days campaign has been overfunded
- **donations_after_100_count**: Number of donations received after reaching 100%
- **amount_received_after_100**: Total amount donated after reaching goal

#### Use Cases

1. **Identify High-Performing Campaigns**
   - Find campaigns with strong donor support
   - Replicate successful campaign strategies

2. **Fund Allocation Planning**
   - Determine which campaigns have excess funds
   - Plan for fund reallocation or program expansion

3. **Donor Behavior Analysis**
   - Understand why donors continue supporting fully funded campaigns
   - Measure campaign momentum and trust

4. **Transparency Reporting**
   - Show stakeholders how overfunded campaigns are managed
   - Demonstrate platform success metrics

---

### 2. Campaign Donation Timeline

**`GET /api/admin/fund-tracking/campaign-timeline/{campaignId}`**

Shows complete donation history for a specific campaign in chronological order, tracking progress to 100% and beyond.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `campaignId` | integer | Yes | The campaign ID |

#### Response Structure

```json
{
  "campaign": {
    "id": 42,
    "title": "Medical Equipment for Rural Clinic",
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
      "donated_at": "2025-09-15T10:30:00.000000Z",
      "is_after_100_percent": false,
      "milestone_reached": null
    },
    {
      "id": 1524,
      "donor_name": "Anonymous",
      "amount": 2500.00,
      "running_total": 7500.00,
      "progress_percentage": 15.0,
      "status": "completed",
      "donated_at": "2025-09-16T14:20:00.000000Z",
      "is_after_100_percent": false,
      "milestone_reached": null
    },
    {
      "id": 1678,
      "donor_name": "Maria Santos",
      "amount": 3000.00,
      "running_total": 50000.00,
      "progress_percentage": 100.0,
      "status": "completed",
      "donated_at": "2025-10-15T14:30:00.000000Z",
      "is_after_100_percent": false,
      "milestone_reached": "100% Funded!"
    },
    {
      "id": 1679,
      "donor_name": "Pedro Cruz",
      "amount": 1000.00,
      "running_total": 51000.00,
      "progress_percentage": 102.0,
      "status": "completed",
      "donated_at": "2025-10-16T09:15:00.000000Z",
      "is_after_100_percent": true,
      "milestone_reached": null
    }
  ],
  "total_donations": 89,
  "date_reached_100_percent": "2025-10-15T14:30:00.000000Z"
}
```

#### Features

- **Running Total**: Shows cumulative amount after each donation
- **Progress Tracking**: Percentage of target achieved at each donation
- **Milestone Detection**: Flags the exact donation that reached 100%
- **Post-100% Flagging**: Marks all donations received after reaching goal
- **Chronological Order**: Donations ordered by date for timeline visualization

#### Use Cases

1. **Visual Timeline Charts**
   - Create interactive timeline graphs showing campaign progress
   - Highlight the 100% milestone point

2. **Donor Recognition**
   - Identify the donor who helped reach the goal
   - Track early supporters vs late supporters

3. **Campaign Analysis**
   - Analyze donation patterns before and after reaching goal
   - Study donation velocity and momentum

4. **Transparency**
   - Show complete donation history to stakeholders
   - Demonstrate campaign success progression

---

## Usage Examples

### Get All Overfunded Campaigns

```bash
GET /api/admin/fund-tracking/overfunded-campaigns
```

Returns all campaigns that have exceeded their target, sorted by overfunding percentage.

### Get Recent Overfunding Activity

```bash
GET /api/admin/fund-tracking/overfunded-campaigns?days=30
```

Shows campaigns with donations received after 100% in the last 30 days.

### Get Top 10 Most Overfunded

```bash
GET /api/admin/fund-tracking/overfunded-campaigns?limit=10
```

Returns the top 10 campaigns by overfunding percentage.

### Get Specific Campaign Timeline

```bash
GET /api/admin/fund-tracking/campaign-timeline/42
```

Returns complete donation history for campaign ID 42.

---

## Frontend Integration Ideas

### Dashboard Cards

**Overfunded Campaigns Alert**
```
🎯 15 Campaigns Over Target
   Total Excess: ₱125,000
   Average: 123.5% funded
```

### Charts & Visualizations

1. **Overfunding Leaderboard**
   - Bar chart showing top overfunded campaigns
   - Display percentage over target

2. **Timeline Visualization**
   - Line chart showing campaign progress over time
   - Mark the 100% milestone point
   - Color-code donations after 100%

3. **Trend Analysis**
   - Track how many days campaigns stay active after reaching goal
   - Average donations per day before vs after 100%

4. **Distribution Chart**
   - Pie chart showing allocation of excess funds
   - Categories: kept, reallocated, reserved

### Tables

**Overfunded Campaigns Table**
```
| Campaign | Target | Current | Overfunded | Days Since 100% | Action |
|----------|--------|---------|------------|-----------------|---------|
| Medical  | ₱50k   | ₱93.7k  | +87.5%     | 23 days        | View    |
| Education| ₱30k   | ₱51k    | +70%       | 15 days        | View    |
```

---

## Business Insights

### Why Track Overfunded Campaigns?

1. **Donor Trust Indicator**
   - Campaigns that continue receiving donations after reaching goals show high donor confidence
   - Indicates strong charity reputation

2. **Campaign Success Metrics**
   - Identifies which campaign types perform best
   - Helps replicate successful strategies

3. **Fund Management**
   - Excess funds need proper allocation planning
   - Transparency in handling overfunding builds trust

4. **Platform Health**
   - High overfunding rates indicate platform growth
   - Shows donor engagement beyond minimum targets

### Recommended Actions for Overfunded Campaigns

1. **Update Campaign Description**
   - Clearly state how excess funds will be used
   - Add stretch goals

2. **Close or Redirect**
   - Consider closing campaigns far exceeding targets
   - Redirect donors to similar needs

3. **Transparency Report**
   - Regular updates on fund allocation
   - Show impact of excess funding

4. **Donor Communication**
   - Thank donors who continued supporting
   - Explain expanded impact

---

## API Response Notes

- All monetary values are in decimal format with 2 decimal places
- Dates use ISO 8601 format with timezone
- Anonymous donations show as "Anonymous" in donor names
- Campaigns without targets are excluded from analysis
- Only completed donations are counted in calculations
- Running totals are calculated in chronological order

---

## Database Query Performance

- Uses indexed columns: `campaign_id`, `status`, `donated_at`
- Efficient eager loading with `with()` to prevent N+1 queries
- Collection operations for fine-grained filtering
- Suitable for campaigns with thousands of donations

---

## Future Enhancements

Consider adding:
- Email alerts when campaigns reach 100%
- Automatic stretch goal suggestions
- Predictive analytics for overfunding likelihood
- Donor segmentation for post-100% donors
- Campaign comparison tool
- Historical overfunding trends
