<?php

namespace App\Http\Controllers;

use App\Mail\GuestSupportTicketCreated;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    /**
     * Display a listing of support tickets for the authenticated resident.
     */
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    /**
     * Store a newly created support ticket in database.
     */
    public function store(Request $request)
    {
        $rules = [
            'category' => 'required|string|in:general,auth_issue,security,marketplace_dispute,technical',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ];

        // Resolve auth state via Sanctum
        $user = $request->user('sanctum');

        if ($user) {
            // Authenticated resident
            $guestName = null;
            $guestEmail = null;
            $userId = $user->id;
        } else {
            // Anonymous guest
            $rules['guest_name'] = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            
            $guestName = $request->input('guest_name');
            $guestEmail = $request->input('guest_email');
            $userId = null;
        }

        $validated = $request->validate($rules);

        // Generate a unique tracking ID: OC-TICK-XXXXX
        do {
            $trackingId = 'OC-TICK-' . Str::upper(Str::random(5));
        } while (SupportTicket::where('tracking_id', $trackingId)->exists());

        // Create the support ticket
        $ticket = SupportTicket::create([
            'tracking_id' => $trackingId,
            'user_id' => $userId,
            'guest_name' => $guestName,
            'guest_email' => $guestEmail,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        // Send email notification to guest if unauthenticated
        if (!$userId && $guestEmail) {
            try {
                Mail::to($guestEmail)->send(new GuestSupportTicketCreated($ticket));
            } catch (\Exception $e) {
                Log::error('Failed to send support ticket email to guest: ' . $e->getMessage());
            }
        }

        return response()->json($ticket, 201);
    }

    /**
     * Track a support ticket status by its tracking_id.
     */
    public function track($tracking_id)
    {
        $ticket = SupportTicket::where('tracking_id', $tracking_id)->firstOrFail();

        return response()->json([
            'tracking_id' => $ticket->tracking_id,
            'status' => $ticket->status,
            'category' => $ticket->category,
            'subject' => $ticket->subject,
            'resolution_notes' => $ticket->resolution_notes,
            'created_at' => $ticket->created_at,
        ]);
    }
}
