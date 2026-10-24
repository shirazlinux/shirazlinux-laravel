<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommentReplyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'name' => 'مدیر سایت']);
    }

    private function writer(): User
    {
        return User::factory()->writer()->create();
    }

    private function publishedPost(): Post
    {
        return Post::query()->create([
            'title' => 'مطلب نظر',
            'slug' => 'comment-post',
            'type' => 'post',
            'status' => 'published',
            'body' => '<p>متن</p>',
        ]);
    }

    public function test_admin_can_reply_and_reply_is_published(): void
    {
        $admin = $this->admin();
        $post = $this->publishedPost();
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'author_name' => 'میهمان',
            'body' => 'سوالی داشتم',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.comments.reply', $comment), [
                'body' => 'سلام. پاسخ شما این است.',
            ])
            ->assertRedirect();

        $reply = Comment::query()->where('parent_id', $comment->id)->first();
        $this->assertNotNull($reply);
        $this->assertTrue($reply->isStaff());
        $this->assertSame('approved', $reply->status);
        $this->assertSame($admin->id, $reply->user_id);
        $this->assertSame('سلام. پاسخ شما این است.', $reply->body);
        $this->assertSame($post->id, $reply->post_id);

        $html = $this->get('/'.$post->slug)->assertOk()->getContent();
        $this->assertStringContainsString('سلام. پاسخ شما این است.', $html);
        $this->assertStringContainsString('پاسخ تیم', $html);
    }

    public function test_admin_reply_approves_pending_parent(): void
    {
        $admin = $this->admin();
        $post = $this->publishedPost();
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'author_name' => 'میهمان',
            'body' => 'در انتظار',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.comments.reply', $comment), [
                'body' => 'منتشر شد.',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $comment->fresh()->status);
        $this->assertSame(1, Comment::query()->where('is_staff', true)->count());
    }

    public function test_writer_cannot_reply_to_comments(): void
    {
        $post = $this->publishedPost();
        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'author_name' => 'میهمان',
            'body' => 'سوال',
            'status' => 'approved',
        ]);

        $this->actingAs($this->writer())
            ->post(route('admin.comments.reply', $comment), [
                'body' => 'نباید ثبت شود',
            ])
            ->assertForbidden();

        $this->assertSame(0, Comment::query()->where('parent_id', $comment->id)->count());
    }
}
