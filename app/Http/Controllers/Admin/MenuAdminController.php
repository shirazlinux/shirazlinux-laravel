<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\NavMenu;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuAdminController extends Controller
{
    public function edit(): View
    {
        $items = setting('nav_menu');
        if (! is_array($items) || $items === []) {
            $items = NavMenu::defaults();
        }

        return view('admin.menu.edit', ['items' => $items]);
    }

    public function update(Request $request): RedirectResponse
    {
        $raw = $request->input('items_json', '[]');
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return back()->withErrors(['items_json' => 'ساختار منو نامعتبر است.']);
        }

        $clean = $this->sanitize($decoded);
        Settings::set('nav_menu', $clean);
        ActivityLog::record('menu.update', null, 'به‌روزرسانی منوی سایت');

        return redirect()->route('admin.menu.edit')->with('ok', 'منو ذخیره شد.');
    }

    public function reset(): RedirectResponse
    {
        Settings::set('nav_menu', NavMenu::defaults());
        ActivityLog::record('menu.reset', null, 'بازنشانی منو به پیش‌فرض');

        return redirect()->route('admin.menu.edit')->with('ok', 'منو به حالت پیش‌فرض برگشت.');
    }

    /** @param list<mixed> $items */
    private function sanitize(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = ($item['type'] ?? 'link') === 'group' ? 'group' : 'link';
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            if ($type === 'group') {
                $children = [];
                foreach ($item['children'] ?? [] as $ch) {
                    if (! is_array($ch)) {
                        continue;
                    }
                    $cl = trim((string) ($ch['label'] ?? ''));
                    $cu = trim((string) ($ch['url'] ?? ''));
                    if ($cl === '' || $cu === '') {
                        continue;
                    }
                    $children[] = ['label' => $cl, 'url' => $cu];
                }
                $out[] = ['type' => 'group', 'label' => $label, 'children' => $children];
            } else {
                $url = trim((string) ($item['url'] ?? '/'));
                $out[] = [
                    'type' => 'link',
                    'label' => $label,
                    'url' => $url !== '' ? $url : '/',
                    'cta' => ! empty($item['cta']),
                ];
            }
        }

        return $out;
    }
}
