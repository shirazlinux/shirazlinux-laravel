<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_writer_must_change_password_before_posts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'نگار',
                'email' => 'negar@example.com',
                'password' => 'secret1234',
                'role' => 'writer',
            ])
            ->assertRedirect();

        $writer = User::query()->where('email', 'negar@example.com')->first();
        $this->assertTrue($writer->mustChangePassword());

        $this->actingAs($writer)
            ->get(route('admin.posts.index'))
            ->assertRedirect(route('admin.password.edit'));

        $this->actingAs($writer)
            ->get(route('admin.password.edit'))
            ->assertOk()
            ->assertSee('رمز را عوض کنید');
    }

    public function test_writer_starts_work_after_password_change(): void
    {
        $writer = User::factory()->writer()->create([
            'password' => 'secret1234',
            'must_change_password' => true,
        ]);

        $this->actingAs($writer)
            ->post(route('admin.password.update'), [
                'current_password' => 'secret1234',
                'password' => 'newpass1234',
                'password_confirmation' => 'newpass1234',
            ])
            ->assertRedirect(route('admin.posts.index', ['type' => 'post']));

        $this->assertFalse($writer->fresh()->mustChangePassword());
        $this->actingAs($writer->fresh())
            ->get(route('admin.posts.index'))
            ->assertOk();
    }

    public function test_login_sends_forced_user_to_password_page(): void
    {
        $writer = User::factory()->writer()->create([
            'email' => 'force@example.com',
            'password' => 'secret1234',
            'must_change_password' => true,
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'force@example.com',
            'password' => 'secret1234',
        ])->assertRedirect(route('admin.password.edit'));
    }
}
