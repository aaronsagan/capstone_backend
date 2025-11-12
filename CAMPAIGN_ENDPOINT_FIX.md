# Campaign Endpoint Fix - Frontend Integration

## Issue
The campaigns page was showing "Total Campaigns: 0" even though campaigns exist in the database.

## Root Cause
The frontend was not calling the correct endpoint to fetch the authenticated charity admin's campaigns.

## Solution
Added a new convenient endpoint that automatically fetches campaigns for the authenticated charity admin without requiring the charity ID.

---

## New Endpoint

### GET /api/charity/campaigns

**Purpose:** Get all campaigns for the authenticated charity admin's charity

**Authentication:** Required (Bearer token)

**Authorization:** charity_admin role

**Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 6,
      "charity_id": 1,
      "title": "Wheels of Hope: Mobility Aid for Persons with Disabilities",
      "status": "draft",
      "display_status": "pending",
      "donation_type": "recurring",
      "target_amount": "50000.00",
      "current_amount": 0,
      "donors_count": 0,
      "created_at": "2025-11-02T23:25:18.000000Z",
      "updated_at": "2025-11-02T23:25:18.000000Z"
    },
    {
      "id": 2,
      "charity_id": 1,
      "title": "Teach to Reach: Empowering Young Minds",
      "status": "closed",
      "display_status": "completed",
      "donation_type": "recurring",
      "target_amount": "100000.00",
      "current_amount": 0,
      "donors_count": 0,
      "created_at": "2025-10-27T23:02:38.000000Z",
      "updated_at": "2025-10-27T23:02:38.000000Z"
    }
  ],
  "total": 3,
  "per_page": 12,
  "last_page": 1
}
```

---

## Frontend Integration

### Update Campaign Page API Call

**Before (Incorrect):**
```javascript
// This requires knowing the charity ID
const response = await fetch(`/api/charities/${charityId}/campaigns`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

**After (Correct):**
```javascript
// Automatically uses authenticated user's charity
const response = await fetch('/api/charity/campaigns', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const data = await response.json();
console.log('Total campaigns:', data.total);
console.log('Campaigns:', data.data);
```

### Complete Example

```javascript
// Fetch campaigns for the authenticated charity admin
async function fetchMyCampaigns() {
  try {
    const response = await fetch('/api/charity/campaigns', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error('Failed to fetch campaigns');
    }

    const data = await response.json();
    
    // Update UI
    document.getElementById('total-campaigns').textContent = data.total;
    
    // Filter by status
    const pending = data.data.filter(c => c.display_status === 'pending');
    const active = data.data.filter(c => c.display_status === 'active');
    const completed = data.data.filter(c => c.display_status === 'completed');
    
    console.log('Pending:', pending.length);
    console.log('Active:', active.length);
    console.log('Completed:', completed.length);
    
    return data;
  } catch (error) {
    console.error('Error fetching campaigns:', error);
    return { data: [], total: 0 };
  }
}
```

### With Pagination

```javascript
async function fetchMyCampaigns(page = 1) {
  const response = await fetch(`/api/charity/campaigns?page=${page}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  
  return {
    campaigns: data.data,
    total: data.total,
    currentPage: data.current_page,
    lastPage: data.last_page,
    perPage: data.per_page
  };
}
```

---

## Statistics Calculation

The endpoint returns campaigns with computed attributes:
- `display_status`: User-friendly status (pending/active/completed)
- `current_amount`: Total donations received
- `donors_count`: Number of unique donors

### Calculate Statistics on Frontend

```javascript
const data = await fetchMyCampaigns();

// Total campaigns
const totalCampaigns = data.total;

// Published campaigns (active)
const publishedCampaigns = data.data.filter(c => c.status === 'published').length;

// Total raised across all campaigns
const totalRaised = data.data.reduce((sum, campaign) => {
  return sum + parseFloat(campaign.current_amount || 0);
}, 0);

// Update UI
document.getElementById('total-campaigns').textContent = totalCampaigns;
document.getElementById('published-campaigns').textContent = publishedCampaigns;
document.getElementById('total-raised').textContent = `₱${totalRaised.toLocaleString()}`;
```

---

## Filtering by Status

```javascript
async function fetchCampaignsByStatus(status) {
  const data = await fetchMyCampaigns();
  
  // Filter by display_status
  const filtered = data.data.filter(campaign => {
    if (status === 'all') return true;
    if (status === 'pending') return campaign.display_status === 'pending';
    if (status === 'active') return campaign.display_status === 'active';
    if (status === 'completed') return campaign.display_status === 'completed';
    return true;
  });
  
  return filtered;
}

// Usage
const pendingCampaigns = await fetchCampaignsByStatus('pending');
const activeCampaigns = await fetchCampaignsByStatus('active');
const completedCampaigns = await fetchCampaignsByStatus('completed');
```

---

## Error Handling

```javascript
async function fetchMyCampaigns() {
  try {
    const response = await fetch('/api/charity/campaigns', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });

    if (response.status === 404) {
      // No charity found for this user
      console.warn('No charity found for this user');
      return { data: [], total: 0 };
    }

    if (response.status === 401) {
      // Unauthorized - redirect to login
      window.location.href = '/login';
      return;
    }

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.error('Error fetching campaigns:', error);
    // Show error message to user
    showErrorMessage('Failed to load campaigns. Please try again.');
    return { data: [], total: 0 };
  }
}
```

---

## Implementation Checklist

- [x] Backend endpoint created: `GET /api/charity/campaigns`
- [x] Route registered in `routes/api.php`
- [x] Controller method `myCampaigns()` implemented
- [x] Tested with existing data
- [ ] Frontend updated to use new endpoint
- [ ] Statistics calculation updated
- [ ] Filter functionality updated
- [ ] Error handling implemented

---

## Testing

### Manual Test with cURL

```bash
# Replace {token} with actual Bearer token
curl -X GET "http://localhost:8080/api/charity/campaigns" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Expected Response

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 6,
      "title": "Wheels of Hope",
      "status": "draft",
      "display_status": "pending",
      "current_amount": 0,
      "donors_count": 0
    }
  ],
  "total": 3,
  "per_page": 12
}
```

---

## Summary

**What Changed:**
1. Added `myCampaigns()` method to `CampaignController`
2. Added route `GET /api/charity/campaigns` for charity admins
3. Endpoint automatically finds charity based on authenticated user

**What to Update in Frontend:**
1. Change API endpoint from `/api/charities/{charity}/campaigns` to `/api/charity/campaigns`
2. Remove charity ID from the request
3. Keep the same response handling (pagination, filtering, etc.)

**Benefits:**
- Simpler API call (no need to know charity ID)
- Automatic authorization (only returns campaigns for user's charity)
- Same response format as before
- Works with existing pagination and filtering logic

The campaigns page should now show all campaigns correctly! 🎉
