<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tag;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TagAdminController extends Controller
{
    public function index(): View
    {
        $tags = Tag::withCount('posts')->orderBy('name')->paginate(40);

        return view('admin.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('admin.tags.form', ['tag' => new Tag]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name'], '-', null) ?: uniqid('tag-');
        $data = $this->handleImage($request, $data);
        $tag = Tag::create($data);
        ActivityLog::record('tag.create', $tag, 'تگ: '.$tag->name);

        return redirect()->route('admin.tags.index')->with('ok', 'تگ ذخیره شد.');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.form', compact('tag'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $this->validated($request, $tag->id);
        if (empty($data['slug'])) {
            $data['slug'] = $tag->slug;
        }
        $data = $this->handleImage($request, $data, $tag);
        $tag->update($data);
        ActivityLog::record('tag.update', $tag, 'ویرایش تگ: '.$tag->name);

        return back()->with('ok', 'تگ به‌روزرسانی شد.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $name = $tag->name;
        $tag->posts()->detach();
        $tag->delete();
        ActivityLog::record('tag.delete', null, 'حذف تگ: '.$name);

        return redirect()->route('admin.tags.index')->with('ok', 'تگ حذف شد.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug,'.($id ?? 'NULL'),
            'description' => 'nullable|string',
            'featured_image' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'image_file' => 'nullable|image|max:8192',
        ]);
    }

    private function handleImage(Request $request, array $data, ?Tag $tag = null): array
    {
        if ($request->hasFile('image_file')) {
            $saved = MediaStorage::storeImage($request->file('image_file'), 'tags');
            $data['featured_image'] = $saved['path'];
        }
        unset($data['image_file']);

        return $data;
    }
}
