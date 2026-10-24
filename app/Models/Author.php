<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Author extends Model
{
    protected $fillable = [
        'publii_id', 'name', 'username', 'slug', 'bio', 'avatar',
        'website', 'telegram', 'mastodon', 'github',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(PostContributor::class);
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return asset(ltrim($this->avatar, '/'));
    }
}
