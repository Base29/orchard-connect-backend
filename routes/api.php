<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider or bootstrap/app.php.
|
*/

Route::get('/maintenance-status', function () {
    return response()->json([
        'is_enabled' => (bool) \App\Models\Setting::getValue('maintenance_mode', false),
        'reverb_key' => env('REVERB_APP_KEY')
    ]);
});

// Public OAuth & Traditional Auth Routes
Route::middleware(\App\Http\Middleware\CheckMaintenanceMode::class)->prefix('auth')->group(function () {
    Route::get('{provider}/redirect', [OAuthController::class, 'redirectToProvider']);
    Route::get('{provider}/callback', [OAuthController::class, 'handleProviderCallback']);
    
    // Traditional email/password credentials signup
    Route::post('/register', function (Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        $token = $user->createToken('community_auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'profile_complete' => false,
        ], 201);
    });

    // Traditional email/password credentials login
    Route::post('/login', function (Request $request) {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password credentials.'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your resident account is suspended.'
            ], 403);
        }

        $token = $user->createToken('community_auth_token')->plainTextToken;
        $profileComplete = $user->residentProfile()->exists();

        return response()->json([
            'token' => $token,
            'user' => $user,
            'profile_complete' => $profileComplete,
        ]);
    });
});

// Authenticated Resident Routes
Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckMaintenanceMode::class])->group(function () {
    
    // User Session Profile Context
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load(['residentProfile', 'roles']);
        
        $rejectionsCount = 0;
        $isLocked = false;
        
        if ($user->residentProfile) {
            $lastApproval = \App\Models\ModerationLog::where('action', 'verify_resident')
                ->where('target_type', \App\Models\User::class)
                ->where('target_id', $user->id)
                ->latest()
                ->first();

            $query = \App\Models\ModerationLog::where('action', 'reject_resident')
                ->where('target_type', \App\Models\User::class)
                ->where('target_id', $user->id)
                ->where('created_at', '>=', now()->subHours(48));

            if ($lastApproval) {
                $query->where('created_at', '>', $lastApproval->created_at);
            }

            $rejectionsCount = $query->count();
            $isLocked = $rejectionsCount >= 3;
        }

        return response()->json([
            'user' => $user,
            'rejections_count' => $rejectionsCount,
            'is_locked' => $isLocked,
        ]);
    });
    
    // Resident stats for dashboard
    Route::get('/user/stats', function (Request $request) {
        $user = $request->user();
        
        $postsCount = \App\Models\Post::where('user_id', $user->id)
            ->where('status', 'published')
            ->count();
            
        $postLikesCount = \App\Models\Like::where('likeable_type', \App\Models\Post::class)
            ->whereIn('likeable_id', function ($query) use ($user) {
                $query->select('id')->from('posts')->where('user_id', $user->id);
            })->count();

        $postCommentsCount = \App\Models\Comment::whereIn('post_id', function ($query) use ($user) {
            $query->select('id')->from('posts')->where('user_id', $user->id);
        })->count();

        $pollsCount = \App\Models\Poll::where('user_id', $user->id)->count();
        
        $pollVotesCount = \App\Models\PollVote::whereIn('poll_id', function ($query) use ($user) {
            $query->select('id')->from('polls')->where('user_id', $user->id);
        })->count();

        $adsCount = \App\Models\Listing::where('user_id', $user->id)->count();
        $activeAdsCount = \App\Models\Listing::where('user_id', $user->id)->where('status', 'active')->count();
        $soldAdsCount = \App\Models\Listing::where('user_id', $user->id)->where('status', 'sold')->count();

        return response()->json([
            'posts' => [
                'count' => $postsCount,
                'likes' => $postLikesCount,
                'comments' => $postCommentsCount,
            ],
            'polls' => [
                'count' => $pollsCount,
                'votes' => $pollVotesCount,
            ],
            'ads' => [
                'count' => $adsCount,
                'active' => $activeAdsCount,
                'sold' => $soldAdsCount,
            ]
        ]);
    });

    // Complete Resident Profile Setup
    Route::post('/resident/profile', function (Request $request) {
        $user = $request->user();

        // 1. Lock check: 3 consecutive rejections in rolling 48 hours
        if ($user->residentProfile) {
            $lastApproval = \App\Models\ModerationLog::where('action', 'verify_resident')
                ->where('target_type', \App\Models\User::class)
                ->where('target_id', $user->id)
                ->latest()
                ->first();

            $query = \App\Models\ModerationLog::where('action', 'reject_resident')
                ->where('target_type', \App\Models\User::class)
                ->where('target_id', $user->id)
                ->where('created_at', '>=', now()->subHours(48));

            if ($lastApproval) {
                $query->where('created_at', '>', $lastApproval->created_at);
            }

            if ($query->count() >= 3) {
                return response()->json([
                    'message' => 'Your account is locked due to too many failed verification attempts. Please visit the society office for physical verification.'
                ], 429);
            }
        }

        // 2. Validate request parameters
        $validated = $request->validate([
            'phase' => 'required|string|in:Phase 1,Phase 2,Phase 3,Phase 4',
            'block' => 'required|string|max:50',
            'house_number' => 'required|string|max:100',
            'street_number' => 'nullable|string|max:100',
            'user_type' => 'required|string|in:owner,tenant',
            'document' => 'required|file|mimes:pdf,png,jpg,jpeg|max:10240',
        ]);

        // 3. Upload verification document
        $storage = app(\App\Services\S3PrivateStorageService::class);
        $file = $request->file('document');
        $fileName = 'bill_' . time() . '.' . $file->getClientOriginalExtension();
        $targetPath = "documents/{$user->id}/{$fileName}";
        $documentPath = $storage->upload($file, $targetPath);

        // 4. Create or update profile
        $profile = $user->residentProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phase' => $validated['phase'],
                'block' => $validated['block'],
                'house_number' => $validated['house_number'],
                'street_number' => $validated['street_number'] ?? null,
                'user_type' => $validated['user_type'],
                'document_path' => $documentPath,
                'status' => 'pending',
                'is_verified' => false,
                'rejection_reason' => null,
                'rejection_message' => null,
            ]
        );

        return response()->json([
            'message' => 'Profile updated successfully. Awaiting administration review.',
            'profile' => $profile
        ]);
    });

    // Media Upload API for Posts and Marketplace listings
    Route::post('/media/upload', [\App\Http\Controllers\MediaUploadController::class, 'upload']);

    // Private WebSockets authorization endpoint secured via Sanctum
    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });

    // Social Timeline Feed
    Route::prefix('posts')->group(function () {
        Route::get('/', function (Request $request) {
            // Retrieve recent timeline posts with user profiles, likes, and flags scoped to current user
            $posts = \App\Models\Post::with([
                'user.residentProfile', 
                'user.roles',
                'likes',
                'flags' => function ($q) use ($request) {
                    $q->where('user_id', $request->user()->id);
                }
            ])
                ->withCount(['comments', 'likes'])
                ->where('status', 'published')
                ->latest()
                ->paginate(15);

            return response()->json($posts);
        });

        Route::post('/', function (Request $request) {
            // Verification guard
            if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
                return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
            }

            $validated = $request->validate([
                'content' => 'required|string',
                'media_urls' => 'nullable|array|max:3',
                'media_urls.*' => 'required|string|url',
            ]);

            $post = $request->user()->posts()->create([
                'content' => $validated['content'],
                'media_urls' => $validated['media_urls'] ?? [],
                'status' => 'published',
            ]);

            return response()->json($post->load('user.residentProfile'), 201);
        });

        // Toggle Post Likes
        Route::post('/{post}/like', function (Request $request, \App\Models\Post $post) {
            // Verification guard
            if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
                return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
            }

            $existingLike = $post->likes()->where('user_id', $request->user()->id)->first();

            if ($existingLike) {
                $existingLike->delete();
                $liked = false;
            } else {
                $post->likes()->create([
                    'user_id' => $request->user()->id
                ]);
                $liked = true;
            }

            $likesCount = $post->likes()->count();

            try {
                broadcast(new \App\Events\PostLiked($post->id, $likesCount, $request->user()->id, $liked))->toOthers();
            } catch (\Exception $e) {
                logger()->error('Broadcasting PostLiked event failed: ' . $e->getMessage());
            }

            return response()->json([
                'liked' => $liked,
                'likes_count' => $likesCount
            ]);
        });

        // Threaded Comments
        Route::post('/{post}/comments', function (Request $request, \App\Models\Post $post) {
            // Verification guard
            if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
                return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
            }

            $validated = $request->validate([
                'content' => 'required|string',
                'parent_id' => 'nullable|uuid|exists:comments,id',
            ]);

            $comment = $post->comments()->create([
                'user_id' => $request->user()->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'content' => $validated['content'],
            ]);

            $loadedComment = $comment->load(['user.residentProfile', 'user.roles']);

            try {
                broadcast(new \App\Events\CommentCreated($loadedComment))->toOthers();
            } catch (\Exception $e) {
                logger()->error('Broadcasting CommentCreated event failed: ' . $e->getMessage());
            }

            return response()->json($loadedComment, 201);
        });

        // Get comments for a post
        Route::get('/{post}/comments', function (\App\Models\Post $post) {
            $comments = $post->comments()
                ->with(['user.residentProfile', 'user.roles'])
                ->oldest()
                ->get();

            return response()->json($comments);
        });

        // Flag Post
        Route::post('/{post}/flag', function (Request $request, \App\Models\Post $post) {
            // Verification guard
            if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
                return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
            }

            // Prevent users from flagging their own posts
            if ($post->user_id === $request->user()->id) {
                return response()->json(['message' => 'You cannot flag your own post.'], 400);
            }

            // Prevent duplicate flagging
            $existingFlag = \App\Models\PostFlag::where('post_id', $post->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existingFlag) {
                return response()->json(['message' => 'You have already flagged this post.'], 400);
            }

            // Validate input
            $validated = $request->validate([
                'reason' => 'required|string|in:spam,harassment,hate_speech,inappropriate,other',
                'comment' => 'nullable|string|max:1000',
            ]);

            // Store flag and update post status
            \Illuminate\Support\Facades\DB::transaction(function () use ($post, $request, $validated) {
                \App\Models\PostFlag::create([
                    'post_id' => $post->id,
                    'user_id' => $request->user()->id,
                    'reason' => $validated['reason'],
                    'comment' => $validated['comment'] ?? null,
                ]);

                $post->increment('flags_count');

                // If threshold of 5 flags is met, auto-moderate the post status to 'flagged' to hide it from feed
                if ($post->flags_count >= 5) {
                    $post->status = 'flagged';
                    $post->save();

                    // Log auto-moderation action
                    \App\Models\ModerationLog::create([
                        'action' => 'auto_flag_post',
                        'target_type' => get_class($post),
                        'target_id' => $post->id,
                        'moderator_id' => null, // system action
                        'reason' => 'Post automatically flagged due to reaching the community report threshold of 5 flags.',
                        'metadata' => json_encode([
                            'flags_count' => $post->flags_count,
                            'previous_status' => 'published',
                        ]),
                    ]);
                }
            });

            return response()->json([
                'message' => 'Post flagged successfully.',
                'flags_count' => $post->flags_count,
                'status' => $post->status,
            ]);
        });
    });

    Route::prefix('listings')->group(function () {
        Route::get('/', function (Request $request) {
            $query = \App\Models\Listing::with([
                'user.residentProfile',
                'user.roles',
                'flags' => function ($q) use ($request) {
                    $q->where('user_id', $request->user()->id);
                }
            ]);
            
            // Allow checking specific user's listings (e.g. for my listings tab) or filtering by active
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            } else {
                $query->where('status', 'active');
            }

            // Category filter
            if ($request->filled('category') && strtolower($request->category) !== 'all') {
                $query->where('category', $request->category);
            }

            // Search filter
            if ($request->filled('search')) {
                $search = '%' . $request->search . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', $search)
                      ->orWhere('description', 'like', $search);
                });
            }

            $listings = $query->latest()->paginate(12);
            return response()->json($listings);
        });

        Route::post('/', function (Request $request) {
            // Verification guard
            if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
                return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'category' => 'required|string|max:100',
                'images' => 'nullable|array|max:3',
                'contact_whatsapp' => 'required|string|max:50',
            ]);

            $listing = $request->user()->listings()->create(
                array_merge($validated, ['status' => 'pending'])
            );

            return response()->json($listing, 201);
        });

        // Get single listing details
        Route::get('/{listing}', function (Request $request, \App\Models\Listing $listing) {
            return response()->json($listing->load([
                'user.residentProfile',
                'user.roles',
                'flags' => function ($q) use ($request) {
                    $q->where('user_id', $request->user()->id);
                }
            ]));
        });

        // Delete listing
        Route::delete('/{listing}', function (Request $request, \App\Models\Listing $listing) {
            $user = $request->user();

            if ($listing->user_id !== $user->id && !$user->can('archive-listings')) {
                return response()->json(['message' => 'Unauthorized. You do not own this listing.'], 403);
            }

            if ($listing->user_id !== $user->id) {
                \App\Models\ModerationLog::create([
                    'action' => 'delete_listing',
                    'target_type' => get_class($listing),
                    'target_id' => $listing->id,
                    'moderator_id' => $user->id,
                    'reason' => 'Classified listing deleted/archived by moderator/admin.',
                    'metadata' => json_encode([
                        'title' => $listing->title,
                        'seller_id' => $listing->user_id,
                    ]),
                ]);
            }

            $listing->delete();
            return response()->json(['message' => 'Listing deleted successfully.']);
        });

        // Change listing status (e.g. mark as sold)
        Route::patch('/{listing}/status', function (Request $request, \App\Models\Listing $listing) {
            $user = $request->user();

            if ($listing->user_id !== $user->id && !$user->can('review-listings')) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            $validated = $request->validate([
                'status' => 'required|string|in:active,sold,flagged,suspended',
            ]);

            $oldStatus = $listing->status;
            $listing->update(['status' => $validated['status']]);

            if ($listing->user_id !== $user->id) {
                \App\Models\ModerationLog::create([
                    'action' => 'update_listing_status',
                    'target_type' => get_class($listing),
                    'target_id' => $listing->id,
                    'moderator_id' => $user->id,
                    'reason' => 'Listing status updated to ' . $validated['status'] . ' by moderator/admin.',
                    'metadata' => json_encode([
                        'previous_status' => $oldStatus,
                        'new_status' => $validated['status'],
                    ]),
                ]);
            }

            return response()->json($listing);
        });

        // Get listing comments
        Route::get('/{listing}/comments', function (\App\Models\Listing $listing) {
            $comments = $listing->comments()
                ->with(['user.residentProfile', 'user.roles'])
                ->oldest()
                ->get();
            return response()->json($comments);
        });

        // Post listing comment
        Route::post('/{listing}/comments', function (Request $request, \App\Models\Listing $listing) {
            // Verification guard
            if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
                return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
            }

            $validated = $request->validate([
                'content' => 'required|string',
                'parent_id' => 'nullable|uuid|exists:comments,id',
            ]);

            $comment = $listing->comments()->create([
                'user_id' => $request->user()->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'content' => $validated['content'],
            ]);

            return response()->json($comment->load(['user.residentProfile', 'user.roles']), 201);
        });

        // Flag Listing
        Route::post('/{listing}/flag', function (Request $request, \App\Models\Listing $listing) {
            // Verification guard
            if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
                return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
            }

            // Prevent users from flagging their own listings
            if ($listing->user_id === $request->user()->id) {
                return response()->json(['message' => 'You cannot flag your own listing.'], 400);
            }

            // Prevent duplicate flagging
            $existingFlag = \App\Models\ListingFlag::where('listing_id', $listing->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existingFlag) {
                return response()->json(['message' => 'You have already flagged this listing.'], 400);
            }

            // Validate input
            $validated = $request->validate([
                'reason' => 'required|string|in:spam,harassment,hate_speech,inappropriate,other',
                'comment' => 'nullable|string|max:1000',
            ]);

            // Store flag and update listing status
            \Illuminate\Support\Facades\DB::transaction(function () use ($listing, $request, $validated) {
                \App\Models\ListingFlag::create([
                    'listing_id' => $listing->id,
                    'user_id' => $request->user()->id,
                    'reason' => $validated['reason'],
                    'comment' => $validated['comment'] ?? null,
                ]);

                $listing->increment('flags_count');

                // If threshold of 5 flags is met, auto-moderate the listing status to 'flagged' to hide it from feed
                if ($listing->flags_count >= 5) {
                    $listing->status = 'flagged';
                    $listing->save();

                    // Log auto-moderation action
                    \App\Models\ModerationLog::create([
                        'action' => 'auto_flag_listing',
                        'target_type' => get_class($listing),
                        'target_id' => $listing->id,
                        'moderator_id' => null, // system action
                        'reason' => 'Listing automatically flagged due to reaching the community report threshold of 5 flags.',
                        'metadata' => json_encode([
                            'flags_count' => $listing->flags_count,
                            'previous_status' => 'active',
                        ]),
                    ]);
                }
            });

            return response()->json([
                'message' => 'Listing flagged successfully.',
                'flags_count' => $listing->flags_count,
                'status' => $listing->status,
            ]);
        });
    });

    // Local Directory
    Route::get('/directory', function () {
        $isVerified = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'true' : true;
        $categories = \App\Models\DirectoryCategory::with(['listings' => function ($query) use ($isVerified) {
            $query->where('is_verified', $isVerified)
                ->withCount('reviews')
                ->withAvg('reviews', 'rating');
        }])->get();

        return response()->json($categories);
    });

    // Get single directory listing details
    Route::get('/directory/{listing}', function (\App\Models\DirectoryListing $listing) {
        if (!$listing->is_verified) {
            return response()->json(['message' => 'Business listing not found or not verified.'], 404);
        }
        
        $listing->loadCount('reviews')->loadAvg('reviews', 'rating');
        $listing->load(['category', 'reviews' => function ($query) {
            $query->with(['user.residentProfile', 'user.roles'])->latest();
        }]);

        return response()->json($listing);
    });

    // Post / update a review for a listing
    Route::post('/directory/{listing}/reviews', function (Request $request, \App\Models\DirectoryListing $listing) {
        // Verification guard
        if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
            return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = $listing->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return response()->json($review->load(['user.residentProfile', 'user.roles']), 201);
    });

    // Delete review
    Route::delete('/directory/reviews/{review}', function (Request $request, \App\Models\DirectoryReview $review) {
        $user = $request->user();

        if ($review->user_id !== $user->id && !$user->can('review-listings')) {
            return response()->json(['message' => 'Unauthorized. You do not own this review.'], 403);
        }

        if ($review->user_id !== $user->id) {
            \App\Models\ModerationLog::create([
                'action' => 'delete_directory_review',
                'target_type' => get_class($review),
                'target_id' => $review->id,
                'moderator_id' => $user->id,
                'reason' => 'Directory review deleted by moderator/admin.',
                'metadata' => json_encode([
                    'review_owner_id' => $review->user_id,
                    'listing_id' => $review->directory_listing_id,
                ]),
            ]);
        }

        $review->delete();
        return response()->json(['message' => 'Review deleted successfully.']);
    });

    // Phone Directory
    Route::get('/phone-directory', function (Request $request) {
        // Verification guard
        if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
            return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
        }

        $contacts = \App\Models\PhoneDirectory::orderBy('category')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return response()->json($contacts);
    });

    // Community Board Announcements
    Route::get('/announcements', function (Request $request) {
        $query = \App\Models\Announcement::with(['author', 'author.roles'])
            ->where('status', 'published')
            ->orderBy('pinned', 'desc')
            ->latest();

        if ($request->boolean('paginate')) {
            return response()->json($query->paginate(15));
        }

        return response()->json($query->take(10)->get());
    });

    Route::get('/announcements/{announcement}', function (\App\Models\Announcement $announcement) {
        if ($announcement->status !== 'published') {
            return response()->json(['message' => 'Announcement not found.'], 404);
        }
        return response()->json($announcement->load(['author', 'author.roles']));
    });

    // Orchard News Articles
    Route::prefix('news')->group(function () {
        Route::get('/', function (Request $request) {
            $news = \App\Models\News::with(['author', 'author.roles'])
                ->withCount('comments')
                ->where('status', 'published')
                ->latest()
                ->paginate(15);

            return response()->json($news);
        });

        Route::get('/{news}', function (Request $request, \App\Models\News $news) {
            if ($news->status !== 'published') {
                return response()->json(['message' => 'News article not found.'], 404);
            }
            return response()->json($news->load(['author', 'author.roles']));
        });

        Route::get('/{news}/comments', function (Request $request, \App\Models\News $news) {
            if ($news->status !== 'published') {
                return response()->json(['message' => 'News article not found.'], 404);
            }

            $comments = $news->comments()
                ->with(['user.residentProfile', 'user.roles'])
                ->oldest()
                ->get();

            return response()->json($comments);
        });

        Route::post('/{news}/comments', function (Request $request, \App\Models\News $news) {
            if ($news->status !== 'published') {
                return response()->json(['message' => 'News article not found.'], 404);
            }

            // Verification guard
            if (!$request->user()->residentProfile || !$request->user()->residentProfile->is_verified) {
                return response()->json(['message' => 'Action locked. Residency verification required.'], 403);
            }

            $validated = $request->validate([
                'content' => 'required|string',
                'parent_id' => 'nullable|uuid|exists:comments,id',
            ]);

            $comment = $news->comments()->create([
                'user_id' => $request->user()->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'content' => $validated['content'],
            ]);

            return response()->json($comment->load(['user.residentProfile', 'user.roles']), 201);
        });
    });

    // Resident Polls
    Route::prefix('polls')->group(function () {
        Route::get('/', [\App\Http\Controllers\PollController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\PollController::class, 'store']);
        Route::put('/{poll}', [\App\Http\Controllers\PollController::class, 'update']);
        Route::post('/{poll}/vote', [\App\Http\Controllers\PollController::class, 'vote']);
        Route::post('/{poll}/suspend', [\App\Http\Controllers\PollController::class, 'suspend']);
    });
});
