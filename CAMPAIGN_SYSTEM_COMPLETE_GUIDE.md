# Campaign System Complete Guide

## Overview
This document provides a comprehensive guide to the campaign system, including status management, donation types, recurring campaigns, and automatic processes.

---

## Campaign Status Workflow

### Status Types

| Database Status | Display Status | Description |
|----------------|----------------|-------------|
| `draft` | `pending` | Campaign is being created/edited, not yet published |
| `published` | `active` | Campaign is live and accepting donations |
| `paused` | `pending` | Campaign was active but temporarily paused by charity |
| `closed` | `completed` | Campaign has ended (manually or automatically) |
| `archived` | `archived` | Campaign is archived for record keeping |

### Status Transitions

```
┌─────────────────────────────────────────────────────────────┐
│                    Campaign Lifecycle                        │
└─────────────────────────────────────────────────────────────┘

1. CREATE (Draft)
   ├─ Status: draft
   ├─ Display: pending
   └─ Visible only to charity owner

2. ACTIVATE (Publish)
   ├─ Action: POST /campaigns/{id}/activate
   ├─ Status: draft → published
   ├─ Display: pending → active
   └─ Now visible to public

3. PAUSE (Temporary Stop)
   ├─ Action: POST /campaigns/{id}/pause
   ├─ Status: published → paused
   ├─ Display: active → pending
   └─ Hidden from public, can be reactivated

4. REACTIVATE (Resume)
   ├─ Action: POST /campaigns/{id}/activate
   ├─ Status: paused → published
   ├─ Display: pending → active
   └─ Visible to public again

5. CLOSE (Complete)
   ├─ Manual: Update status to 'closed'
   ├─ Automatic: When end_date or deadline_at is reached
   ├─ Status: published → closed
   ├─ Display: active → completed
   └─ No longer accepting donations
```

---

## Donation Types

### One-Time Campaigns

**Characteristics:**
- Single campaign instance
- Has start_date and end_date
- Automatically closes when end_date is reached
- Remains active even if goal is achieved (until end date)

**Creation:**
```json
{
  "title": "Medical Fund for Community",
  "donation_type": "one_time",
  "start_date": "2025-11-03",
  "end_date": "2025-12-31",
  "target_amount": 100000,
  "status": "draft"
}
```

**Behavior:**
- When `status` is set to `published`, campaign becomes active immediately
- Campaign stays active until `end_date` is reached
- Even if `target_amount` is reached, campaign remains active
- Automatically closes at end date via scheduled task

### Recurring Campaigns

**Characteristics:**
- Parent campaign that spawns child instances
- Creates new campaign occurrences automatically
- Each occurrence has its own start/end dates
- Can be enabled/disabled via `auto_publish` setting

**Creation:**
```json
{
  "title": "Monthly Food Drive",
  "donation_type": "recurring",
  "is_recurring": true,
  "recurrence_type": "monthly",
  "recurrence_interval": 1,
  "recurrence_start_date": "2025-11-01",
  "recurrence_end_date": "2026-11-01",
  "auto_publish": true,
  "target_amount": 50000
}
```

**Recurrence Types:**
- `weekly`: Creates new occurrence every N weeks
- `monthly`: Creates new occurrence every N months
- `quarterly`: Creates new occurrence every N quarters (3 months)
- `yearly`: Creates new occurrence every N years

**Auto-Publish Setting:**
- `auto_publish: true`: New occurrences are created with status `published` (active)
- `auto_publish: false`: New occurrences are created with status `draft` (pending)

**Parent-Child Relationship:**
```
Parent Campaign (Recurring)
│
├─ Nov 2025 ──► Occurrence #1 (Auto-created)
│   ├─ start_date: 2025-11-01
│   ├─ end_date: 2025-11-30
│   └─ status: published (if auto_publish=true)
│
├─ Dec 2025 ──► Occurrence #2 (Auto-created)
│   ├─ start_date: 2025-12-01
│   ├─ end_date: 2025-12-31
│   └─ status: published (if auto_publish=true)
│
└─ ... continues until recurrence_end_date
```

---

## Donation Type Immutability

### Important Rule
**Once a campaign is created, its `donation_type` CANNOT be changed.**

### Why?
- Changing donation type would break the parent-child relationship for recurring campaigns
- One-time campaigns have different data structures than recurring campaigns
- Prevents data inconsistency and logical errors

### Implementation
```php
// In CampaignController@update
if (isset($data['donation_type'])) {
    unset($data['donation_type']);
    Log::warning('Attempted to change donation_type');
}
```

