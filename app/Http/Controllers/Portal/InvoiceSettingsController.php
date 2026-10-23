<?php

namespace App\Http\Controllers\Portal;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\InvoiceTax;
use Illuminate\Http\Request;

/** Agency-wide invoice setup: numbering, business details, column names, terms and the list of taxes. */
class InvoiceSettingsController extends PortalController
{
    private function agency(Request $r): Agency
    {
        $u = $r->user();
        abort_unless($u->isStaff() && $u->hasPerm('billing.manage'), 403, 'Your role does not allow that.');

        return $u->agency;
    }

    private function tax(Request $r, InvoiceTax $tax): InvoiceTax
    {
        abort_unless($tax->agency_id === $this->agency($r)->id, 404);

        return $tax;
    }

    public function show(Request $r)
    {
        $agency = $this->agency($r);
        $cfg = $agency->invoiceSettings();

        return view('portal.invoice-settings', [
            'agency' => $agency, 'cfg' => $cfg, 'preview' => $agency->formatInvoiceNumber((int) $cfg['next_number'], $cfg),
            'issued' => Invoice::where('agency_id', $agency->id)->count(), 'taxes' => $agency->invoiceTaxes()->get(),
            'tab' => $r->query('tab') === 'tax' ? 'tax' : 'general',
        ]);
    }

    public function save(Request $r)
    {
        $agency = $this->agency($r);
        $v = $r->validate([
            'prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_\/{}.]*$/'], 'padding' => 'required|integer|min:1|max:10', 'next_number' => 'required|integer|min:1|max:99999999',
            'due_days' => 'required|integer|min:0|max:365',
            'label_item' => 'nullable|string|max:24', 'label_qty' => 'nullable|string|max:24', 'label_rate' => 'nullable|string|max:24',
            'label_discount' => 'nullable|string|max:24', 'label_tax' => 'nullable|string|max:24', 'label_amount' => 'nullable|string|max:24',
            'business_name' => 'nullable|string|max:120', 'address' => 'nullable|string|max:500', 'tax_id' => 'nullable|string|max:60', 'email' => 'nullable|email|max:120',
            'phone' => 'nullable|string|max:40', 'bank' => 'nullable|string|max:800', 'terms' => 'nullable|string|max:3000', 'notes' => 'nullable|string|max:3000', 'email_message' => 'nullable|string|max:1000',
        ], ['prefix.regex' => 'The prefix can only use letters, numbers and - _ / . (and {YYYY} for the year).']);
        $defaults = Agency::invoiceDefaults();
        foreach (['label_item', 'label_qty', 'label_rate', 'label_discount', 'label_tax', 'label_amount'] as $k) {
            $v[$k] = trim((string) ($v[$k] ?? '')) ?: $defaults[$k];
        }
        $v['prefix'] = $v['prefix'] ?? '';
        foreach (['business_name', 'address', 'tax_id', 'email', 'phone', 'bank', 'terms', 'notes', 'email_message'] as $k) {
            $v[$k] = $v[$k] ?? '';
        }
        $agency->update(['invoice_settings' => array_merge($agency->invoice_settings ?? [], $v)]);

        return back()->with('ok', 'Invoice settings saved');
    }

    // ── taxes ────────────────────────────────────────────────

    private function taxRules(Request $r): array
    {
        return $r->validate(['name' => 'required|string|max:60', 'rate' => 'required|numeric|min:0|max:100', 'is_default' => 'nullable|boolean']);
    }

    public function storeTax(Request $r)
    {
        $agency = $this->agency($r);
        $v = $this->taxRules($r);
        $tax = $agency->invoiceTaxes()->create(['name' => $v['name'], 'rate' => $v['rate'], 'is_default' => $r->boolean('is_default'), 'position' => ($agency->invoiceTaxes()->max('position') ?? 0) + 1]);

        return $r->expectsJson() ? response()->json(['id' => $tax->id, 'name' => $tax->name, 'rate' => (float) $tax->rate, 'is_default' => $tax->is_default])
            : redirect('/portal/invoice-settings?tab=tax')->with('ok', 'Tax added');
    }

    public function updateTax(Request $r, InvoiceTax $tax)
    {
        $this->tax($r, $tax);
        $v = $this->taxRules($r);
        $tax->update(['name' => $v['name'], 'rate' => $v['rate'], 'is_default' => $r->boolean('is_default')]);

        return redirect('/portal/invoice-settings?tab=tax')->with('ok', 'Tax updated. Existing invoices keep the rate they were issued with.');
    }

    public function destroyTax(Request $r, InvoiceTax $tax)
    {
        $this->tax($r, $tax)->delete();

        return redirect('/portal/invoice-settings?tab=tax')->with('ok', 'Tax removed. Existing invoices are not changed.');
    }
}
