<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CrewMember;
use App\Models\EventStaff;
use App\Models\Post;
use Illuminate\View\View;

class CrewController extends Controller
{
    public function index(): View
    {
        abort(404);
    }

    public function show(string $slug): View
    {
        $member = CrewMember::query()->where('slug', $slug)->firstOrFail();

        $eventRoles = EventStaff::query()
            ->where('crew_member_id', $member->id)
            ->with(['post' => fn ($q) => $q->published()->postsOnly()->with('tags')])
            ->orderByDesc('id')
            ->get()
            ->filter(fn (EventStaff $row) => $row->post !== null)
            ->values();

        $authored = collect();
        if ($member->author_id) {
            $authored = Post::query()
                ->published()
                ->postsOnly()
                ->where('author_id', $member->author_id)
                ->with(['author', 'tags'])
                ->orderByDesc('published_at')
                ->limit(12)
                ->get();
        }

        return view('site.crew-member', compact('member', 'eventRoles', 'authored'));
    }
}
