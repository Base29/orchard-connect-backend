<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Events\PostLiked;
use App\Events\ResidentVerificationStatusUpdated;
use App\Events\ListingStatusUpdated;
use App\Models\User;
use App\Models\Post;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendPlatformNotification
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            if ($event instanceof CommentCreated) {
                $comment = $event->comment;
                $commenter = $comment->user;

                if (!$commenter) {
                    return;
                }

                // 1. Post Comment Notification
                if ($comment->post_id) {
                    $post = $comment->post;
                    if ($post && $post->user_id !== $commenter->id) {
                        $postOwner = User::find($post->user_id);
                        if ($postOwner) {
                            $postOwner->notify(new GeneralNotification(
                                'New Comment on Your Post',
                                "{$commenter->name} commented on your post: \"" . Str::limit($comment->content, 30) . "\"",
                                "/dashboard/feed?post={$post->id}",
                                ['type' => 'comment', 'post_id' => $post->id, 'comment_id' => $comment->id]
                            ));
                        }
                    }
                }

                // 2. Listing Comment Notification
                if ($comment->listing_id) {
                    $listing = $comment->listing;
                    if ($listing && $listing->user_id !== $commenter->id) {
                        $listingOwner = User::find($listing->user_id);
                        if ($listingOwner) {
                            $listingOwner->notify(new GeneralNotification(
                                'New Comment on Your Listing',
                                "{$commenter->name} commented on your listing \"{$listing->title}\"",
                                "/dashboard/marketplace/{$listing->id}",
                                ['type' => 'listing_comment', 'listing_id' => $listing->id, 'comment_id' => $comment->id]
                            ));
                        }
                    }
                }

                // 3. Nested Comment Reply Notification
                if ($comment->parent_id) {
                    $parentComment = $comment->parent;
                    if ($parentComment && $parentComment->user_id !== $commenter->id) {
                        $parentCommentOwner = User::find($parentComment->user_id);
                        if ($parentCommentOwner) {
                            $targetUrl = '/dashboard';
                            if ($comment->post_id) {
                                $targetUrl = "/dashboard/feed?post={$comment->post_id}";
                            } elseif ($comment->listing_id) {
                                $targetUrl = "/dashboard/marketplace/{$comment->listing_id}";
                            } elseif ($comment->news_id) {
                                $targetUrl = "/dashboard/news/{$comment->news_id}";
                            }

                            $parentCommentOwner->notify(new GeneralNotification(
                                'Reply to Your Comment',
                                "{$commenter->name} replied to your comment: \"" . Str::limit($comment->content, 30) . "\"",
                                $targetUrl,
                                ['type' => 'comment_reply', 'comment_id' => $comment->id, 'parent_id' => $comment->parent_id]
                            ));
                        }
                    }
                }
            }

            if ($event instanceof PostLiked) {
                if ($event->userId) {
                    $post = Post::find($event->postId);
                    if ($post && $post->user_id !== $event->userId) {
                        $liker = User::find($event->userId);
                        $postOwner = User::find($post->user_id);
                        if ($liker && $postOwner && $event->liked) {
                            $postOwner->notify(new GeneralNotification(
                                'New Like on Your Post',
                                "{$liker->name} liked your post.",
                                "/dashboard/feed?post={$post->id}",
                                ['type' => 'like', 'post_id' => $post->id, 'liker_id' => $event->userId]
                            ));
                        }
                    }
                }
            }

            if ($event instanceof ResidentVerificationStatusUpdated) {
                $user = User::find($event->userId);
                if ($user) {
                    if ($event->status === 'approved') {
                        $user->notify(new GeneralNotification(
                            'Residency Verification Approved',
                            '🎉 Congratulations! Your residency profile has been verified and approved!',
                            '/dashboard',
                            ['type' => 'verification_approved']
                        ));
                    } elseif ($event->status === 'rejected') {
                        $reason = $event->rejectionReason ? str_replace('_', ' ', $event->rejectionReason) : 'Please check details.';
                        $user->notify(new GeneralNotification(
                            'Residency Verification Rejected',
                            "⚠️ Your residency verification was rejected. Reason: {$reason}",
                            '/auth/complete-profile',
                            ['type' => 'verification_rejected', 'reason' => $event->rejectionReason, 'message' => $event->rejectionMessage]
                        ));
                    }
                }
            }

            if ($event instanceof ListingStatusUpdated) {
                if ($event->userId) {
                    $user = User::find($event->userId);
                    if ($user) {
                        $statusText = $event->status === 'active' ? 'approved & listed' : $event->status;
                        $user->notify(new GeneralNotification(
                            'Listing Status Updated',
                            "Your listing \"{$event->listingTitle}\" has been {$statusText}.",
                            "/dashboard/marketplace/{$event->listingId}",
                            ['type' => 'listing_status', 'listing_id' => $event->listingId, 'status' => $event->status]
                        ));
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Notification dispatch listener failed: ' . $e->getMessage(), [
                'exception' => $e,
                'event' => get_class($event)
            ]);
        }
    }
}
