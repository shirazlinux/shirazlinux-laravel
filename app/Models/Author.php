<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
