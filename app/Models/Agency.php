<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Agency extends Model
{
    protected $guarded = [];

    protected $casts = ['invoice_settings' => 'array'];

    public function invoiceTaxes()
    {
        return $this->hasMany(InvoiceTax::class)->orderBy('position')->orderBy('id');
    }

    public static function invoiceDefaults(): array
    {
        return [
            'template' => 'classic', 'prefix' => 'INV-', 'padding' => 4, 'next_number' => 1, 'due_days' => 14,
            'label_item' => 'Description', 'label_qty' => 'Qty', 'label_rate' => 'Rate', 'label_discount' => 'Disc', 'label_tax' => 'Tax', 'label_amount' => 'Amount',
            'business_name' => '', 'address' => '', 'tax_id' => '', 'email' => '', 'phone' => '', 'bank' => '',
            'terms' => 'Payment is due by the due date shown above. Please use the invoice number as the payment reference.',
            'notes' => 'Thank you for your business.', 'email_message' => '',
        ];
    }

    public function invoiceSettings(): array
    {
        return array_merge(self::invoiceDefaults(), array_filter($this->invoice_settings ?? [], fn ($v) => $v !== null));
    }

    /** Format a counter value: prefix "INV-" and 7 become "INV-0007". {YYYY} in the prefix becomes the year. */
    public function formatInvoiceNumber(int $n, ?array $cfg = null): string
    {
        $cfg ??= $this->invoiceSettings();

        return str_replace('{YYYY}', now()->format('Y'), (string) $cfg['prefix']).str_pad((string) $n, (int) $cfg['padding'], '0', STR_PAD_LEFT);
    }

    /** Next free number, going up by one each time. Locked so two people saving at once never get the same one. */
    public function allocateInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $agency = static::whereKey($this->id)->lockForUpdate()->first();
            $cfg = $agency->invoiceSettings();
            $n = max(1, (int) $cfg['next_number']);
            while (Invoice::where('agency_id', $agency->id)->where('number', $agency->formatInvoiceNumber($n, $cfg))->exists()) {
                $n++;
            }
            $number = $agency->formatInvoiceNumber($n, $cfg);
            $agency->update(['invoice_settings' => array_merge($agency->invoice_settings ?? [], ['next_number' => $n + 1])]);

            return $number;
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class)->orderBy('position')->orderBy('id');
    }

    public function templates()
    {
        return $this->hasMany(DocTemplate::class)->orderBy('id');
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? url('/agency-logo/'.$this->id).'?v='.$this->updated_at?->timestamp : null;
    }
}
