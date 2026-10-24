<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $fillable = [
        'publii_id', 'author_id', 'user_id', 'title', 'slug', 'excerpt', 'body',
        'featured_image', 'meta_title', 'meta_description', 'type', 'status',
        'featured', 'exclude_homepage', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'exclude_homepage' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy(Builder $q, User $user): Builder
    {
        return $q->where('user_id', $user->id);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function crewMembers(): BelongsToMany
    {
        return $this->belongsToMany(CrewMember::class, 'post_crew')
            ->withPivot('sort')
            ->orderByPivot('sort')
            ->orderBy('crew_members.sort');
    }

    public function eventStaff(): HasMany
    {
        return $this->hasMany(EventStaff::class)->orderBy('sort')->orderBy('id');
    }

    public function contributors(): HasMany
    {
        return $this->hasMany(PostContributor::class)->orderBy('sort')->orderBy('id');
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderBy('created_at')
            ->with(['approvedChildren']);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    public function scopePostsOnly(Builder $q): Builder
    {
        return $q->where('type', 'post');
    }

    public function scopePagesOnly(Builder $q): Builder
    {
        return $q->where('type', 'page');
    }

    public function isPage(): bool
    {
        return $this->type === 'page';
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class)->orderByDesc('id');
    }
}
