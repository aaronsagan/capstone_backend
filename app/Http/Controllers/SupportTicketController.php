<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    /**
     * Get user's support tickets
     */
    public function index(Request $request)
    {
        $tickets = $request->user()
            ->supportTickets()
            ->with(['latestMessage.sender'])
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    /**
     * Create a new support ticket
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ]);

        // Create initial message
        SupportMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_staff' => false,
        ]);

        // Send acknowledgment email
        try {
            Mail::to($request->user()->email)->queue(
                new \App\Mail\Engagement\SupportTicketAcknowledgmentMail($request->user(), $ticket, $validated['message'])
            );
        } catch (\Exception $e) {
            Log::error('Failed to send support ticket acknowledgment email', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }

        $ticket->load(['messages.sender']);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully',
            'ticket' => $ticket
        ], 201);
    }

    /**
     * Get a specific ticket with messages
     */
    public function show(Request $request, $id)
    {
        $ticket = $request->user()
            ->supportTickets()
            ->with(['messages.sender', 'assignedTo'])
            ->findOrFail($id);

        return response()->json($ticket);
    }

    /**
     * Add a message to a ticket
     */
    public function addMessage(Request $request, $id)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $ticket = $request->user()
            ->supportTickets()
            ->findOrFail($id);

        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_staff' => false,
        ]);

        // Update ticket status if it was resolved
        if ($ticket->status === 'resolved' || $ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
        }

        $message->load('sender');

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully',
            'ticket_message' => $message
        ], 201);
    }
}
