<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Notifications\GeneralNotification;

class MentionService
{
    /**
     * Parse content and notify mentioned users.
     *
     * @param string $content
     * @param mixed $source
     * @param User $author
     * @return void
     */
    public static function processMentions(string $content, $source, User $author): void
    {
        // Check for @all mention
        $hasMentionAll = preg_match('/@\[all\]\(user:all\)/', $content);

        // Regex to match @[Name](user:UserUUID)
        preg_match_all('/@\[([^\]]+)\]\(user:([a-fA-F0-9-]+)\)/', $content, $matches);

        // Collect user IDs to notify
        $userIds = [];
        if (!empty($matches[2])) {
            $userIds = array_diff(array_unique($matches[2]), [$author->id]);
        }

        if ($hasMentionAll) {
            // Get all verified residents (except author)
            $mentionedUsers = User::where('id', '!=', $author->id)
                ->verifiedResidents()
                ->get();
        } elseif (!empty($userIds)) {
            // Find specific users who are verified residents
            $mentionedUsers = User::whereIn('id', $userIds)
                ->verifiedResidents()
                ->get();
        } else {
            return;
        }

        foreach ($mentionedUsers as $user) {
            if ($source instanceof Post) {
                $title = $hasMentionAll ? 'Community Alert' : 'New Mention in Post';
                $msg = $hasMentionAll ? "{$author->name} mentioned everyone in a post." : "{$author->name} mentioned you in a post.";
                $user->notify(new GeneralNotification(
                    $title,
                    $msg,
                    "/dashboard/feed?post={$source->id}",
                    [
                        'post_id' => $source->id,
                        'type' => $hasMentionAll ? 'post_mention_all' : 'post_mention'
                    ]
                ));
            } elseif ($source instanceof Comment) {
                $title = $hasMentionAll ? 'Community Alert' : 'New Mention in Comment';
                $msg = $hasMentionAll ? "{$author->name} mentioned everyone in a comment." : "{$author->name} mentioned you in a comment on a post.";
                $user->notify(new GeneralNotification(
                    $title,
                    $msg,
                    "/dashboard/feed?post={$source->post_id}&comment={$source->id}",
                    [
                        'post_id' => $source->post_id,
                        'comment_id' => $source->id,
                        'type' => $hasMentionAll ? 'comment_mention_all' : 'comment_mention'
                    ]
                ));
            }
        }
    }
}
