<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->ownsPost($post);
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, Post $post): bool
    {
        return $this->view($user, $post);
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->view($user, $post);
    }
}
