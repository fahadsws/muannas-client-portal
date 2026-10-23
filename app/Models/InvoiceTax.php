<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A tax the agency can apply to invoice lines (VAT 5%, Service tax 10%…). Invoices keep a snapshot, so editing one never changes old invoices. */
class InvoiceTax extends Model
{
    protected $guarded = [];

    protected $casts = ['rate' => 'decimal:2', 'is_default' => 'boolean'];

    public function label(): string
    {
        return $this->name.' '.rtrim(rtrim(number_format((float) $this->rate, 2), '0'), '.').'%';
    }
}
