# API Testing Examples

## Testing Campaign Status Display

### 1. Get All Campaigns for a Charity
```bash
# As charity owner (authenticated)
curl -X GET "http://localhost:8080/api/charities/1/campaigns" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 7,
      "title": "Test Draft Campaign",
      "status": "published",
      "display_status": "active",
      "charity_id": 1,
      ...
    },
    {
      "id": 8,
      "title": "Test Published Campaign",
      "status": "published",
      "display_status": "active",
      ...
    },
    {
      "id": 9,
      "title": "Test Closed Campaign",
      "status": "closed",
      "display_status": "completed",
      ...
    }
  ],
  "total": 3
}
```

### 2. Create a Draft Campaign
```bash
curl -X POST "http://localhost:8080/api/charities/1/campaigns" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "New Campaign",
    "description": "Campaign description",
    "status": "draft",
    "donation_type": "one_time",
    "campaign_type": "education",
    "beneficiary_category": ["students"],
    "region": "NCR",
    "province": "Metro Manila",
    "city": "Manila",
    "barangay": "Sample Barangay",
    "target_amount": 10000
  }'
```

**Expected Response:**
```json
{
  "message": "Campaign created successfully",
  "campaign": {
    "id": 10,
    "title": "New Campaign",
    "status": "draft",
    "display_status": "pending",
    ...
  }
}
```

### 3. Update Campaign from Draft to Published
```bash
curl -X PUT "http://localhost:8080/api/campaigns/10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "published"
  }'
```

**Expected Response:**
```json
{
  "id": 10,
  "title": "New Campaign",
  "status": "published",
  "display_status": "active",
  ...
}
```

### 4. Get Single Campaign
```bash
curl -X GET "http://localhost:8080/api/campaigns/10" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "id": 10,
  "title": "New Campaign",
  "status": "published",
  "display_status": "active",
  "current_amount": 0,
  "donors_count": 0,
  "total_donations": 0,
  ...
}
```

## Frontend Filtering Example

When you filter campaigns on the frontend, use the `display_status` field:

```javascript
// Get all campaigns
const response = await fetch('http://localhost:8080/api/charities/1/campaigns', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const { data: campaigns } = await response.json();

// Filter by status
const pendingCampaigns = campaigns.filter(c => c.display_status === 'pending');
const activeCampaigns = campaigns.filter(c => c.display_status === 'active');
const completedCampaigns = campaigns.filter(c => c.display_status === 'completed');

console.log('Pending:', pendingCampaigns.length);
console.log('Active:', activeCampaigns.length);
console.log('Completed:', completedCampaigns.length);
```

## Status Workflow

```
┌─────────────────────────────────────────────────────────────┐
│                    Campaign Lifecycle                        │
└─────────────────────────────────────────────────────────────┘

1. CREATE (Draft)
   ├─ Database: status = "draft"
   └─ API Response: display_status = "pending"
   
2. EDIT & PUBLISH
   ├─ Update: status = "published"
   └─ API Response: display_status = "active"
   
3. COMPLETE/CLOSE
   ├─ Update: status = "closed"
   └─ API Response: display_status = "completed"
   
4. ARCHIVE (Optional)
   ├─ Update: status = "archived"
   └─ API Response: display_status = "archived"
```

## Notes

- **Pending** = Draft campaigns waiting to be published
- **Active** = Published campaigns accepting donations
- **Completed** = Closed campaigns that have ended
- The `status` field in the database remains unchanged
- The `display_status` is automatically computed and included in all API responses
