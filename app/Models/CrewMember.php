<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CrewMember extends Model
{
    protected $fillable = [
        'author_id', 'name', 'slug', 'role', 'bio', 'photo',
        'website', 'telegram', 'mastodon', 'sort', 'active', 'former', 'tenure',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'former' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_crew')
            ->withPivot('sort');
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo) {
            return null;
        }
        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        return asset(ltrim($this->photo, '/'));
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort')->orderBy('name');
    }

    public function profileUrl(): string
    {
        return route('crew.show', $this->slug);
    }

    public function eventStaff(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EventStaff::class);
    }
}
