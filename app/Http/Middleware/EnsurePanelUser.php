<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->canAccessAdmin()) {
            abort(403, 'دسترسی به پنل ندارید.');
        }

        $name = $request->route()?->getName();
        if ($user->mustChangePassword()) {
            if (in_array($name, ['admin.password.edit', 'admin.password.update'], true)) {
                return $next($request);
            }

            return redirect()->route('admin.password.edit');
        }

        if ($user->isWriter()) {
            $allowed = [
                'admin.dashboard',
                'admin.posts.index',
                'admin.posts.create',
                'admin.posts.store',
                'admin.posts.edit',
                'admin.posts.update',
                'admin.posts.destroy',
                'admin.posts.bulk',
                'admin.media.index',
                'admin.media.store',
                'admin.password.edit',
                'admin.password.update',
            ];
            if ($name && ! in_array($name, $allowed, true)) {
                abort(403, 'نویسنده فقط به مطالب خودش دسترسی دارد.');
            }
        }

        return $next($request);
    }
}
