# Campaign Status Implementation

## Overview
This implementation adds a `display_status` field to campaigns that maps the database status to user-friendly display values.

## Status Mapping

| Database Status | Display Status | Description |
|----------------|----------------|-------------|
| `draft` | `pending` | Campaign is in draft mode, awaiting publication |
| `published` | `active` | Campaign is live and accepting donations |
| `closed` | `completed` | Campaign has ended |
| `archived` | `archived` | Campaign is archived |

## Implementation Details

### 1. Campaign Model (`app/Models/Campaign.php`)
Added a `display_status` accessor that automatically converts the database status to the display status:

```php
public function getDisplayStatusAttribute()
{
    switch ($this->status) {
        case 'draft':
            return 'pending';
        case 'published':
            return 'active';
        case 'closed':
            return 'completed';
        default:
            return $this->status;
    }
}
```

The `display_status` is automatically appended to all Campaign model instances via the `$appends` property.

### 2. Campaign Controller (`app/Http/Controllers/CampaignController.php`)
The controller methods now automatically include `display_status` in responses:

- **`index()`**: Lists campaigns with `display_status` automatically included
- **`show()`**: Shows single campaign with `display_status`
- **`store()`**: Creates campaign and returns it with `display_status`
- **`update()`**: Updates campaign and returns it with `display_status`

### 3. Workflow

#### Creating a Campaign
1. Charity creates a campaign with `status: 'draft'`
2. API returns campaign with `display_status: 'pending'`
3. Frontend shows campaign as "Pending"

#### Publishing a Campaign
1. Charity edits the draft campaign and changes `status` to `'published'`
2. API returns campaign with `display_status: 'active'`
3. Frontend shows campaign as "Active"
4. Campaign now appears in public listings

#### Filtering Campaigns
When filtering campaigns by status on the frontend:
- Filter by `display_status: 'pending'` to show draft campaigns
- Filter by `display_status: 'active'` to show published campaigns
- Filter by `display_status: 'completed'` to show closed campaigns

## API Endpoints

### GET `/api/charities/{charity}/campaigns`
Returns all campaigns for a charity (if authenticated as owner) or only published campaigns (if public).

**Response includes `display_status`:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Education Fund",
      "status": "draft",
      "display_status": "pending",
      ...
    },
    {
      "id": 2,
      "title": "Medical Aid",
      "status": "published",
      "display_status": "active",
      ...
    }
  ]
}
```

### PUT `/api/campaigns/{campaign}`
Updates a campaign. When changing status from `draft` to `published`, the `display_status` automatically changes from `pending` to `active`.

**Request:**
```json
{
  "status": "published"
}
```

**Response:**
```json
{
  "id": 1,
  "title": "Education Fund",
  "status": "published",
  "display_status": "active",
  ...
}
```

## Frontend Integration

### Filtering Example
```javascript
// Filter campaigns by display status
const filterCampaigns = (campaigns, filterStatus) => {
  if (!filterStatus) return campaigns;
  
  return campaigns.filter(campaign => 
    campaign.display_status === filterStatus
  );
};

// Usage
const pendingCampaigns = filterCampaigns(campaigns, 'pending');
const activeCampaigns = filterCampaigns(campaigns, 'active');
const completedCampaigns = filterCampaigns(campaigns, 'completed');
```

### Status Badge Component
```javascript
const StatusBadge = ({ status }) => {
  const statusConfig = {
    pending: { color: 'yellow', label: 'Pending' },
    active: { color: 'green', label: 'Active' },
    completed: { color: 'gray', label: 'Completed' },
  };
  
  const config = statusConfig[status] || { color: 'gray', label: status };
  
  return (
    <span className={`badge badge-${config.color}`}>
      {config.label}
    </span>
  );
};

// Usage
<StatusBadge status={campaign.display_status} />
```

## Testing

Run the verification script to test the implementation:

```bash
php verify_campaign_status.php
```

This will verify:
- ✓ Draft campaigns show as 'pending'
- ✓ Published campaigns show as 'active'
- ✓ Closed campaigns show as 'completed'
- ✓ Status transitions work correctly
- ✓ display_status is automatically included in JSON output

## Database Schema
No database changes required. The `status` column remains unchanged:
- Database stores: `draft`, `published`, `closed`, `archived`
- API returns both `status` and `display_status`
- Frontend uses `display_status` for display purposes

## Benefits
1. **Backward Compatible**: Existing code using `status` continues to work
2. **User-Friendly**: Display values are more intuitive for users
3. **Automatic**: No manual mapping needed in controllers
4. **Consistent**: All API responses include `display_status`
5. **Flexible**: Easy to add new status mappings in the future
