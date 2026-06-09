<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use App\Models\ResidentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrchardNewsImageTest extends TestCase
{
    use RefreshDatabase;

    protected User $verifiedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verifiedUser = User::factory()->create();
        ResidentProfile::create([
            'user_id' => $this->verifiedUser->id,
            'phase' => 'Phase 1',
            'block' => 'Block A',
            'house_number' => '123',
            'user_type' => 'owner',
            'document_path' => 'bill.jpg',
            'status' => 'approved',
            'is_verified' => true,
        ]);
    }

    /**
     * Test news list API returns image_path and image_url.
     */
    public function test_news_list_api_returns_image_details(): void
    {
        News::create([
            'title' => 'Important Society Announcement',
            'content' => 'Please read the following details carefully.',
            'status' => 'published',
            'image_path' => 'news/1/test_image.jpg',
        ]);

        $response = $this->actingAs($this->verifiedUser)->getJson('/api/news');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('news/1/test_image.jpg', $data[0]['image_path']);
        $this->assertNotEmpty($data[0]['image_url']);
        $this->assertStringEndsWith('news/1/test_image.jpg', $data[0]['image_url']);
        $this->assertStringStartsWith('http', $data[0]['image_url']);
    }

    /**
     * Test news detail API returns image_path and image_url.
     */
    public function test_news_detail_api_returns_image_details(): void
    {
        $news = News::create([
            'title' => 'Important Society Announcement',
            'content' => 'Please read the following details carefully.',
            'status' => 'published',
            'image_path' => 'news/1/test_image.jpg',
        ]);

        $response = $this->actingAs($this->verifiedUser)->getJson("/api/news/{$news->id}");

        $response->assertStatus(200)
            ->assertJsonPath('image_path', 'news/1/test_image.jpg');

        $imageUrl = $response->json('image_url');
        $this->assertStringEndsWith('news/1/test_image.jpg', $imageUrl);
        $this->assertStringStartsWith('http', $imageUrl);
    }
}
