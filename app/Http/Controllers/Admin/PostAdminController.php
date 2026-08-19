<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\Post;
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
        $type = $request->query('type', 'post');
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $posts = Post::query()
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
        return view('admin.posts.form', [
            'post' => new Post(['type' => $request->query('type', 'post'), 'status' => 'draft']),
            'authors' => Author::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'selectedTags' => [],
            'revisions' => collect(),
            'previewUrl' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title'], '-', null) ?: uniqid('p-');
        if (empty($data['published_at']) && $data['status'] === 'published') {
            $data['published_at'] = now();
        }
        $post = Post::create($data);
        $post->tags()->sync($request->input('tags', []));
        $this->snapshot($post);
        ActivityLog::record('post.create', $post, 'ایجاد: '.$post->title);

        return redirect()->route('admin.posts.edit', $post)->with('ok', 'ذخیره شد.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', [
            'post' => $post,
            'authors' => Author::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'selectedTags' => $post->tags()->pluck('tags.id')->all(),
            'revisions' => $post->revisions()->with('user:id,name')->limit(12)->get(),
            'previewUrl' => URL::temporarySignedRoute('preview.show', now()->addDays(7), ['slug' => $post->slug]),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->snapshot($post);
        $data = $this->validated($request, $post->id);
        if (empty($data['published_at']) && $data['status'] === 'published' && ! $post->published_at) {
            $data['published_at'] = now();
        }
        $post->update($data);
        $post->tags()->sync($request->input('tags', []));
        ActivityLog::record('post.update', $post, 'ویرایش: '.$post->title);

        return back()->with('ok', 'به‌روزرسانی شد.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $title = $post->title;
        $type = $post->type;
        $post->tags()->detach();
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

        $posts = Post::query()->whereIn('id', $data['ids'])->get();
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
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug,'.($id ?? 'NULL'),
            'type' => 'required|in:post,page',
            'status' => 'required|in:published,draft,hidden',
            'author_id' => 'nullable|exists:authors,id',
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
