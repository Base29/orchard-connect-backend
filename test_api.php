<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;

// 1. Get or create a verified user
$user = User::first();
if (!$user) {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
}

// Ensure the user has a verified resident profile
if (!$user->residentProfile) {
    $user->residentProfile()->create([
        'phase' => 'Phase 1',
        'block' => 'Block A',
        'house_number' => '123',
        'user_type' => 'owner',
        'document_path' => 'demo.pdf',
        'status' => 'approved',
        'is_verified' => true,
    ]);
} else {
    $user->residentProfile->update([
        'status' => 'approved',
        'is_verified' => true,
    ]);
}

// 2. Create a post
$post = Post::create([
    'user_id' => $user->id,
    'content' => 'Test Post Content',
    'status' => 'published',
]);

echo "Created post: {$post->id}\n";

// 3. Simulate calling the API endpoint directly by dispatching a request through the router
$request = Request::create("/api/posts/{$post->id}/comments", 'POST', [
    'content' => 'Test Comment Content',
]);

// Authenticate the user for the request
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Run the route
$response = app()->handle($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Content: " . $response->getContent() . "\n";

// 4. Check comments in DB
$comments = Comment::where('post_id', $post->id)->get();
echo "Comments in DB: " . $comments->count() . "\n";
foreach ($comments as $c) {
    echo " - ID: {$c->id}, Content: {$c->content}\n";
}

// Clean up
Comment::where('post_id', $post->id)->delete();
$post->delete();
