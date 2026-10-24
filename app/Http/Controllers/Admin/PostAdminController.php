<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\CrewMember;
use App\Models\EventStaff;
use App\Models\Post;
use App\Models\User;
use App\Support\MediaStorage;
use App\Models\PostRevision;
use App\Models\Tag;
use App\Support\JalaliDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Post::class);
        $user = $this->actor();
        $type = $request->query('type', 'post');
        if ($user->isWriter()) {
            $type = 'post';
        }
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $posts = Post::query()
            ->when($user->isWriter(), fn ($query) => $query->ownedBy($user))
            ->when(in_array($type, ['post', 'page'], true), fn ($query) => $query->where('type', $type))
            ->when($status && in_array($status, ['published', 'draft', 'hidden'], true), fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%");
                });
            })
            ->with(['author', 'tags'])
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.posts.index', compact('posts', 'type', 'q', 'status'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Post::class);
        $user = $this->actor();
        $type = $request->query('type', 'post');
        if ($user->isWriter()) {
            $type = 'post';
            $user->ensureAuthor();
        }

        return view('admin.posts.form', [
            'post' => new Post(['type' => $type, 'status' => 'draft', 'author_id' => $user->author_id]),
            'authors' => Author::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'crewMembers' => CrewMember::query()->orderBy('sort')->orderBy('name')->get(),
            'eventStaff' => collect(),
            'contributors' => [],
            'selectedTags' => [],
            'revisions' => collect(),
            'previewUrl' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Post::class);
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title'], '-', null) ?: uniqid('p-');
        if (empty($data['published_at']) && $data['status'] === 'published') {
            $data['published_at'] = now();
        }
        $post = Post::create($data);
        $post->tags()->sync($request->input('tags', []));
        $this->syncEventStaff($post, $request);
        $this->syncContributors($post, $request);
        $this->snapshot($post);
        ActivityLog::record('post.create', $post, 'ایجاد: '.$post->title);

        return redirect()->route('admin.posts.edit', $post)->with('ok', 'ذخیره شد.');
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);

        return view('admin.posts.form', [
            'post' => $post,
            'authors' => Author::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'crewMembers' => CrewMember::query()->orderBy('sort')->orderBy('name')->get(),
            'eventStaff' => $post->eventStaff,
            'contributors' => $post->contributors()->get(['author_id','role'])->map(fn ($c) => [
                'author_id' => $c->author_id,
                'role' => $c->role,
            ])->all(),
            'selectedTags' => $post->tags()->pluck('tags.id')->all(),
            'revisions' => $post->revisions()->with('user:id,name')->limit(12)->get(),
            'previewUrl' => URL::temporarySignedRoute('preview.show', now()->addDays(7), ['slug' => $post->slug]),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);
        $this->snapshot($post);
        $data = $this->validated($request, $post->id);
        if (empty($data['published_at']) && $data['status'] === 'published' && ! $post->published_at) {
            $data['published_at'] = now();
        }
        $post->update($data);
        $post->tags()->sync($request->input('tags', []));
        $this->syncEventStaff($post, $request);
        $this->syncContributors($post, $request);
        ActivityLog::record('post.update', $post, 'ویرایش: '.$post->title);

        return back()->with('ok', 'به‌روزرسانی شد.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);
        $title = $post->title;
        $type = $post->type;
        $post->tags()->detach();
        $post->crewMembers()->detach();
        $post->eventStaff()->delete();
        $post->delete();
        ActivityLog::record('post.delete', null, 'حذف: '.$title);

        return redirect()->route('admin.posts.index', ['type' => $type])->with('ok', 'حذف شد.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:posts,id',
            'action' => 'required|in:publish,draft,hide,delete',
            'type' => 'nullable|in:post,page',
        ]);

        $user = $this->actor();
        $posts = Post::query()
            ->whereIn('id', $data['ids'])
            ->when($user->isWriter(), fn ($query) => $query->ownedBy($user)->where('type', 'post'))
            ->get();
        if ($posts->isEmpty()) {
            abort(403, 'به این مطالب دسترسی ندارید.');
        }
        foreach ($posts as $post) {
            match ($data['action']) {
                'publish' => $post->update([
                    'status' => 'published',
                    'published_at' => $post->published_at ?: now(),
                ]),
                'draft' => $post->update(['status' => 'draft']),
                'hide' => $post->update(['status' => 'hidden']),
                'delete' => tap($post, function (Post $p) {
                    $p->tags()->detach();
                    $p->crewMembers()->detach();
                    $p->eventStaff()->delete();
                    $p->delete();
                }),
            };
        }
        ActivityLog::record('post.bulk', null, 'عملیات گروهی: '.$data['action'], [
            'ids' => $data['ids'],
            'count' => count($data['ids']),
        ]);

        return back()->with('ok', 'عملیات گروهی انجام شد ('.count($data['ids']).' مورد).');
    }

    private function syncEventStaff(Post $post, Request $request): void
    {
        $rows = $request->input('event_staff', []);
        if (! is_array($rows)) {
            $rows = [];
        }
        $files = $request->file('event_staff', []);
        $keep = [];
        $sort = 0;
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $role = trim((string) ($row['role'] ?? ''));
            $photo = trim((string) ($row['photo'] ?? ''));
            $crewId = (int) ($row['crew_member_id'] ?? 0) ?: null;
            if ($name === '' && $role === '' && $photo === '' && ! $crewId && empty($files[$i]['file'])) {
                continue;
            }
            if ($crewId) {
                $member = CrewMember::query()->find($crewId);
                if ($member && $name === '') {
                    $name = $member->name;
                }
                if ($member && $photo === '') {
                    $photo = (string) $member->photo;
                }
            }
            if ($name === '') {
                continue;
            }
            $member = $this->ensureCrewMember($name, $photo !== '' ? $photo : null, $crewId);
            $crewId = $member->id;
            if ($photo === '' && $member->photo) {
                $photo = (string) $member->photo;
            }
            if (isset($files[$i]['file']) && $files[$i]['file']) {
                $saved = MediaStorage::storeImage($files[$i]['file'], 'event-staff');
                $photo = $saved['path'];
            }
            $payload = [
                'crew_member_id' => $crewId,
                'name' => $name,
                'role' => $role !== '' ? $role : null,
                'photo' => $photo !== '' ? $photo : null,
                'sort' => $sort++,
            ];
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $staff = EventStaff::query()->where('post_id', $post->id)->where('id', $id)->first();
                if ($staff) {
                    $staff->update($payload);
                    $keep[] = $staff->id;
                    continue;
                }
            }
            $created = $post->eventStaff()->create($payload);
            $keep[] = $created->id;
        }
        $post->eventStaff()->whereNotIn('id', $keep ?: [0])->delete();
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private function syncContributors(Post $post, Request $request): void
    {
        $user = $this->actor();
        if ($user->isWriter()) {
            $author = $user->ensureAuthor();
            $post->contributors()->delete();
            \App\Models\PostContributor::query()->create([
                'post_id' => $post->id,
                'author_id' => $author->id,
                'role' => 'writer',
                'sort' => 0,
            ]);

            return;
        }

        $rows = $request->input('contributors', []);
        if (! is_array($rows)) {
            $rows = [];
        }
        $keep = [];
        $sort = 0;
        $seen = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $aid = (int) ($row['author_id'] ?? 0);
            $role = (string) ($row['role'] ?? 'writer');
            if ($aid < 1 || ! in_array($role, ['writer', 'translator', 'editor'], true)) {
                continue;
            }
            $key = $aid.':'.$role;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rec = \App\Models\PostContributor::query()->updateOrCreate(
                ['post_id' => $post->id, 'author_id' => $aid, 'role' => $role],
                ['sort' => $sort++]
            );
            $keep[] = $rec->id;
        }
        $post->contributors()->whereNotIn('id', $keep ?: [0])->delete();
    }

    private function ensureCrewMember(string $name, ?string $photo, ?int $crewId): CrewMember
    {
        if ($crewId) {
            $found = CrewMember::query()->find($crewId);
            if ($found) {
                return $found;
            }
        }
        $found = CrewMember::query()->where('name', $name)->first();
        if ($found) {
            if ($photo && ! $found->photo) {
                $found->update(['photo' => $photo]);
            }

            return $found;
        }
        $slug = Str::slug($name, '-', null) ?: uniqid('crew-');
        $base = $slug;
        $n = 2;
        while (CrewMember::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return CrewMember::query()->create([
            'name' => $name,
            'slug' => $slug,
            'photo' => $photo,
            'active' => false,
            'sort' => 800,
        ]);
    }

    private function snapshot(Post $post): void
    {
        if (! $post->exists) {
            return;
        }
        try {
            PostRevision::query()->create([
                'post_id' => $post->id,
                'user_id' => auth()->id(),
                'title' => $post->title,
                'body' => $post->body,
                'excerpt' => $post->excerpt,
                'status' => $post->status,
            ]);
            // keep last 30
            $ids = PostRevision::query()
                ->where('post_id', $post->id)
                ->orderByDesc('id')
                ->skip(30)
                ->take(100)
                ->pluck('id');
            if ($ids->isNotEmpty()) {
                PostRevision::query()->whereIn('id', $ids)->delete();
            }
        } catch (\Throwable) {
            // table may not exist yet
        }
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $user = $this->actor();
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug,'.($id ?? 'NULL'),
            'type' => 'required|in:post,page',
            'status' => 'required|in:published,draft,hidden',
            'author_id' => $user->isWriter() ? 'nullable' : 'nullable|exists:authors,id',
            'excerpt' => 'nullable|string',
            'body' => 'nullable|string',
            'featured_image' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'featured' => 'sometimes|boolean',
            'exclude_homepage' => 'sometimes|boolean',
            'published_at' => 'nullable|string|max:40',
        ]) + [
            'featured' => $request->boolean('featured'),
            'exclude_homepage' => $request->boolean('exclude_homepage'),
        ];

        if ($user->isWriter()) {
            if (($data['type'] ?? 'post') === 'page') {
                abort(403, 'نویسنده نمی‌تواند صفحه بسازد.');
            }
            $author = $user->ensureAuthor();
            $data['type'] = 'post';
            $data['author_id'] = $author->id;
            $data['featured'] = false;
            if ($id) {
                $existing = Post::query()->find($id);
                $data['user_id'] = $existing?->user_id ?: $user->id;
            } else {
                $data['user_id'] = $user->id;
            }
        } else {
            $data['user_id'] = $id
                ? (Post::query()->find($id)?->user_id ?: $user->id)
                : $user->id;
        }

        $raw = $data['published_at'] ?? null;
        if ($raw === null || trim((string) $raw) === '') {
            $data['published_at'] = null;
        } else {
            $carbon = JalaliDate::parseToCarbon((string) $raw);
            if (! $carbon) {
                throw ValidationException::withMessages([
                    'published_at' => 'تاریخ انتشار شمسی نامعتبر است. نمونه: ۱۴۰۴/۰۵/۲۶ ۱۴:۳۰',
                ]);
            }
            $data['published_at'] = $carbon;
        }

        return $data;
    }
}
