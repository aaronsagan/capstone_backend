<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use App\Models\Donation;
use App\Models\RecurringDonation;
use App\Models\SavedItem;
use App\Models\CharityFollow;

class DataExportController extends Controller
{
    /**
     * Export all user data as a downloadable ZIP file
     */
    public function export(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please log in.'
                ], 401);
            }

            // Check if ZipArchive is available
            if (!class_exists('ZipArchive')) {
                Log::error('ZipArchive not available. PHP zip extension is not installed.');
                return response()->json([
                    'success' => false,
                    'message' => 'Server configuration error: ZIP extension not available. Please contact support.'
                ], 500);
            }
            
            // Create exports directory if it doesn't exist
            $exportsDir = storage_path('app/exports');
            if (!file_exists($exportsDir)) {
                if (!mkdir($exportsDir, 0755, true)) {
                    Log::error('Failed to create exports directory: ' . $exportsDir);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create export directory. Please contact support.'
                    ], 500);
                }
            }

            // Create a temporary directory for this export
            $exportId = uniqid('export_') . '_' . $user->id;
            $tempDir = storage_path("app/exports/{$exportId}");
            
            if (!file_exists($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    Log::error('Failed to create temp export directory: ' . $tempDir);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create temporary directory. Please contact support.'
                    ], 500);
                }
            }

            Log::info("Starting data export for user {$user->id}");

            // 1. Profile Data
            $this->exportProfile($user, $tempDir);

            // 2. Donation History
            $this->exportDonations($user, $tempDir);

            // 3. Recurring Donations
            $this->exportRecurringDonations($user, $tempDir);

            // 4. Saved Items & Follows
            $this->exportEngagement($user, $tempDir);

            // 5. Support Tickets
            $this->exportSupportTickets($user, $tempDir);

            // 6. Messages
            $this->exportMessages($user, $tempDir);

            // 7. Sessions
            $this->exportSessions($user, $tempDir);

            // 8. Security & Account Activity
            $this->exportSecurityData($user, $tempDir);

            // Create README
            $this->createReadme($user, $tempDir);

            // Create ZIP file
            $zipPath = storage_path("app/exports/{$exportId}.zip");
            $zip = new ZipArchive();
            
            $zipOpenResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($zipOpenResult !== TRUE) {
                $errorMsg = $this->getZipErrorMessage($zipOpenResult);
                Log::error("Failed to create ZIP file: {$errorMsg}");
                throw new \Exception("Could not create ZIP file: {$errorMsg}");
            }

            // Add all files to ZIP
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $fileCount = 0;
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($tempDir) + 1);
                    
                    // Normalize path separators for cross-platform compatibility
                    $relativePath = str_replace('\\', '/', $relativePath);
                    
                    $zip->addFile($filePath, $relativePath);
                    $fileCount++;
                }
            }

            $zip->close();

            Log::info("Successfully created ZIP with {$fileCount} files for user {$user->id}");

            // Clean up temp directory
            $this->deleteDirectory($tempDir);

            // Verify ZIP file was created
            if (!file_exists($zipPath)) {
                Log::error("ZIP file was not created at: {$zipPath}");
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create export file. Please try again.'
                ], 500);
            }

            // Return the ZIP file
            return response()->download($zipPath, "charityhub_data_{$user->id}_" . date('Y-m-d') . ".zip")
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Data export failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user() ? $request->user()->id : 'unknown'
            ]);
            
            // Clean up on error
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            
            if (isset($zipPath) && file_exists($zipPath)) {
                @unlink($zipPath);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data. Please try again or contact support.'
            ], 500);
        }
    }

    private function getZipErrorMessage($code)
    {
        $errors = [
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_MEMORY => 'Malloc failure',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_OPEN => 'Can\'t open file',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_SEEK => 'Seek error',
        ];
        
        return $errors[$code] ?? "Unknown error code: {$code}";
    }

    private function createReadme($user, $tempDir)
    {
        $readme = "CharityHub Data Export\n";
        $readme .= "======================\n\n";
        $readme .= "Exported for: {$user->name} ({$user->email})\n";
        $readme .= "Export Date: " . date('F j, Y g:i A') . "\n";
        $readme .= "User ID: {$user->id}\n\n";
        $readme .= "This archive contains all your personal data from CharityHub:\n\n";
        $readme .= "- profile.json: Your account information\n";
        $readme .= "- donations.json: Complete donation history\n";
        $readme .= "- recurring_donations.json: Active and past recurring donations\n";
        $readme .= "- engagement.json: Saved items and followed charities\n";
        $readme .= "- support_tickets.json: Your support conversations\n";
        $readme .= "- messages.json: Direct messages\n";
        $readme .= "- sessions.json: Login sessions history\n";
        $readme .= "- security.json: Security-related activities\n\n";
        $readme .= "All data is in JSON format for easy parsing.\n\n";
        $readme .= "For questions: charityhub25@gmail.com\n";

        file_put_contents("{$tempDir}/README.txt", $readme);
    }

    private function exportProfile($user, $tempDir)
    {
        try {
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toDateTimeString() : null,
                'created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
                'updated_at' => $user->updated_at ? $user->updated_at->toDateTimeString() : null,
                'two_factor_enabled' => !empty($user->two_factor_secret),
            ];

            try {
                if ($user->donorProfile) {
                    $data['donor_profile'] = $user->donorProfile->toArray();
                }
            } catch (\Exception $e) {
                Log::warning("Failed to load donor profile: " . $e->getMessage());
            }

            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($jsonData === false) {
                throw new \Exception('Failed to encode profile data to JSON');
            }

            file_put_contents("{$tempDir}/profile.json", $jsonData);
            Log::info("Profile data exported successfully");
        } catch (\Exception $e) {
            Log::error("Failed to export profile: " . $e->getMessage());
            file_put_contents("{$tempDir}/profile.json", json_encode(['error' => 'Failed to export profile data'], JSON_PRETTY_PRINT));
        }
    }

    private function exportDonations($user, $tempDir)
    {
        try {
            $donations = [];
            
            if (class_exists('\App\Models\Donation')) {
                $donations = \App\Models\Donation::where('donor_id', $user->id)
                    ->with(['campaign:id,title', 'charity:id,name'])
                    ->get()
                    ->toArray();
            }

            $jsonData = json_encode($donations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents("{$tempDir}/donations.json", $jsonData);
            Log::info("Donations exported: " . count($donations) . " records");
        } catch (\Exception $e) {
            Log::error("Failed to export donations: " . $e->getMessage());
            file_put_contents("{$tempDir}/donations.json", json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function exportRecurringDonations($user, $tempDir)
    {
        try {
            $recurring = [];
            
            if (class_exists('\App\Models\RecurringDonation')) {
                $recurring = \App\Models\RecurringDonation::where('donor_id', $user->id)
                    ->with(['campaign:id,title', 'charity:id,name'])
                    ->get()
                    ->toArray();
            }

            $jsonData = json_encode($recurring, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents("{$tempDir}/recurring_donations.json", $jsonData);
            Log::info("Recurring donations exported: " . count($recurring) . " records");
        } catch (\Exception $e) {
            Log::error("Failed to export recurring donations: " . $e->getMessage());
            file_put_contents("{$tempDir}/recurring_donations.json", json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function exportEngagement($user, $tempDir)
    {
        try {
            $followedCharities = [];
            $savedItems = [];
            
            try {
                if (class_exists('\App\Models\CharityFollow')) {
                    $followedCharities = \App\Models\CharityFollow::where('user_id', $user->id)
                        ->with('charity:id,name')
                        ->get()
                        ->toArray();
                }
            } catch (\Exception $e) {
                Log::warning("Failed to export followed charities: " . $e->getMessage());
            }
            
            try {
                if (class_exists('\App\Models\SavedItem')) {
                    $savedItems = \App\Models\SavedItem::where('user_id', $user->id)
                        ->with('savable')
                        ->get()
                        ->toArray();
                }
            } catch (\Exception $e) {
                Log::warning("Failed to export saved items: " . $e->getMessage());
            }
            
            $data = [
                'followed_charities' => $followedCharities,
                'saved_items' => $savedItems,
            ];

            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents("{$tempDir}/engagement.json", $jsonData);
            Log::info("Engagement data exported successfully");
        } catch (\Exception $e) {
            Log::error("Failed to export engagement data: " . $e->getMessage());
            file_put_contents("{$tempDir}/engagement.json", json_encode(['followed_charities' => [], 'saved_items' => []], JSON_PRETTY_PRINT));
        }
    }

    private function exportSupportTickets($user, $tempDir)
    {
        try {
            $tickets = [];
            
            if (class_exists('\App\Models\SupportTicket')) {
                $tickets = \App\Models\SupportTicket::where('user_id', $user->id)
                    ->get()
                    ->toArray();
            }

            $jsonData = json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents("{$tempDir}/support_tickets.json", $jsonData);
            Log::info("Support tickets exported: " . count($tickets) . " records");
        } catch (\Exception $e) {
            Log::error("Failed to export support tickets: " . $e->getMessage());
            file_put_contents("{$tempDir}/support_tickets.json", json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function exportMessages($user, $tempDir)
    {
        try {
            $sent = [];
            $received = [];
            
            if (class_exists('\App\Models\Message')) {
                try {
                    $sent = \App\Models\Message::where('sender_id', $user->id)->get()->toArray();
                } catch (\Exception $e) {
                    Log::warning("Failed to export sent messages: " . $e->getMessage());
                }
                
                try {
                    $received = \App\Models\Message::where('receiver_id', $user->id)->get()->toArray();
                } catch (\Exception $e) {
                    Log::warning("Failed to export received messages: " . $e->getMessage());
                }
            }

            $data = [
                'sent_messages' => $sent,
                'received_messages' => $received,
            ];

            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents("{$tempDir}/messages.json", $jsonData);
            Log::info("Messages exported successfully");
        } catch (\Exception $e) {
            Log::error("Failed to export messages: " . $e->getMessage());
            file_put_contents("{$tempDir}/messages.json", json_encode(['sent_messages' => [], 'received_messages' => []], JSON_PRETTY_PRINT));
        }
    }

    private function exportSessions($user, $tempDir)
    {
        try {
            $sessions = [];
            
            if (class_exists('\App\Models\UserSession')) {
                $sessions = \App\Models\UserSession::where('user_id', $user->id)->get()->toArray();
            }

            $jsonData = json_encode($sessions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents("{$tempDir}/sessions.json", $jsonData);
            Log::info("Sessions exported: " . count($sessions) . " records");
        } catch (\Exception $e) {
            Log::error("Failed to export sessions: " . $e->getMessage());
            file_put_contents("{$tempDir}/sessions.json", json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function exportSecurityData($user, $tempDir)
    {
        try {
            $failedLogins = [];
            $emailChanges = [];
            
            try {
                if (class_exists('\App\Models\FailedLogin')) {
                    $failedLogins = \App\Models\FailedLogin::where('email', $user->email)
                        ->latest()
                        ->limit(100)
                        ->get()
                        ->toArray();
                }
            } catch (\Exception $e) {
                Log::warning("Failed to export failed logins: " . $e->getMessage());
            }
            
            try {
                if (class_exists('\App\Models\EmailChange')) {
                    $emailChanges = \App\Models\EmailChange::where('user_id', $user->id)
                        ->get()
                        ->toArray();
                }
            } catch (\Exception $e) {
                Log::warning("Failed to export email changes: " . $e->getMessage());
            }
            
            $data = [
                'failed_logins' => $failedLogins,
                'email_changes' => $emailChanges,
            ];

            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents("{$tempDir}/security.json", $jsonData);
            Log::info("Security data exported successfully");
        } catch (\Exception $e) {
            Log::error("Failed to export security data: " . $e->getMessage());
            file_put_contents("{$tempDir}/security.json", json_encode(['failed_logins' => [], 'email_changes' => []], JSON_PRETTY_PRINT));
        }
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
