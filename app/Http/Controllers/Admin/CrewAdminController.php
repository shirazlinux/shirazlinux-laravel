<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\CrewMember;
use App\Support\MediaStorage;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CrewAdminController extends Controller
{
    public function index(): View
    {
        $crew = CrewMember::query()->orderBy('sort')->orderBy('name')->get();

        return view('admin.crew.index', compact('crew'));
    }

    public function create(): View
    {
        return view('admin.crew.form', [
            'member' => new CrewMember(['active' => true, 'sort' => 0]),
            'authors' => Author::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name'], '-', null) ?: uniqid('crew-');
        $data = $this->handlePhoto($request, $data);
        $member = CrewMember::create($data);
        ActivityLog::record('crew.create', $member, 'کادر اجرایی: '.$member->name);

        return redirect()->route('admin.crew.index')->with('ok', 'عضو کادر ذخیره شد.');
    }

    public function edit(CrewMember $crew): View
    {
        return view('admin.crew.form', [
            'member' => $crew,
            'authors' => Author::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, CrewMember $crew): RedirectResponse
    {
        $data = $this->validated($request, $crew->id);
        if (empty($data['slug'])) {
            $data['slug'] = $crew->slug;
        }
        $data = $this->handlePhoto($request, $data);
        $crew->update($data);
        ActivityLog::record('crew.update', $crew, 'ویرایش کادر: '.$crew->name);

        return back()->with('ok', 'کادر به‌روزرسانی شد.');
    }

    public function destroy(CrewMember $crew): RedirectResponse
    {
        $name = $crew->name;
        $crew->posts()->detach();
        $crew->delete();
        ActivityLog::record('crew.delete', null, 'حذف کادر: '.$name);

        return redirect()->route('admin.crew.index')->with('ok', 'عضو کادر حذف شد.');
    }

    public function updateIntro(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'crew_page_title' => 'nullable|string|max:200',
            'crew_page_intro' => 'nullable|string|max:4000',
        ]);
        Settings::setMany([
            'crew_page_title' => trim((string) ($data['crew_page_title'] ?? '')),
            'crew_page_intro' => trim((string) ($data['crew_page_intro'] ?? '')),
        ]);

        return back()->with('ok', 'متن معرفی صفحه ذخیره شد.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:crew_members,slug,'.($id ?? 'NULL'),
            'role' => 'nullable|string|max:160',
            'bio' => 'nullable|string',
            'photo' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
            'mastodon' => 'nullable|string|max:255',
            'author_id' => 'nullable|exists:authors,id',
            'sort' => 'nullable|integer|min:0|max:9999',
            'tenure' => 'nullable|string|max:80',
            'photo_file' => 'nullable|image|max:4096',
        ]) + [
            'active' => $request->boolean('active'),
            'former' => $request->boolean('former'),
            'sort' => (int) $request->input('sort', 0),
        ];
    }

    private function handlePhoto(Request $request, array $data): array
    {
        if ($request->hasFile('photo_file')) {
            $saved = MediaStorage::storeImage($request->file('photo_file'), 'crew');
            $data['photo'] = $saved['path'];
        }
        unset($data['photo_file']);

        return $data;
    }
}
