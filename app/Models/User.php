<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'author_id', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_WRITER = 'writer';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function normalizedRole(): string
    {
        $role = strtolower(trim((string) ($this->role ?? '')));
        if (in_array($role, [self::ROLE_WRITER, 'editor', 'author'], true)) {
            return self::ROLE_WRITER;
        }

        return self::ROLE_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->normalizedRole() === self::ROLE_ADMIN;
    }

    public function isWriter(): bool
    {
        return $this->normalizedRole() === self::ROLE_WRITER;
    }

    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() || $this->isWriter();
    }

    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password;
    }

    public function ownsPost(Post $post): bool
    {
        return (int) $post->user_id === (int) $this->id;
    }

    public function roleLabel(): string
    {
        return $this->isAdmin() ? 'مدیر' : 'نویسنده';
    }

    public function ensureAuthor(): Author
    {
        if ($this->author_id) {
            $existing = Author::query()->find($this->author_id);
            if ($existing) {
                return $existing;
            }
        }

        $base = Str::slug($this->name, '-', null) ?: 'user-'.$this->id;
        $slug = $base;
        $n = 2;
        while (Author::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }
        $username = Str::before((string) $this->email, '@') ?: $slug;
        $author = Author::query()->create([
            'name' => $this->name,
            'username' => $username,
            'slug' => $slug,
        ]);
        $this->forceFill(['author_id' => $author->id])->save();

        return $author;
    }
}
