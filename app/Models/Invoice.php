<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issued_on' => 'date', 'due_on' => 'date', 'paid_on' => 'date', 'sent_at' => 'datetime', 'draft' => 'boolean',
        'amount' => 'decimal:2', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'discount_value' => 'decimal:2',
        'tax_rate' => 'decimal:2', 'tax_amount' => 'decimal:2', 'tax_breakdown' => 'array',
        'reminded_before_at' => 'datetime', 'reminded_overdue_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => ['Pending', 'Invoice issued, not yet due'],
        'due_soon' => ['Due Soon', 'Payment due within 3 days'],
        'overdue' => ['Overdue', 'Past the due date'],
        'paid' => ['Paid', 'Payment received and confirmed'],
    ];

    public const SOON_DAYS = 3;

    public const METHODS = ['bank' => 'Bank transfer', 'card' => 'Card', 'cash' => 'Cash', 'cheque' => 'Cheque', 'other' => 'Other'];

    protected static function booted(): void
    {
        static::creating(function (Invoice $i) {
            $i->agency_id ??= Account::whereKey($i->account_id)->value('agency_id');
        });
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position')->orderBy('id');
    }

    /** Clients only ever see invoices that have been sent. */
    public function scopeVisibleToClient($q)
    {
        return $q->where('draft', false);
    }

    /** Status is worked out from the dates, so it can never go stale. */
    public function status(): string
    {
        if ($this->paid_on) {
            return 'paid';
        }
        if ($this->due_on->isBefore(today())) {
            return 'overdue';
        }

        return $this->due_on->lte(today()->addDays(self::SOON_DAYS)) ? 'due_soon' : 'pending';
    }

    public function statusLabel(): string
    {
        return $this->draft ? 'Draft' : self::STATUSES[$this->status()][0];
    }

    /** CSS class suffix for the status tag. */
    public function statusKey(): string
    {
        return $this->draft ? 'draft' : $this->status();
    }

    public function isPaid(): bool
    {
        return (bool) $this->paid_on;
    }

    /** Paid invoices are locked: they can no longer be edited or deleted. */
    public function editable(): bool
    {
        return ! $this->isPaid();
    }

    public function money(?float $v = null): string
    {
        return $this->currency.' '.number_format((float) ($v ?? $this->amount), 2);
    }

    /** Lines to show: the stored ones, or a single line for invoices made before line items existed. */
    public function lines()
    {
        if ($this->items->isNotEmpty()) {
            return $this->items;
        }

        return collect([new InvoiceItem(['description' => $this->title ?: 'Services', 'qty' => 1, 'unit_price' => $this->amount])]);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /** Replace the lines and recompute every total. Each line: description, details, qty, unit_price, discount_type/value, taxes [{name, rate}]. */
    public function setLines(array $lines): void
    {
        $this->items()->delete();
        foreach (array_values($lines) as $n => $l) {
            $this->items()->create([
                'description' => $l['description'], 'details' => $l['details'] ?? null, 'qty' => $l['qty'], 'unit_price' => $l['unit_price'], 'position' => $n,
                'discount_type' => $l['discount_type'] ?? 'percent', 'discount_value' => $l['discount_value'] ?? 0,
                'taxes' => collect($l['taxes'] ?? [])->map(fn ($t) => ['name' => (string) $t['name'], 'rate' => round((float) $t['rate'], 2)])->values()->all(),
            ]);
        }
        $this->load('items');
        $this->recalc();
    }

    /**
     * Line discount first, then the overall discount (shared across lines in proportion), then each line's taxes on what is left.
     * Taxes are grouped by name and rate so the invoice can list "VAT 5%" once.
     */
    public function recalc(): void
    {
        $net = [];
        foreach ($this->items as $it) {
            $gross = $it->gross();
            $it->discount = $it->discountFor($gross);
            $it->save();
            $net[$it->id] = $gross - (float) $it->discount;
        }
        $sub = round(array_sum($net), 2);
        $overall = $this->discount_type === 'percent'
            ? round($sub * min(100, (float) $this->discount_value) / 100, 2)
            : min($sub, round((float) $this->discount_value, 2));
        $factor = $sub > 0 ? ($sub - $overall) / $sub : 0;

        $groups = [];
        foreach ($this->items as $it) {
            foreach ($it->taxes ?? [] as $t) {
                $k = $t['name'].'|'.$t['rate'];
                $groups[$k] ??= ['name' => $t['name'], 'rate' => (float) $t['rate'], 'amount' => 0.0];
                $groups[$k]['amount'] += $net[$it->id] * $factor * (float) $t['rate'] / 100;
            }
        }
        $breakdown = array_values(array_map(fn ($g) => ['amount' => round($g['amount'], 2)] + $g, $groups));
        $tax = round(array_sum(array_column($breakdown, 'amount')), 2);
        $this->fill([
            'subtotal' => $sub, 'discount' => $overall, 'tax_amount' => $tax, 'tax_breakdown' => $breakdown, 'amount' => round($sub - $overall + $tax, 2),
        ])->save();
    }

    public function url(): string
    {
        return url('/portal/invoices/'.$this->id);
    }
}
