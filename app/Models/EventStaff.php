<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventStaff extends Model
{
    protected $table = 'event_staff';

    protected $fillable = [
        'post_id', 'crew_member_id', 'name', 'role', 'photo', 'sort',
    ];

    protected function casts(): array
    {
        return ['sort' => 'integer'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function crewMember(): BelongsTo
    {
        return $this->belongsTo(CrewMember::class);
    }

    public function photoUrl(): ?string
    {
        $photo = $this->photo;
        if (! $photo && $this->relationLoaded('crewMember')) {
            $photo = $this->crewMember?->photo;
        }
        if (! $photo) {
            return null;
        }
        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $photo;
        }

        return asset(ltrim($photo, '/'));
    }

    public function profileUrl(): ?string
    {
        $member = $this->relationLoaded('crewMember') ? $this->crewMember : $this->crewMember()->first();

        return $member?->profileUrl();
    }
}
