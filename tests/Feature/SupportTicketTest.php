<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use App\Mail\GuestSupportTicketCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        // Create a test resident
        $this->resident = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Guest can submit a support ticket successfully.
     */
    public function test_guest_can_submit_support_ticket(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/support/tickets', [
            'guest_name' => 'John Doe',
            'guest_email' => 'john@example.com',
            'category' => 'general',
            'subject' => 'Inquiry about phase 2 development',
            'description' => 'I would like to know when the central park in phase 2 will be completed.',
        ]);

        $response->assertStatus(201);
        
        $ticket = SupportTicket::first();
        $this->assertNotNull($ticket);
        $this->assertEquals('John Doe', $ticket->guest_name);
        $this->assertEquals('john@example.com', $ticket->guest_email);
        $this->assertNull($ticket->user_id);
        $this->assertStringStartsWith('OC-TICK-', $ticket->tracking_id);

        Mail::assertSent(GuestSupportTicketCreated::class, function ($mail) use ($ticket) {
            return $mail->hasTo('john@example.com') && $mail->ticket->id === $ticket->id;
        });
    }

    /**
     * Guest ticket validation requires guest name and email.
     */
    public function test_guest_ticket_validation_requires_name_and_email(): void
    {
        $response = $this->postJson('/api/support/tickets', [
            'category' => 'general',
            'subject' => 'Invalid guest ticket',
            'description' => 'Missing guest details',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guest_name', 'guest_email']);
    }

    /**
     * Authenticated resident can submit a support ticket.
     */
    public function test_authenticated_resident_can_submit_support_ticket(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->resident, 'sanctum')
            ->postJson('/api/support/tickets', [
                'category' => 'technical',
                'subject' => 'App loading slowly',
                'description' => 'The feed is taking more than 5 seconds to fetch comments.',
            ]);

        $response->assertStatus(201);

        $ticket = SupportTicket::first();
        $this->assertNotNull($ticket);
        $this->assertEquals($this->resident->id, $ticket->user_id);
        $this->assertNull($ticket->guest_name);
        $this->assertNull($ticket->guest_email);
        $this->assertEquals('technical', $ticket->category);

        // Authenticated residents should NOT trigger guest email notification
        Mail::assertNotSent(GuestSupportTicketCreated::class);
    }

    /**
     * Anyone can track a ticket via tracking_id.
     */
    public function test_anyone_can_track_ticket_by_tracking_id(): void
    {
        $ticket = SupportTicket::create([
            'tracking_id' => 'OC-TICK-TEST1',
            'guest_name' => 'Guest User',
            'guest_email' => 'guest@example.com',
            'category' => 'general',
            'subject' => 'Test Subject',
            'description' => 'Test Description',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/support/tickets/track/OC-TICK-TEST1');

        $response->assertStatus(200)
            ->assertJson([
                'tracking_id' => 'OC-TICK-TEST1',
                'status' => 'pending',
                'category' => 'general',
                'subject' => 'Test Subject',
                'resolution_notes' => null,
            ]);
    }

    /**
     * Tracking a non-existent ticket returns 404.
     */
    public function test_tracking_non_existent_ticket_returns_404(): void
    {
        $response = $this->getJson('/api/support/tickets/track/OC-TICK-FAKE9');
        $response->assertStatus(404);
    }

    /**
     * Residents can retrieve their own tickets only.
     */
    public function test_residents_can_retrieve_only_their_own_tickets(): void
    {
        // Ticket for resident
        SupportTicket::create([
            'tracking_id' => 'OC-TICK-RES1',
            'user_id' => $this->resident->id,
            'category' => 'general',
            'subject' => 'Resident Ticket',
            'description' => 'My request',
        ]);

        // Ticket for another user
        $otherUser = User::factory()->create();
        SupportTicket::create([
            'tracking_id' => 'OC-TICK-RES2',
            'user_id' => $otherUser->id,
            'category' => 'general',
            'subject' => 'Other User Ticket',
            'description' => 'Other request',
        ]);

        $response = $this->actingAs($this->resident, 'sanctum')
            ->getJson('/api/support/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['tracking_id' => 'OC-TICK-RES1'])
            ->assertJsonMissing(['tracking_id' => 'OC-TICK-RES2']);
    }

    /**
     * Ticket status update dispatches event and notifies user.
     */
    public function test_ticket_status_change_dispatches_event_and_notification(): void
    {
        \Illuminate\Support\Facades\Event::fake([
            \App\Events\SupportTicketStatusUpdated::class
        ]);

        $ticket = SupportTicket::create([
            'tracking_id' => 'OC-TICK-NOTIF1',
            'user_id' => $this->resident->id,
            'category' => 'general',
            'subject' => 'Inquiry',
            'description' => 'Test message',
            'status' => 'pending',
        ]);

        // Trigger update
        $ticket->update(['status' => 'resolved']);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\SupportTicketStatusUpdated::class, function ($event) use ($ticket) {
            return $event->ticketId === $ticket->id 
                && $event->status === 'resolved' 
                && $event->userId === $this->resident->id;
        });
    }

    /**
     * Ticket status update notifies user via GeneralNotification when Event listener is executed.
     */
    public function test_ticket_status_change_notifies_user(): void
    {
        $ticket = SupportTicket::create([
            'tracking_id' => 'OC-TICK-NOTIF2',
            'user_id' => $this->resident->id,
            'category' => 'general',
            'subject' => 'Inquiry',
            'description' => 'Test message',
            'status' => 'pending',
        ]);

        \Illuminate\Support\Facades\Notification::fake();

        // Directly dispatch the listener or trigger update (listener will run synchronously in tests)
        $ticket->update(['status' => 'resolved']);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $this->resident,
            \App\Notifications\GeneralNotification::class,
            function ($notification, $channels) use ($ticket) {
                return $notification->title === 'Support Ticket Status Updated'
                    && str_contains($notification->message, 'OC-TICK-NOTIF2')
                    && $notification->metadata['ticket_id'] === $ticket->id;
            }
        );
    }
}

