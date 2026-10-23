<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends PortalController
{
    public function show(Request $r, Account $account)
    {
        $this->reach($r, $account);
        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $r->query('month', now()->format('Y-m')));
        } catch (\Throwable $e) {
            $month = now()->startOfMonth();
        }
        $month = $month->startOfMonth();

        $from = $month->copy()->startOfWeek(Carbon::MONDAY);
        $to = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $posts = $this->visiblePosts($r, $account)->whereBetween('scheduled_on', [$from->toDateString(), $to->toDateString()])
            ->orderBy('scheduled_on')->orderBy('id')->get()->groupBy(fn ($p) => $p->scheduled_on->toDateString());

        $weeks = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $weeks[(int) floor($d->diffInDays($from) / 7)][] = $d->copy();
        }

        return view('portal.calendar', [
            'account' => $account, 'month' => $month, 'weeks' => $weeks, 'posts' => $posts,
            'prev' => $month->copy()->subMonth()->format('Y-m'), 'next' => $month->copy()->addMonth()->format('Y-m'),
            'unscheduled' => $this->visiblePosts($r, $account)->whereNull('scheduled_on')->count(),
        ]);
    }
}
