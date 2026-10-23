<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Support\Notify;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'portal:invoice-reminders';

    protected $description = 'Email clients before an invoice is due and again while it is overdue';

    public function handle(): int
    {
        $before = 0;
        $late = 0;

        // 1) due within the reminder window and not reminded yet
        Invoice::with('account')->where('draft', false)->whereNull('paid_on')->whereNull('reminded_before_at')
            ->whereBetween('due_on', [today()->toDateString(), today()->addDays((int) config('portal.invoice_reminder_days'))->toDateString()])
            ->each(function (Invoice $i) use (&$before) {
                Notify::invoiceReminder($i, false);
                $i->update(['reminded_before_at' => now()]);
                $before++;
            });

        // 2) overdue: first notice immediately, then every N days until paid
        Invoice::with('account')->where('draft', false)->whereNull('paid_on')->where('due_on', '<', today()->toDateString())
            ->where(fn ($q) => $q->whereNull('reminded_overdue_at')->orWhere('reminded_overdue_at', '<=', now()->subDays((int) config('portal.overdue_repeat_days'))))
            ->each(function (Invoice $i) use (&$late) {
                Notify::invoiceReminder($i, true);
                $i->update(['reminded_overdue_at' => now()]);
                $late++;
            });

        $this->info("Reminders sent: {$before} before due date, {$late} overdue.");

        return self::SUCCESS;
    }
}
