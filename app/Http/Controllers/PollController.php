<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\ModerationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    /**
     * Display a listing of the polls.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all polls, eager loading options with their votes count, and creator
        $polls = Poll::with(['user.residentProfile', 'options' => function ($query) {
            $query->withCount('votes');
        }])
        ->withCount('votes')
        ->latest()
        ->paginate(15);

        // Map polls to include user's voting state
        $polls->getCollection()->transform(function ($poll) use ($user) {
            $userVote = PollVote::where('poll_id', $poll->id)
                ->where('user_id', $user->id)
                ->first();

            $poll->user_voted_option_id = $userVote ? $userVote->poll_option_id : null;
            return $poll;
        });

        return response()->json($polls);
    }

    /**
     * Store a newly created poll.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Verification guard
        if (!$user->residentProfile || !$user->residentProfile->is_verified) {
            return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
        }

        // Check if the user has an active poll running
        $now = now();
        $hasActivePoll = Poll::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->exists();

        if ($hasActivePoll) {
            return response()->json([
                'message' => 'You already have an active poll running. You cannot create a new poll until it finishes or is stopped.'
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'options' => 'required|array|min:2|max:10',
            'options.*' => 'required|string|max:150',
        ]);

        $poll = DB::transaction(function () use ($user, $validated) {
            $poll = Poll::create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'],
                'status' => 'active',
            ]);

            foreach ($validated['options'] as $optionText) {
                $poll->options()->create([
                    'option_text' => $optionText,
                ]);
            }

            return $poll;
        });

        return response()->json($poll->load(['options', 'user.residentProfile']), 201);
    }

    /**
     * Update the specified poll.
     */
    public function update(Request $request, Poll $poll)
    {
        $user = $request->user();

        // Owner validation
        if ($poll->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized. You do not own this poll.'], 403);
        }

        // Edit validation: Poll should not be edited while active.
        if ($poll->isActive()) {
            return response()->json(['message' => 'Active polls cannot be edited.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'options' => 'nullable|array|min:2|max:10',
            'options.*' => 'required|string|max:150',
        ]);

        DB::transaction(function () use ($poll, $validated) {
            $poll->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'],
            ]);

            // Only update options if they were provided and no votes have been cast yet
            if (isset($validated['options']) && $poll->votes()->count() === 0) {
                $poll->options()->delete();
                foreach ($validated['options'] as $optionText) {
                    $poll->options()->create([
                        'option_text' => $optionText,
                    ]);
                }
            }
        });

        return response()->json($poll->load(['options', 'user.residentProfile']));
    }

    /**
     * Cast a vote on a poll.
     */
    public function vote(Request $request, Poll $poll)
    {
        $user = $request->user();

        // Verification guard
        if (!$user->residentProfile || !$user->residentProfile->is_verified) {
            return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
        }

        // Active poll validation
        if (!$poll->isActive()) {
            return response()->json(['message' => 'This poll is not currently active.'], 403);
        }

        // Duplicate vote validation
        $alreadyVoted = PollVote::where('poll_id', $poll->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyVoted) {
            return response()->json(['message' => 'You have already voted in this poll.'], 422);
        }

        $validated = $request->validate([
            'poll_option_id' => 'required|uuid|exists:poll_options,id',
        ]);

        // Option belongs to poll validation
        $option = PollOption::where('id', $validated['poll_option_id'])
            ->where('poll_id', $poll->id)
            ->first();

        if (!$option) {
            return response()->json(['message' => 'Invalid option selected.'], 422);
        }

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $validated['poll_option_id'],
            'user_id' => $user->id,
        ]);

        // Return updated poll data
        $updatedPoll = Poll::with(['user.residentProfile', 'options' => function ($query) {
            $query->withCount('votes');
        }])
        ->withCount('votes')
        ->find($poll->id);

        $updatedPoll->user_voted_option_id = $validated['poll_option_id'];

        return response()->json($updatedPoll);
    }

    /**
     * Suspend/stop the poll.
     */
    public function suspend(Request $request, Poll $poll)
    {
        $user = $request->user();

        // Allow moderator or creator to suspend the poll
        $isCreator = $poll->user_id === $user->id;
        $isModerator = $user->hasAnyRole(['Super Admin', 'Feed Moderator']);

        if (!$isCreator && !$isModerator) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $poll->status;
        $poll->update(['status' => 'suspended']);

        // Log moderation action
        ModerationLog::create([
            'action' => 'suspend_poll',
            'target_type' => get_class($poll),
            'target_id' => $poll->id,
            'moderator_id' => $user->id,
            'reason' => $validated['reason'] ?? ($isCreator ? 'Poll stopped by author.' : 'Poll suspended by moderator.'),
            'metadata' => json_encode([
                'previous_status' => $oldStatus,
            ]),
        ]);

        return response()->json($poll->load(['options', 'user.residentProfile']));
    }
}