### Frontend Behavior
- When editing a campaign, the donation type field should be:
  - **Disabled** (not editable)
  - **Display only** (show current value)
  - **Not included in update request**

---

## Automatic Processes

### 1. Campaign Closure (Hourly)

**Command:** `campaigns:close-expired`  
**Schedule:** Every hour  
**Purpose:** Automatically close campaigns that have reached their end date

**Logic:**
```php
// Closes campaigns where:
- status = 'published'
- AND (end_date < today OR deadline_at < now)
```

**Example:**
- Campaign created: Nov 1, 2025
- End date: Nov 30, 2025
- Status: published (active)
- On Dec 1, 2025, 01:00 AM → Automatically closed
- Display status changes: active → completed

### 2. Recurring Campaign Processing (Daily)

**Command:** `campaigns:process-recurring`  
**Schedule:** Daily at midnight  
**Purpose:** Create new occurrences for recurring campaigns

**Logic:**
```php
// Finds campaigns where:
- is_recurring = true
- next_occurrence_date <= today
- recurrence_end_date is null OR >= today
```

**Process:**
1. Find parent campaigns due for recurrence
2. Create new child campaign with:
   - Same details as parent
   - New start_date = parent's next_occurrence_date
   - New end_date = calculated based on recurrence_type
   - Status = published (if auto_publish=true) or draft
   - Linked to parent via parent_campaign_id
3. Update parent's next_occurrence_date
4. Stop if recurrence_end_date is reached

---

## API Endpoints

### Campaign Management

#### Create Campaign
```http
POST /api/charities/{charity}/campaigns
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Campaign Title",
  "donation_type": "one_time",
  "start_date": "2025-11-03",
  "end_date": "2025-12-31",
  "status": "draft"
}
```

#### Update Campaign
```http
PUT /api/campaigns/{campaign}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "status": "published"
  // Note: donation_type is NOT allowed
}
```

#### Activate Campaign
```http
POST /api/campaigns/{campaign}/activate
Authorization: Bearer {token}

Response:
{
  "message": "Campaign activated successfully",
  "campaign": {
    "id": 1,
    "status": "published",
    "display_status": "active"
  }
}
```

#### Pause Campaign
```http
POST /api/campaigns/{campaign}/pause
Authorization: Bearer {token}

Response:
{
  "message": "Campaign paused successfully",
  "campaign": {
    "id": 1,
    "status": "paused",
    "display_status": "pending"
  }
}
```

#### List Campaigns (Filtered by Status)
```http
GET /api/charities/{charity}/campaigns
Authorization: Bearer {token}

Response:
{
  "data": [
    {
      "id": 1,
      "status": "draft",
      "display_status": "pending"
    },
    {
      "id": 2,
      "status": "published",
      "display_status": "active"
    },
    {
      "id": 3,
      "status": "paused",
      "display_status": "pending"
    },
    {
      "id": 4,
      "status": "closed",
      "display_status": "completed"
    }
  ]
}
```

---

## Frontend Implementation Guide

### Filtering Campaigns by Status

```javascript
// Get all campaigns
const campaigns = await fetchCampaigns();

// Filter by display_status
const pendingCampaigns = campaigns.filter(c => c.display_status === 'pending');
const activeCampaigns = campaigns.filter(c => c.display_status === 'active');
const completedCampaigns = campaigns.filter(c => c.display_status === 'completed');

// Note: pending includes both draft and paused campaigns
```

### Campaign Action Buttons

```javascript
// Show appropriate buttons based on status
const getActionButtons = (campaign) => {
  switch (campaign.status) {
    case 'draft':
    case 'paused':
      return ['Activate', 'Edit', 'Delete'];
    
    case 'published':
      return ['Pause', 'Edit'];
    
    case 'closed':
      return ['View', 'Archive'];
    
    default:
      return ['View'];
  }
};
```

### Donation Type Display

```javascript
// When editing campaign
<FormField
  label="Donation Type"
  value={campaign.donation_type}
  disabled={true}  // Always disabled
  helpText="Donation type cannot be changed after creation"
/>

// Show recurring settings only if donation_type is 'recurring'
{campaign.donation_type === 'recurring' && (
  <RecurringSettings
    recurrenceType={campaign.recurrence_type}
    recurrenceInterval={campaign.recurrence_interval}
    autoPublish={campaign.auto_publish}
    onUpdate={handleRecurringUpdate}
  />
)}
```

### Auto-Publish Toggle for Recurring Campaigns

