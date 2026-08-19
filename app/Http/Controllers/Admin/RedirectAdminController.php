<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RedirectAdminController extends Controller
{
    public function index(): View
    {
        $redirects = Redirect::query()->orderByDesc('id')->paginate(40);

        return view('admin.redirects.index', compact('redirects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_path' => 'required|string|max:500',
            'to_url' => 'required|string|max:500',
            'status_code' => 'required|in:301,302',
        ]);
        $from = '/'.ltrim(parse_url($data['from_path'], PHP_URL_PATH) ?: $data['from_path'], '/');
        if ($from !== '/') {
            $from = rtrim($from, '/') ?: '/';
        }

        Redirect::query()->updateOrCreate(
            ['from_path' => $from],
            [
                'to_url' => trim($data['to_url']),
                'status_code' => (int) $data['status_code'],
                'enabled' => true,
            ]
        );
        ActivityLog::record('redirect.save', null, "ریدایرکت {$from}");

        return back()->with('ok', 'ریدایرکت ذخیره شد.');
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $from = $redirect->from_path;
        $redirect->delete();
        ActivityLog::record('redirect.delete', null, "حذف ریدایرکت {$from}");

        return back()->with('ok', 'حذف شد.');
    }
}
