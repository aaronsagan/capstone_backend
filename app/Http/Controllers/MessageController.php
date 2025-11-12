<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Get list of conversations
     */
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        // Get unique conversation partners with latest message
        // SECURITY: Using parameter binding to prevent SQL injection
        $conversations = DB::table('messages')
            ->select(
                DB::raw('CASE WHEN sender_id = :userId THEN receiver_id ELSE sender_id END as partner_id'),
                DB::raw('MAX(created_at) as last_message_at'),
                DB::raw('SUM(CASE WHEN receiver_id = :userId AND is_read = 0 THEN 1 ELSE 0 END) as unread_count')
            )
            ->addBinding([$userId, $userId], 'select')
            ->where(function($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->groupBy('partner_id')
            ->orderBy('last_message_at', 'desc')
            ->get();

        // Load user details for each partner
        $conversations = $conversations->map(function($conv) {
            $partner = User::select('id', 'name', 'email', 'profile_image', 'role')
                ->find($conv->partner_id);
            
            return [
                'partner' => $partner,
                'last_message_at' => $conv->last_message_at,
                'unread_count' => $conv->unread_count
            ];
        });

        return response()->json($conversations);
    }

    /**
     * Get conversation with a specific user
     */
    public function conversation(Request $request, $userId)
    {
        $currentUserId = $request->user()->id;

        $messages = Message::where(function($query) use ($currentUserId, $userId) {
                $query->where('sender_id', $currentUserId)
                    ->where('receiver_id', $userId);
            })
            ->orWhere(function($query) use ($currentUserId, $userId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', $currentUserId);
            })
            ->with(['sender:id,name,profile_image', 'receiver:id,name,profile_image'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark received messages as read
        Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        // Get partner info
        $partner = User::select('id', 'name', 'email', 'profile_image', 'role')
            ->findOrFail($userId);

        return response()->json([
            'partner' => $partner,
            'messages' => $messages
        ]);
    }

    /**
     * Send a message
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:5000',
        ]);

        // Prevent sending message to self
        if ($validated['receiver_id'] == $request->user()->id) {
            return response()->json([
                'message' => 'Cannot send message to yourself'
            ], 422);
        }

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $validated['receiver_id'],
            'content' => $validated['content'],
        ]);

        $message->load(['sender', 'receiver']);

        // Send email notification
        try {
            $receiver = User::find($validated['receiver_id']);
            Mail::to($receiver->email)->queue(
                new \App\Mail\Engagement\NewMessageNotificationMail($request->user(), $receiver, $message)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send new message notification email', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }

    /**
     * Get unread message count
     */
    public function unreadCount(Request $request)
    {
        $count = Message::where('receiver_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Request $request, $id)
    {
        $message = Message::where('receiver_id', $request->user()->id)
            ->findOrFail($id);

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read'
        ]);
    }
}
