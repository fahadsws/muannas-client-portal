<?php

namespace App\Support;

use App\Mail\PortalNotice;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Post;
use App\Models\Report;
use App\Models\ReviewBatch;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/** Every email the portal sends. A mail failure is logged and never blocks the action that caused it. */
class Notify
{
    private static function send(iterable $users, PortalNotice $mail): void
    {
        foreach (collect($users)->unique('email') as $u) {
            try {
                Mail::to($u->email)->send(clone $mail);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public static function clients(Account $a)
    {
        return $a->clientUsers()->get();
    }

    /** Assigned team members plus the agency admins. */
    public static function team(Account $a)
    {
        return $a->team()->get()->merge(User::where('agency_id', $a->agency_id)->where('role', 'owner')->get());
    }

    /** "New content is ready for your review" — to the client, with the WhatsApp-shareable batch link. */
    public static function contentReady(ReviewBatch $b, int $count): void
    {
        $a = $b->account;
        self::send(self::clients($a), new PortalNotice(
            "New content ready for your review — {$a->name}",
            'New content is ready for your review',
            ["{$count} ".($count === 1 ? 'post is' : 'posts are')." waiting for your approval in \"{$b->title}\".", 'You can approve each one with a single tap, or ask for changes.'],
            'Review content',
            $b->url(),
        ));
    }

    /** Client approved / asked for changes / commented — to the assigned team and admins. */
    public static function clientActed(Post $p, User $actor, string $what, ?string $detail = null): void
    {
        $lines = ["{$actor->name} {$what} on \"{$p->title}\" for {$p->account->name}."];
        if ($detail) {
            $lines[] = '"'.mb_strimwidth($detail, 0, 300, '…').'"';
        }
        self::send(self::team($p->account)->reject(fn ($u) => $u->id === $actor->id), new PortalNotice(
            "{$p->account->name}: {$actor->name} {$what}",
            ucfirst($what),
            $lines,
            'Open post',
            url('/portal/posts/'.$p->id),
        ));
    }

    public static function invoiceReminder(Invoice $i, bool $overdue): void
    {
        $a = $i->account;
        $when = $overdue ? 'was due on '.$i->due_on->format('j M Y').' and is now overdue' : 'is due on '.$i->due_on->format('j M Y');
        self::send(self::clients($a), new PortalNotice(
            ($overdue ? 'Overdue: ' : 'Reminder: ').'invoice '.$i->number,
            $overdue ? 'An invoice is overdue' : 'An invoice is due soon',
            ["Invoice {$i->number} for {$i->money()} {$when}.", 'If you have already paid, thank you — we will mark it as paid once we confirm receipt.'],
            'View invoice',
            $i->url(),
        ));
    }

    /** Manual send: the invoice PDF goes to the chosen addresses, with an optional personal message. */
    public static function invoiceSent(Invoice $i, array $emails, ?string $message = null): void
    {
        $a = $i->account;
        $cfg = $a->agency->invoiceSettings();
        $lines = ["Invoice {$i->number} for {$i->money()} is ready. It is due on ".$i->due_on->format('j M Y').'.'];
        foreach (array_filter([$message, $cfg['email_message'] ?? null]) as $m) {
            $lines[] = $m;
        }
        $lines[] = 'The invoice is attached as a PDF, and you can also view it in your portal.';
        foreach ($emails as $e) {
            try {
                Mail::to($e)->send(new PortalNotice('Invoice '.$i->number.' from '.($cfg['business_name'] ?: $a->agency->name), 'Invoice '.$i->number, $lines, 'View invoice', $i->url(), $i->id));
            } catch (\Throwable $ex) {
                report($ex);
            }
        }
    }

    public static function reportReady(Report $r): void
    {
        $a = $r->account;
        self::send(self::clients($a), new PortalNotice(
            'Your '.$r->month->format('F Y').' performance report is ready',
            'Your monthly report is ready',
            ['The '.$r->month->format('F Y')." performance report for {$a->name} is now available in your portal."],
            'View report',
            url('/portal/c/'.$a->id.'/reports'),
        ));
    }
}
