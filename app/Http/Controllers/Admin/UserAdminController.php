<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    public function index(): View
    {
        $users = User::query()->orderBy('id')->get();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|max:120',
            'role' => 'required|in:admin,editor',
        ]);
        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);
        ActivityLog::record('user.create', null, 'ایجاد کاربر '.$data['email']);

        return back()->with('ok', 'کاربر ایجاد شد.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|max:120',
            'role' => 'required|in:admin,editor',
        ]);
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        ActivityLog::record('user.update', null, 'ویرایش کاربر '.$user->email);

        return back()->with('ok', 'کاربر به‌روز شد.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'نمی‌توانید خودتان را حذف کنید.']);
        }
        if (User::query()->count() <= 1) {
            return back()->withErrors(['user' => 'حداقل یک کاربر باید بماند.']);
        }
        $email = $user->email;
        $user->delete();
        ActivityLog::record('user.delete', null, 'حذف کاربر '.$email);

        return back()->with('ok', 'حذف شد.');
    }
}
