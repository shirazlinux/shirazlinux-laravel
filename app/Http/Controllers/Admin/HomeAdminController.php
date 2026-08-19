<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MediaStorage;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeAdminController extends Controller
{
    public function edit(): View
    {
        $settings = Settings::all();
        $slides = $settings['home_slider'] ?? Settings::defaultSlides();
        if (! is_array($slides) || $slides === []) {
            $slides = Settings::defaultSlides();
        }

        return view('admin.home.edit', [
            'settings' => $settings,
            'slides' => $slides,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'home_intro_title' => 'nullable|string|max:200',
            'home_intro_text' => 'nullable|string|max:2000',
            'home_show_slider' => 'sometimes|boolean',
            'home_show_edu' => 'sometimes|boolean',
            'home_show_history' => 'sometimes|boolean',
            'home_show_projects' => 'sometimes|boolean',
            'home_show_videos' => 'sometimes|boolean',
            'slides' => 'nullable|array|max:12',
            'slides.*.title' => 'nullable|string|max:160',
            'slides.*.alt' => 'nullable|string|max:200',
            'slides.*.url' => 'nullable|string|max:500',
            'slides.*.image' => 'nullable|string|max:500',
            'slides.*.enabled' => 'nullable',
            'slides.*.file' => 'nullable|image|max:8192',
        ]);

        $slidesIn = $request->input('slides', []);
        $files = $request->file('slides', []);
        $normalized = [];

        foreach ($slidesIn as $i => $slide) {
            if (! is_array($slide)) {
                continue;
            }
            $image = trim((string) ($slide['image'] ?? ''));
            if (isset($files[$i]['file']) && $files[$i]['file']) {
                $saved = MediaStorage::storeImage($files[$i]['file'], 'slider');
                $image = $saved['path'];
            }
            if ($image === '' && trim((string) ($slide['title'] ?? '')) === '') {
                continue;
            }
            $normalized[] = [
                'image' => $image,
                'title' => trim((string) ($slide['title'] ?? '')),
                'alt' => trim((string) ($slide['alt'] ?? $slide['title'] ?? '')),
                'url' => trim((string) ($slide['url'] ?? '')),
                'enabled' => ! empty($slide['enabled']),
            ];
        }

        Settings::setMany([
            'home_intro_title' => trim((string) ($data['home_intro_title'] ?? '')),
            'home_intro_text' => trim((string) ($data['home_intro_text'] ?? '')),
            'home_show_slider' => $request->boolean('home_show_slider'),
            'home_show_edu' => $request->boolean('home_show_edu'),
            'home_show_history' => $request->boolean('home_show_history'),
            'home_show_projects' => $request->boolean('home_show_projects'),
            'home_show_videos' => $request->boolean('home_show_videos'),
            'home_slider' => $normalized,
        ]);

        return redirect()
            ->route('admin.home.edit')
            ->with('ok', 'صفحه اصلی و اسلایدر ذخیره شد.');
    }
}
