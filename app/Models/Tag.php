<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\File;

class Tag extends Model
{
    protected $fillable = [
        'publii_id', 'name', 'slug', 'description',
        'featured_image', 'meta_title', 'meta_description',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Cover image under public/media/tags/{publii_id}/ if present.
     * Prefers larger landscape covers when multiple files exist.
     */
    public function featuredImagePath(): ?string
    {
        if ($this->featured_image) {
            $path = ltrim($this->featured_image, '/');
            if (str_starts_with($path, 'http') || is_file(public_path($path))) {
                return $path;
            }
        }

        // Prefer curated covers for known tags
        $overrides = [
            'post' => 'media/tags/5/post-free-cover.jpg',
        ];
        if (isset($overrides[$this->slug])) {
            $path = $overrides[$this->slug];
            if (is_file(public_path($path))) {
                return $path;
            }
        }

        if (! $this->publii_id) {
            return null;
        }

        $dir = public_path('media/tags/'.$this->publii_id);
        if (! is_dir($dir)) {
            return null;
        }

        $files = collect(File::files($dir))
            ->filter(fn ($f) => preg_match('/\.(jpe?g|png|webp|gif)$/i', $f->getFilename()))
            ->filter(fn ($f) => ! str_ends_with(strtolower($f->getFilename()), '.bak'))
            // Prefer largest file (better for hero)
            ->sortByDesc(fn ($f) => $f->getSize())
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        return 'media/tags/'.$this->publii_id.'/'.$files->first()->getFilename();
    }

    public function isEventLike(): bool
    {
        return in_array($this->slug, [
            'event', 'dorehami', 'workshop', 'conference', 'freesoftwaretalks',
        ], true);
    }

    /** Square designed posters (پست آزاد). */
    public function isPosterLike(): bool
    {
        return $this->slug === 'post';
    }

    /** Guide / community reading lists — prefer large landscape covers. */
    public function isGuideLike(): bool
    {
        return in_array($this->slug, [
            'free-software-community-guide',
            'free-software-community',
            'free-software',
            'libre-learn',
            'librelearn',
        ], true);
    }
}