```javascript
// Allow charity to enable/disable auto-publish
<Toggle
  label="Automatically publish new occurrences"
  checked={campaign.auto_publish}
  onChange={(value) => updateCampaign({ auto_publish: value })}
  helpText="When enabled, new campaign occurrences will be published automatically"
/>
```

---

## Database Schema

### Campaigns Table

```sql
CREATE TABLE campaigns (
  id BIGINT PRIMARY KEY,
  charity_id BIGINT,
  title VARCHAR(255),
  description TEXT,
  target_amount DECIMAL(12,2),
  
  -- Status
  status ENUM('draft', 'published', 'paused', 'closed', 'archived') DEFAULT 'draft',
  
  -- Donation Type (IMMUTABLE)
  donation_type ENUM('one_time', 'recurring') NOT NULL,
  
  -- Dates
  start_date DATE,
  end_date DATE,
  deadline_at TIMESTAMP,
  
  -- Recurring Campaign Fields
  is_recurring BOOLEAN DEFAULT FALSE,
  recurrence_type ENUM('weekly', 'monthly', 'quarterly', 'yearly'),
  recurrence_interval INT,
  recurrence_start_date DATE,
  recurrence_end_date DATE,
  next_occurrence_date DATE,
  auto_publish BOOLEAN,
  parent_campaign_id BIGINT,
  occurrence_number INT,
  
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## Testing Commands

### Test Campaign Closure
```bash
php artisan campaigns:close-expired
```

### Test Recurring Campaign Processing
```bash
php artisan campaigns:process-recurring
```

### List All Campaigns
```bash
php artisan campaigns:list
```

---

## Common Scenarios

### Scenario 1: Create and Activate One-Time Campaign
1. Create campaign with `status: 'draft'` → Shows as "Pending"
2. Edit and finalize campaign details
3. Click "Activate" button → POST /campaigns/{id}/activate
4. Campaign status changes to `published` → Shows as "Active"
5. Campaign accepts donations until `end_date`
6. Automatically closes when `end_date` is reached → Shows as "Completed"

### Scenario 2: Create Recurring Campaign with Auto-Publish
1. Create campaign with `donation_type: 'recurring'`, `auto_publish: true`
2. Set `recurrence_start_date: '2025-11-01'`
3. Set `recurrence_type: 'monthly'`, `recurrence_interval: 1`
4. Parent campaign is created
5. On Nov 1, daily task creates first occurrence with `status: 'published'`
6. On Dec 1, daily task creates second occurrence with `status: 'published'`
7. Each occurrence automatically closes at its `end_date`

### Scenario 3: Pause and Resume Campaign
1. Active campaign is accepting donations
2. Charity needs to temporarily stop accepting donations
3. Click "Pause" button → POST /campaigns/{id}/pause
4. Campaign status changes to `paused` → Shows as "Pending"
5. Campaign is hidden from public listings
6. When ready, click "Activate" → POST /campaigns/{id}/activate
7. Campaign status changes to `published` → Shows as "Active" again

### Scenario 4: Recurring Campaign with Manual Approval
1. Create campaign with `auto_publish: false`
2. Daily task creates new occurrences with `status: 'draft'`
3. Occurrences show as "Pending" in charity dashboard
4. Charity reviews each occurrence
5. Charity clicks "Activate" on approved occurrences
6. Only activated occurrences become visible to public

---

## Important Notes

1. **Goal Achievement**: Reaching the target amount does NOT automatically close a campaign. It remains active until the end date.

2. **Donation Type Immutability**: Once set, `donation_type` cannot be changed. This is enforced at the controller level.

3. **Paused vs Draft**: Both show as "Pending" but:
   - Draft: Never been published
   - Paused: Was published, then paused

4. **Recurring Campaign Parent**: The parent campaign itself is not shown to donors. Only the child occurrences are visible.

5. **Auto-Publish Toggle**: Can be changed at any time for recurring campaigns. Affects only future occurrences.

6. **Scheduled Tasks**: Require Laravel scheduler to be running:
   ```bash
   php artisan schedule:work
   ```

---

## Troubleshooting

### Campaigns not closing automatically
- Check if scheduler is running: `php artisan schedule:work`
- Manually run: `php artisan campaigns:close-expired`
- Check logs: `storage/logs/laravel.log`

### Recurring campaigns not creating occurrences
- Check if scheduler is running
- Manually run: `php artisan campaigns:process-recurring`
- Verify `next_occurrence_date` is set correctly
- Check `recurrence_end_date` hasn't been reached

### Cannot edit donation_type
- This is intentional and by design
- Create a new campaign if you need a different donation type
