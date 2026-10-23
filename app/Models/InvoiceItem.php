<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['qty' => 'decimal:2', 'unit_price' => 'decimal:2', 'discount_value' => 'decimal:2', 'discount' => 'decimal:2', 'taxes' => 'array'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Qty × rate, before this line's discount. */
    public function gross(): float
    {
        return round((float) $this->qty * (float) $this->unit_price, 2);
    }

    /** What the customer pays for this line before tax. */
    public function total(): float
    {
        return round($this->gross() - (float) $this->discount, 2);
    }

    public function discountFor(float $gross): float
    {
        $v = (float) $this->discount_value;

        return $this->discount_type === 'fixed' ? min($gross, round($v, 2)) : round($gross * min(100, $v) / 100, 2);
    }
}
