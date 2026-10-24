<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent content fix for the /fediverse page:
 *  - create tag "fediverse" and attach the /mastodon article
 *  - unify Mastodon spelling to «ماستادون» in post text fields and tag names
 *
 * Production (no SSH): back up database.sqlite first, then run with --dry-run,
 * review the list, then run without it (e.g. via a one-off PHP that calls Artisan).
 */
class FediverseContentCommand extends Command
{
    protected $signature = 'shirazlinux:fediverse-content {--dry-run : Only list what would change}';

    protected $description = 'Create the fediverse tag, attach /mastodon, and unify Mastodon spelling in DB content';

    /** Old spellings => new spelling */
    private const SPELLINGS = [
        'ماس‌تودون' => 'ماستادون',
        'ماس تودون' => 'ماستادون',
        'ماستودون' => 'ماستادون',
        'موستودون' => 'ماستادون',
    ];

    private const POST_FIELDS = ['title', 'body', 'excerpt', 'meta_title', 'meta_description'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info($dry ? 'DRY RUN, nothing is written.' : 'Applying changes.');

        DB::transaction(function () use ($dry) {
            // 1) tag + attach
            $tag = Tag::query()->where('slug', 'fediverse')->first();
            if (! $tag) {
                $this->line('+ create tag fediverse (فدیورس)');
                if (! $dry) {
                    $tag = Tag::query()->create([
                        'name' => 'فدیورس',
                        'slug' => 'fediverse',
                        'description' => 'شبکه اجتماعی آزاد و غیرمتمرکز: ماستادون، پیکسل‌فد، پیرتیوب، لمی و دیگر نرم‌افزارهای فدیورس.',
                    ]);
                }
            } else {
                $this->line('= tag fediverse exists (id '.$tag->id.')');
            }

            $mastodon = Post::query()->where('slug', 'mastodon')->first();
            if (! $mastodon) {
                $this->warn('! post "mastodon" not found, nothing attached');
            } elseif ($tag && $mastodon->tags()->where('tags.id', $tag->id)->exists()) {
                $this->line('= /mastodon already tagged fediverse');
            } else {
                $this->line('+ attach tag fediverse to /mastodon (post id '.$mastodon->id.')');
                if (! $dry && $tag) {
                    $mastodon->tags()->syncWithoutDetaching([$tag->id]);
                }
            }

            // 2) spelling in posts
            $query = Post::query();
            $query->where(function ($q) {
                foreach (self::POST_FIELDS as $field) {
                    foreach (array_keys(self::SPELLINGS) as $old) {
                        $q->orWhere($field, 'like', '%'.$old.'%');
                    }
                }
            });
            foreach ($query->get() as $post) {
                $dirty = [];
                foreach (self::POST_FIELDS as $field) {
                    $value = $post->{$field};
                    if (! is_string($value) || $value === '') {
                        continue;
                    }
                    $new = strtr($value, self::SPELLINGS);
                    if ($new !== $value) {
                        $count = 0;
                        foreach (array_keys(self::SPELLINGS) as $old) {
                            $count += mb_substr_count($value, $old);
                        }
                        $dirty[$field] = $new;
                        $this->line(sprintf('~ post %d /%s field %s: %d replacement(s)', $post->id, $post->slug, $field, $count));
                    }
                }
                if ($dirty && ! $dry) {
                    // query builder: keep updated_at untouched for a pure spelling fix
                    DB::table('posts')->where('id', $post->id)->update($dirty);
                }
            }

            // 3) spelling in tag names / descriptions
            foreach (Tag::query()->get() as $t) {
                $dirty = [];
                foreach (['name', 'description'] as $field) {
                    $value = $t->{$field};
                    if (is_string($value) && ($new = strtr($value, self::SPELLINGS)) !== $value) {
                        $dirty[$field] = $new;
                        $this->line(sprintf('~ tag %d (%s) %s: "%s" => "%s"', $t->id, $t->slug, $field, $value, $new));
                    }
                }
                if ($dirty && ! $dry) {
                    DB::table('tags')->where('id', $t->id)->update($dirty);
                }
            }
        });

        if (! $dry) {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            $this->info('Done. Cache and compiled views cleared.');
        }

        return self::SUCCESS;
    }
}
