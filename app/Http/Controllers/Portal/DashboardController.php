<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostEvent;
use App\Support\Access;
use Illuminate\Http\Request;

class DashboardController extends PortalController
{
    /** Clients go straight to their own dashboard; staff see all their clients. */
    public function home(Request $r)
    {
        $u = $r->user();
        $accounts = Access::accountsFor($u);

        if (! $u->isStaff()) {
            return $accounts->count() === 1
                ? redirect('/portal/c/'.$accounts->first()->id)
                : view('portal.no-workspace');
        }

        return redirect('/portal/clients');
    }

    public function show(Request $r, Account $account)
    {
        $this->reach($r, $account);
        $staff = $this->isStaff($r);

        $stats = $this->visiblePosts($r, $account)->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');
        $summary = [
            'pending' => (int) ($stats['pending'] ?? 0),
            'approved' => (int) ($stats['approved'] ?? 0),
            'progress' => (int) (($stats['draft'] ?? 0) + ($stats['changes'] ?? 0)),
            'published' => (int) ($stats['published'] ?? 0),
        ];

        $pipeline = collect(array_keys(Post::STATUSES))->mapWithKeys(fn ($k) => [$k => (int) ($stats[$k] ?? 0)]);
        if (! $staff) {
            $pipeline->forget('draft');
        }
        $months = collect(range(5, 0))->map(fn ($n) => today()->startOfMonth()->subMonths($n));
        $byMonth = $this->visiblePosts($r, $account)->whereNotNull('scheduled_on')->get(['scheduled_on'])->groupBy(fn ($p) => $p->scheduled_on->format('Y-m'))->map->count();
        $monthly = $months->map(fn ($m) => ['label' => $m->format('M'), 'n' => (int) ($byMonth[$m->format('Y-m')] ?? 0)]);

        $attention = $this->visiblePosts($r, $account)->whereIn('status', $staff ? ['changes', 'pending'] : ['pending'])
            ->orderBy('scheduled_on')->orderBy('id')->limit(5)->get();
        $upcoming = $this->visiblePosts($r, $account)->whereDate('scheduled_on', '>=', today())->where('status', '!=', 'published')
            ->orderBy('scheduled_on')->orderBy('id')->limit(5)->get();
        $projects = $account->projects()->with('tasks')->where('status', '!=', 'completed')->limit(4)->get();

        $outstanding = $account->invoices()->visibleToClient()->whereNull('paid_on')->get()->sortBy('due_on')->values();
        $reports = $account->reports()->limit(2)->get();

        $hour = (int) now()->format('G');

        return view('portal.dashboard', [
            'account' => $account, 'summary' => $summary, 'pipeline' => $pipeline, 'monthly' => $monthly, 'attention' => $attention, 'upcoming' => $upcoming, 'projects' => $projects,
            'outstanding' => $outstanding, 'report' => $reports->get(0), 'previous' => $reports->get(1),
            'activity' => $this->activity($r, $account), 'canAct' => $r->user()->isClient(),
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'),
            'manage' => $staff && $this->reach($r, $account) === 'edit',
        ]);
    }

    /** Latest approvals, change requests, sends and comments on the posts this person can see. */
    private function activity(Request $r, Account $account)
    {
        $ids = $this->visiblePosts($r, $account)->pluck('id');
        $staff = $this->isStaff($r);

        $events = PostEvent::with('post:id,title')->whereIn('post_id', $ids)->when(! $staff, fn ($q) => $q->where('action', '!=', 'reopened'))
            ->latest('id')->limit(8)->get()->map(fn ($e) => [
                'at' => $e->created_at, 'who' => $e->actor, 'role' => $e->role, 'kind' => $e->action, 'post' => $e->post_id,
                'text' => match ($e->action) {
                    'approved' => 'approved', 'changes_requested' => 'asked for changes on', 'submitted' => 'sent for review:',
                    'published' => 'published', default => 'updated',
                }.' “'.($e->post?->title ?? 'a post').'”',
            ]);
        $comments = PostComment::with('post:id,title')->whereIn('post_id', $ids)->latest('id')->limit(8)->get()->map(fn ($c) => [
            'at' => $c->created_at, 'who' => $c->author, 'role' => $c->role, 'kind' => 'comment', 'post' => $c->post_id,
            'text' => 'commented on “'.($c->post?->title ?? 'a post').'”',
        ]);

        return $events->concat($comments)->sortByDesc('at')->take(7)->values();
    }

    public function logo(Request $r, Account $account)
    {
        $this->reach($r, $account);

        return $this->stream((string) $account->logo);
    }
}
