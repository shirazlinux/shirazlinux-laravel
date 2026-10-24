<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $cred = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if (Auth::attempt($cred, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user && $user->role === 'editor') {
                $user->forceFill(['role' => User::ROLE_WRITER])->save();
            }
            if ($user && $user->mustChangePassword()) {
                return redirect()->route('admin.password.edit');
            }
            if ($user && $user->isWriter()) {
                return redirect()->route('admin.posts.index', ['type' => 'post']);
            }

            return redirect()->intended(route('admin.dashboard'));
        }
        return back()->withErrors(['email' => 'ایمیل یا رمز اشتباه است.'])->onlyInput('email');
    }

    public function showPassword(): View
    {
        return view('admin.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:120|confirmed',
        ], [
            'current_password.required' => 'رمز فعلی را بنویسید.',
            'password.required' => 'رمز جدید را بنویسید.',
            'password.min' => 'رمز جدید حداقل ۸ نویسه باشد.',
            'password.confirmed' => 'تکرار رمز جدید یکی نیست.',
        ]);

        if (! Hash::check($data['current_password'], $user->getAuthPassword())) {
            return back()->withErrors(['current_password' => 'رمز فعلی درست نیست.']);
        }
        if (Hash::check($data['password'], $user->getAuthPassword())) {
            return back()->withErrors(['password' => 'رمز جدید باید با رمز فعلی فرق داشته باشد.']);
        }

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();
        $request->session()->regenerate();

        if ($user->isWriter()) {
            return redirect()
                ->route('admin.posts.index', ['type' => 'post'])
                ->with('ok', 'رمز عوض شد. حالا می‌توانید کار را شروع کنید.');
        }

        return redirect()->route('admin.dashboard')->with('ok', 'رمز عوض شد.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
