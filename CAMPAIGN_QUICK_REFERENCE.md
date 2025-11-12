# Campaign System - Quick Reference

## Status Mapping

| Database | Display | Description |
|----------|---------|-------------|
| `draft` | `pending` | Being created/edited |
| `paused` | `pending` | Temporarily stopped |
| `published` | `active` | Live and accepting donations |
| `closed` | `completed` | Ended |

## Key Rules

### ✅ DO
- Create campaigns as `draft` first
- Use activate endpoint to publish
- Set end_date for one-time campaigns
- Enable auto_publish for recurring campaigns
- Let campaigns run until end_date

### ❌ DON'T
- Change donation_type after creation (immutable)
- Expect campaigns to close when goal is reached
- Manually close campaigns before end_date (unless needed)

## API Endpoints

```http
# Create
POST /api/charities/{charity}/campaigns

# Activate (draft/paused → published)
POST /api/campaigns/{campaign}/activate

# Pause (published → paused)
POST /api/campaigns/{campaign}/pause

# Update (donation_type NOT allowed)
PUT /api/campaigns/{campaign}
```

## Campaign Types

### One-Time
```json
{
  "donation_type": "one_time",
  "start_date": "2025-11-03",
  "end_date": "2025-12-31"
}
```
- Single instance
- Auto-closes at end_date
- Goal achievement doesn't close it

### Recurring
```json
{
  "donation_type": "recurring",
  "is_recurring": true,
  "recurrence_type": "monthly",
  "recurrence_interval": 1,
  "auto_publish": true
}
```
- Parent spawns children
- Auto-publish controls child status
- Each child has own lifecycle

## Scheduled Tasks

```bash
# Close expired campaigns (hourly)
php artisan campaigns:close-expired

# Create recurring occurrences (daily)
php artisan campaigns:process-recurring

# Run scheduler (development)
php artisan schedule:work
```

## Frontend Integration

### Disable Donation Type When Editing
```javascript
<input 
  type="text" 
  value={campaign.donation_type} 
  disabled={true}
/>
```

### Show Correct Action Buttons
```javascript
{campaign.status === 'draft' || campaign.status === 'paused' ? (
  <Button onClick={activate}>Activate</Button>
) : campaign.status === 'published' ? (
  <Button onClick={pause}>Pause</Button>
) : null}
```

### Filter by Display Status
```javascript
const pending = campaigns.filter(c => c.display_status === 'pending');
const active = campaigns.filter(c => c.display_status === 'active');
const completed = campaigns.filter(c => c.display_status === 'completed');
```

## Common Scenarios

### Scenario 1: Create & Launch Campaign
1. Create with `status: 'draft'` → Shows as "Pending"
2. Edit and finalize details
3. POST `/campaigns/{id}/activate` → Shows as "Active"
4. Accepts donations until end_date
5. Auto-closes at end_date → Shows as "Completed"

### Scenario 2: Pause & Resume
1. Active campaign → POST `/campaigns/{id}/pause`
2. Status: `paused` → Shows as "Pending"
3. Hidden from public
4. POST `/campaigns/{id}/activate` → Shows as "Active" again

### Scenario 3: Recurring with Manual Approval
1. Create with `auto_publish: false`
2. New occurrences created as `draft`
3. Review each occurrence
4. POST `/campaigns/{id}/activate` to publish
5. Only activated ones visible to public

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Campaigns not auto-closing | Run `php artisan schedule:work` |
| Can't edit donation_type | This is intentional - create new campaign |
| Recurring not creating | Check `next_occurrence_date` is set |
| Campaign closed too early | Check end_date and deadline_at |

## Documentation

- **Complete Guide**: `CAMPAIGN_SYSTEM_COMPLETE_GUIDE.md`
- **Fixes Summary**: `CAMPAIGN_SYSTEM_FIXES_SUMMARY.md`
- **This File**: Quick reference for daily use
