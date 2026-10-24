<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WriterPostAccessTest extends TestCase
{
    use RefreshDatabase;

    private function writer(array $attrs = []): User
    {
        $user = User::factory()->writer()->create($attrs);
        $user->ensureAuthor();

        return $user->fresh();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_writer_user(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'نگار احمدی',
                'email' => 'negar@example.com',
                'password' => 'secret1234',
                'role' => 'writer',
            ])
            ->assertRedirect();

        $writer = User::query()->where('email', 'negar@example.com')->first();
        $this->assertNotNull($writer);
        $this->assertTrue($writer->isWriter());
        $this->assertNotNull($writer->author_id);
        $this->assertSame('نگار احمدی', $writer->author->name);
    }

    public function test_writer_can_create_and_publish_own_post(): void
    {
        $writer = $this->writer(['name' => 'سارا']);
        $this->actingAs($writer)
            ->post(route('admin.posts.store'), [
                'title' => 'اولین مطلب من',
                'slug' => 'my-first-post',
                'type' => 'post',
                'status' => 'published',
                'excerpt' => 'خلاصه',
                'body' => '<p>متن</p>',
                'author_id' => 9999,
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'my-first-post')->first();
        $this->assertNotNull($post);
        $this->assertSame($writer->id, $post->user_id);
        $this->assertSame($writer->author_id, $post->author_id);
        $this->assertSame('published', $post->status);
        $this->assertSame('post', $post->type);
        $this->assertFalse((bool) $post->featured);
    }

    public function test_writer_cannot_edit_or_delete_someone_elses_post(): void
    {
        $owner = $this->writer(['email' => 'owner@example.com']);
        $other = $this->writer(['email' => 'other@example.com']);
        $post = Post::query()->create([
            'title' => 'مطلب دیگران',
            'slug' => 'someone-else',
            'type' => 'post',
            'status' => 'published',
            'user_id' => $owner->id,
            'author_id' => $owner->author_id,
            'body' => '<p>x</p>',
        ]);

        $this->actingAs($other)->get(route('admin.posts.edit', $post))->assertForbidden();
        $this->actingAs($other)->put(route('admin.posts.update', $post), [
            'title' => 'هک',
            'slug' => 'someone-else',
            'type' => 'post',
            'status' => 'draft',
        ])->assertForbidden();
        $this->actingAs($other)->delete(route('admin.posts.destroy', $post))->assertForbidden();
        $this->assertSame('مطلب دیگران', $post->fresh()->title);
    }

    public function test_writer_list_only_shows_own_posts(): void
    {
        $mine = $this->writer(['email' => 'mine@example.com']);
        $other = $this->writer(['email' => 'other2@example.com']);
        Post::query()->create([
            'title' => 'مال من',
            'slug' => 'mine-post',
            'type' => 'post',
            'status' => 'draft',
            'user_id' => $mine->id,
            'author_id' => $mine->author_id,
        ]);
        Post::query()->create([
            'title' => 'مال او',
            'slug' => 'their-post',
            'type' => 'post',
            'status' => 'draft',
            'user_id' => $other->id,
            'author_id' => $other->author_id,
        ]);

        $html = $this->actingAs($mine)->get(route('admin.posts.index'))->assertOk()->getContent();
        $this->assertStringContainsString('مال من', $html);
        $this->assertStringNotContainsString('مال او', $html);
    }

    public function test_writer_cannot_open_admin_only_pages(): void
    {
        $writer = $this->writer();
        $this->actingAs($writer)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($writer)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($writer)->get(route('admin.comments.index'))->assertForbidden();
        $this->actingAs($writer)->get(route('admin.media.index'))->assertForbidden();
        $this->actingAs($writer)->get(route('admin.posts.index', ['type' => 'page']))->assertOk();
        $html = $this->actingAs($writer)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('کتابخانه رسانه', $html);
        $this->assertStringNotContainsString('href="'.route('admin.comments.index').'"', $html);
        $this->assertStringContainsString('مطالب', $html);
    }

    public function test_legacy_editor_role_is_treated_as_writer(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $this->assertTrue($editor->isWriter());
        $this->assertFalse($editor->isAdmin());
        $this->actingAs($editor)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.posts.index'))->assertOk();
    }

    public function test_writer_cannot_create_a_page(): void
    {
        $writer = $this->writer();
        $this->actingAs($writer)
            ->post(route('admin.posts.store'), [
                'title' => 'صفحه تقلبی',
                'slug' => 'fake-page',
                'type' => 'page',
                'status' => 'published',
            ])
            ->assertForbidden();
        $this->assertNull(Post::query()->where('slug', 'fake-page')->first());
    }

    public function test_admin_can_edit_writer_post(): void
    {
        $writer = $this->writer();
        $post = Post::query()->create([
            'title' => 'پیش‌نویس سارا',
            'slug' => 'sara-draft',
            'type' => 'post',
            'status' => 'draft',
            'user_id' => $writer->id,
            'author_id' => $writer->author_id,
        ]);
        $this->actingAs($this->admin())
            ->put(route('admin.posts.update', $post), [
                'title' => 'ویرایش مدیر',
                'slug' => 'sara-draft',
                'type' => 'post',
                'status' => 'published',
            ])
            ->assertRedirect();
        $this->assertSame('ویرایش مدیر', $post->fresh()->title);
        $this->assertSame('published', $post->fresh()->status);
    }
}
