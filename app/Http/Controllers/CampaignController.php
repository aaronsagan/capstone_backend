<?php

namespace App\Http\Controllers;

use App\Models\{Campaign, Charity};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Services\SecurityService;
use App\Jobs\SendNewCampaignNotifications;
use App\Jobs\SendCampaignCompletedEmails;

class CampaignController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Get campaigns for the authenticated charity admin
     */
    public function myCampaigns(Request $r){
        // Get the charity owned by the authenticated user
        $charity = Charity::where('owner_id', $r->user()->id)->first();
        
        if (!$charity) {
            return response()->json([
                'message' => 'No charity found for this user',
                'data' => []
            ], 404);
        }

        // Return all campaigns for this charity with pagination
        return $charity->campaigns()->latest()->paginate(12);
    }

    /**
     * Get public list of all campaigns (for public campaign directory)
     */
    public function publicIndex(Request $r){
        $query = Campaign::where('status', 'published')
            ->with(['charity:id,name,logo_path,description']);
        
        // Optional filters
        if ($r->has('campaign_type')) {
            $query->where('campaign_type', $r->campaign_type);
        }
        
        if ($r->has('region')) {
            $query->where('region', $r->region);
        }
        
        if ($r->has('search')) {
            $search = $r->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sort options
        $sortBy = $r->get('sort', 'latest');
        switch($sortBy) {
            case 'popular':
                $query->orderByDesc('donors_count');
                break;
            case 'ending_soon':
                $query->whereNotNull('deadline_at')
                      ->where('deadline_at', '>', now())
                      ->orderBy('deadline_at', 'asc');
                break;
            case 'almost_funded':
                $query->whereColumn('total_donations_received', '>=', 'target_amount')
                      ->orderByDesc('total_donations_received');
                break;
            default: // latest
                $query->latest();
        }
        
        return $query->paginate(12);
    }

    public function index(Request $r, Charity $charity){
        // If authenticated user owns the charity, show all campaigns
        // display_status is automatically appended by the Campaign model accessor
        if ($r->user() && $charity->owner_id === $r->user()->id) {
            return $charity->campaigns()->latest()->paginate(12);
        }
        // Otherwise only show published campaigns (active)
        return $charity->campaigns()->where('status','published')->latest()->paginate(12);
    }

    public function show(Campaign $campaign){ 
        $campaign->load(['charity:id,name,logo_path,description', 'donationChannels']);
        
        // Add computed stats
        $campaign->total_donations = $campaign->donations()
            ->where('status', 'completed')
            ->count();
        
        // current_amount, donors_count, and display_status are automatically included
        return $campaign;
    }

    public function store(Request $r, Charity $charity){
        try {
            abort_unless($charity->owner_id === $r->user()->id, 403, 'You can only create campaigns for your own charity');

            // Debug: Log incoming request data
            Log::info('Campaign creation request', [
                'all_data' => $r->all(),
                'beneficiary_category' => $r->input('beneficiary_category'),
                'region' => $r->input('region'),
                'province' => $r->input('province'),
                'city' => $r->input('city'),
                'campaign_type' => $r->input('campaign_type'),
            ]);

            $data = $r->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'problem' => 'nullable|string',
                'solution' => 'nullable|string',
                'expected_outcome' => 'nullable|string',
                'outcome' => 'nullable|string',
                'beneficiary' => 'nullable|string|max:1000',
                'beneficiary_category' => 'required|array|min:1',
                'beneficiary_category.*' => 'string|max:100',
                'region' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'barangay' => 'required|string|max:255',
                'target_amount' => 'nullable|numeric|min:0',
                'requires_target_amount' => 'nullable|boolean',
                'deadline_at' => 'nullable|date|after:today',
                'status' => 'in:draft,published,paused,closed,archived',
                'donation_type' => 'required|in:one_time,recurring',
                'campaign_type' => 'required|in:education,feeding_program,medical,disaster_relief,environment,animal_welfare,other',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                // Recurring campaign fields
                'is_recurring' => 'nullable|boolean',
                'recurrence_type' => 'nullable|required_if:is_recurring,true|in:weekly,monthly,quarterly,yearly',
                'recurrence_interval' => 'nullable|integer|min:1|max:12',
                'recurrence_start_date' => 'nullable|required_if:is_recurring,true|date',
                'recurrence_end_date' => 'nullable|date|after:recurrence_start_date',
                'auto_publish' => 'nullable|boolean',
            ]);

            // Normalize target amount based on requires_target_amount flag
            if (array_key_exists('requires_target_amount', $data)) {
                if (!$data['requires_target_amount']) {
                    $data['target_amount'] = null;
                }
            }

            // Map 'outcome' to 'expected_outcome' if provided
            if (isset($data['outcome'])) {
                $data['expected_outcome'] = $data['outcome'];
                unset($data['outcome']);
            }

            // Handle cover image upload
            if ($r->hasFile('cover_image')) {
                $data['cover_image_path'] = $r->file('cover_image')->store('campaign_covers', 'public');
            }

            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'draft';
            }

            // Handle recurring campaign logic
            // If donation_type is recurring, ensure is_recurring is set
            if ($data['donation_type'] === 'recurring') {
                // Default is_recurring to true if not explicitly set
                if (!isset($data['is_recurring'])) {
                    $data['is_recurring'] = true;
                }
                
                if ($data['is_recurring']) {
                    // Set default values for recurring campaigns
                    if (!isset($data['recurrence_interval']) || $data['recurrence_interval'] === null) {
                        $data['recurrence_interval'] = 1;
                    }
                    if (!isset($data['auto_publish'])) {
                        $data['auto_publish'] = true;
                    }
                    if (!isset($data['recurrence_type'])) {
                        $data['recurrence_type'] = 'monthly';
                    }
                    
                    // Calculate next occurrence date based on recurrence_start_date
                    if (isset($data['recurrence_start_date']) && $data['recurrence_start_date']) {
                        $data['next_occurrence_date'] = $this->calculateNextOccurrence(
                            $data['recurrence_start_date'],
                            $data['recurrence_type'],
                            $data['recurrence_interval']
                        );
                    }
                } else {
                    // If is_recurring is explicitly false, set fields to null
                    $data['recurrence_type'] = null;
                    $data['recurrence_interval'] = null;
                    $data['recurrence_start_date'] = null;
                    $data['recurrence_end_date'] = null;
                    $data['next_occurrence_date'] = null;
                    $data['auto_publish'] = null;
                }
            } else {
                // For one_time donations, ensure all recurring fields are null
                $data['is_recurring'] = false;
                $data['recurrence_type'] = null;
                $data['recurrence_interval'] = null;
                $data['recurrence_start_date'] = null;
                $data['recurrence_end_date'] = null;
                $data['next_occurrence_date'] = null;
                $data['auto_publish'] = null;
            }

            $campaign = $charity->campaigns()->create($data);

            // Log campaign creation activity
            $this->securityService->logActivity($r->user(), 'campaign_created', [
                'campaign_id' => $campaign->id,
                'campaign_title' => $campaign->title,
                'charity_id' => $charity->id,
                'charity_name' => $charity->name,
                'campaign_type' => $campaign->campaign_type,
                'target_amount' => $campaign->target_amount,
            ]);

            // Notify followers about new campaign
            if ($campaign->status === 'published') {
                \App\Services\NotificationHelper::newCampaignFromFollowedCharity($campaign);
                
                // Send email notifications to charity followers
                dispatch(new SendNewCampaignNotifications($campaign));
            }

            // display_status is automatically appended by the Campaign model accessor
            return response()->json([
                'message' => 'Campaign created successfully',
                'campaign' => $campaign->load(['charity', 'donationChannels'])
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Campaign creation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'charity_id' => $charity->id,
                'user_id' => $r->user()->id
            ]);

            return response()->json([
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $r, Campaign $campaign){
        abort_unless($campaign->charity->owner_id === $r->user()->id, 403);
        
        $data = $r->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'problem' => 'nullable|string',
            'solution' => 'nullable|string',
            'expected_outcome' => 'nullable|string',
            'outcome' => 'nullable|string',
            'beneficiary' => 'nullable|string|max:1000',
            'beneficiary_category' => 'nullable|array',
            'beneficiary_category.*' => 'string|max:100',
            'region' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'target_amount' => 'nullable|numeric|min:0',
            'requires_target_amount' => 'nullable|boolean',
            'deadline_at' => 'nullable|date',
            'status' => 'sometimes|in:draft,published,paused,closed,archived',
            // donation_type is NOT editable after creation - removed from validation
            'campaign_type' => 'sometimes|in:education,feeding_program,medical,disaster_relief,environment,animal_welfare,other',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            // Recurring campaign fields
            'is_recurring' => 'nullable|boolean',
            'recurrence_type' => 'nullable|in:weekly,monthly,quarterly,yearly',
            'recurrence_interval' => 'nullable|integer|min:1|max:12',
            'recurrence_start_date' => 'nullable|date',
            'recurrence_end_date' => 'nullable|date|after:recurrence_start_date',
            'auto_publish' => 'nullable|boolean',
        ]);

        // Normalize target amount based on requires_target_amount flag on update
        if (array_key_exists('requires_target_amount', $data)) {
            if (!$data['requires_target_amount']) {
                $data['target_amount'] = null;
            }
        }

        // Map 'outcome' to 'expected_outcome' if provided
        if (isset($data['outcome'])) {
            $data['expected_outcome'] = $data['outcome'];
            unset($data['outcome']);
        }

        // Handle cover image upload
        if ($r->hasFile('cover_image')) {
            $data['cover_image_path'] = $r->file('cover_image')->store('campaign_covers', 'public');
        }

        // Handle status change from draft to published - automatically make it active
        // When a draft campaign is published, it becomes active
        if (isset($data['status']) && $data['status'] === 'published' && $campaign->status === 'draft') {
            // Campaign is being published from draft - it's now active
            Log::info('Campaign status changed from draft to published', [
                'campaign_id' => $campaign->id,
                'campaign_title' => $campaign->title,
            ]);
        }

        // Handle recurring campaign logic updates
        if (isset($data['is_recurring']) && $data['is_recurring']) {
            // Recalculate next occurrence if recurrence settings changed
            if (isset($data['recurrence_start_date']) || isset($data['recurrence_type']) || isset($data['recurrence_interval'])) {
                $startDate = $data['recurrence_start_date'] ?? $campaign->recurrence_start_date;
                $type = $data['recurrence_type'] ?? $campaign->recurrence_type;
                $interval = $data['recurrence_interval'] ?? $campaign->recurrence_interval ?? 1;
                
                $data['next_occurrence_date'] = $this->calculateNextOccurrence($startDate, $type, $interval);
            }
        }

        // Prevent donation_type from being changed
        if (isset($data['donation_type'])) {
            unset($data['donation_type']);
            Log::warning('Attempted to change donation_type', [
                'campaign_id' => $campaign->id,
                'user_id' => $r->user()->id,
            ]);
        }

        $campaign->update($data);
        
        // display_status is automatically appended by the Campaign model accessor
        return $campaign->load(['charity', 'donationChannels']);
    }

    /**
     * Activate a campaign (change status from draft/paused to published)
     */
    public function activate(Request $r, Campaign $campaign)
    {
        abort_unless($campaign->charity->owner_id === $r->user()->id, 403);
        
        // Only allow activation if campaign is draft or paused
        if (!in_array($campaign->status, ['draft', 'paused'])) {
            return response()->json([
                'message' => 'Campaign can only be activated if it is in draft or paused status',
                'current_status' => $campaign->status
            ], 422);
        }
        
        $campaign->update(['status' => 'published']);
        
        Log::info('Campaign activated', [
            'campaign_id' => $campaign->id,
            'campaign_title' => $campaign->title,
            'previous_status' => $campaign->getOriginal('status'),
            'user_id' => $r->user()->id,
        ]);
        
        return response()->json([
            'message' => 'Campaign activated successfully',
            'campaign' => $campaign->load(['charity', 'donationChannels'])
        ]);
    }

    /**
     * Pause a campaign (change status from published to paused)
     */
    public function pause(Request $r, Campaign $campaign)
    {
        abort_unless($campaign->charity->owner_id === $r->user()->id, 403);
        
        // Only allow pausing if campaign is published
        if ($campaign->status !== 'published') {
            return response()->json([
                'message' => 'Campaign can only be paused if it is currently published',
                'current_status' => $campaign->status
            ], 422);
        }
        
        $campaign->update(['status' => 'paused']);
        
        Log::info('Campaign paused', [
            'campaign_id' => $campaign->id,
            'campaign_title' => $campaign->title,
            'user_id' => $r->user()->id,
        ]);
        
        return response()->json([
            'message' => 'Campaign paused successfully',
            'campaign' => $campaign->load(['charity', 'donationChannels'])
        ]);
    }

    public function destroy(Request $r, Campaign $campaign){
        abort_unless($campaign->charity->owner_id === $r->user()->id, 403);
        $campaign->delete();
        return response()->noContent();
    }

    /**
     * Get campaign updates/posts
     */
    public function getUpdates(Campaign $campaign)
    {
        // TODO: Implement updates/posts relationship
        // For now, return empty array
        return response()->json(['data' => []]);
    }

    /**
     * Get campaign supporters (donors with their total contributions)
     */
    public function getSupporters(Campaign $campaign)
    {
        $supporters = $campaign->donations()
            ->with('donor:id,name,email')
            ->where('status', 'completed')
            ->selectRaw('donor_id, is_anonymous, SUM(amount) as total_amount, MAX(donated_at) as donated_at, MAX(created_at) as created_at')
            ->groupBy('donor_id', 'is_anonymous')
            ->orderByRaw('SUM(amount) DESC')
            ->get()
            ->map(function ($donation) {
                return [
                    'id' => $donation->donor_id,
                    'donor_id' => $donation->donor_id,
                    'name' => $donation->is_anonymous ? 'Anonymous' : $donation->donor?->name,
                    'donor' => $donation->is_anonymous ? null : [
                        'id' => $donation->donor?->id,
                        'name' => $donation->donor?->name,
                    ],
                    'is_anonymous' => $donation->is_anonymous,
                    'amount' => (float) $donation->total_amount,
                    'total_amount' => (float) $donation->total_amount,
                    'donated_at' => $donation->donated_at,
                    'created_at' => $donation->created_at,
                ];
            });

        return response()->json(['data' => $supporters]);
    }

    /**
     * Get campaign donations with pagination
     */
    public function getDonations(Request $request, Campaign $campaign)
    {
        $donations = $campaign->donations()
            ->with('donor:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json($donations);
    }

    /**
     * Get campaign fund usage breakdown
     */
    public function getFundUsage(Campaign $campaign)
    {
        // TODO: Implement fund usage tracking
        // For now, return empty array
        return response()->json(['data' => []]);
    }

    /**
     * Get campaign statistics
     */
    public function getStats(Campaign $campaign)
    {
        $stats = [
            'total_raised' => $campaign->current_amount ?? 0,
            'target_amount' => $campaign->target_amount ?? 0,
            'donors_count' => $campaign->donors_count ?? 0,
            'donations_count' => $campaign->donations()->where('status', 'completed')->count(),
            'pending_donations' => $campaign->donations()->where('status', 'pending')->count(),
            'progress_percentage' => $campaign->target_amount > 0 
                ? round(($campaign->current_amount / $campaign->target_amount) * 100, 2) 
                : 0,
        ];

        return response()->json($stats);
    }

    /**
     * Calculate next occurrence date for recurring campaigns
     */
    private function calculateNextOccurrence($startDate, $recurrenceType, $interval = 1)
    {
        $date = \Carbon\Carbon::parse($startDate);
        
        switch ($recurrenceType) {
            case 'weekly':
                return $date->addWeeks($interval);
            case 'monthly':
                return $date->addMonths($interval);
            case 'quarterly':
                return $date->addMonths(3 * $interval);
            case 'yearly':
                return $date->addYears($interval);
            default:
                return $date->addMonths($interval);
        }
    }
}
