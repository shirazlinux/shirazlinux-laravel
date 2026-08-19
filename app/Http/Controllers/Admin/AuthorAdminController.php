<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Author;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthorAdminController extends Controller
{
    public function index(): View
    {
        $authors = Author::withCount('posts')->orderBy('name')->paginate(40);

        return view('admin.authors.index', compact('authors'));
    }

    public function create(): View
    {
        return view('admin.authors.form', ['author' => new Author]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name'], '-', null) ?: uniqid('author-');
        if (empty($data['username'])) {
            $data['username'] = $data['slug'];
        }
        $data = $this->handleAvatar($request, $data);
        $author = Author::create($data);
        ActivityLog::record('author.create', $author, 'نویسنده: '.$author->name);

        return redirect()->route('admin.authors.index')->with('ok', 'نویسنده ذخیره شد.');
    }

    public function edit(Author $author): View
    {
        return view('admin.authors.form', compact('author'));
    }

    public function update(Request $request, Author $author): RedirectResponse
    {
        $data = $this->validated($request, $author->id);
        if (empty($data['slug'])) {
            $data['slug'] = $author->slug;
        }
        $data = $this->handleAvatar($request, $data);
        $author->update($data);
        ActivityLog::record('author.update', $author, 'ویرایش نویسنده: '.$author->name);

        return back()->with('ok', 'نویسنده به‌روزرسانی شد.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        if ($author->posts()->exists()) {
            return back()->withErrors(['author' => 'این نویسنده مطلب دارد؛ اول مطالب را منتقل یا حذف کنید.']);
        }
        $name = $author->name;
        $author->delete();
        ActivityLog::record('author.delete', null, 'حذف نویسنده: '.$name);

        return redirect()->route('admin.authors.index')->with('ok', 'نویسنده حذف شد.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:authors,slug,'.($id ?? 'NULL'),
            'bio' => 'nullable|string',
            'avatar' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
            'mastodon' => 'nullable|string|max:255',
            'github' => 'nullable|string|max:255',
            'avatar_file' => 'nullable|image|max:4096',
        ]);
    }

    private function handleAvatar(Request $request, array $data): array
    {
        if ($request->hasFile('avatar_file')) {
            $saved = MediaStorage::storeImage($request->file('avatar_file'), 'authors');
            $data['avatar'] = $saved['path'];
        }
        unset($data['avatar_file']);

        return $data;
    }
}
