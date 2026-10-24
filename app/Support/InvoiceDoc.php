<?php

namespace App\Support;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/** One place that builds what an invoice looks like, for the page, the print view, the PDF and the email attachment. */
class InvoiceDoc
{
    public static function data(Invoice $i, bool $embedLogo = false): array
    {
        $i->loadMissing('account.agency', 'items', 'project');
        $agency = $i->account->agency;
        $cfg = $agency->invoiceSettings();
        $logo = null;
        if ($agency->logo && Storage::disk('local')->exists($agency->logo)) {
            $logo = $embedLogo
                ? 'data:'.Storage::disk('local')->mimeType($agency->logo).';base64,'.base64_encode(Storage::disk('local')->get($agency->logo))
                : $agency->logoUrl();
        }

        return [
            'i' => $i, 'agency' => $agency, 'cfg' => $cfg, 'logo' => $logo, 'client' => $i->account,
            'seller' => $cfg['business_name'] ?: $agency->name,
        ];
    }

    /** An unsaved example invoice in the agency's own details, for the template previews in invoice settings. */
    public static function sample(\App\Models\Agency $agency, string $tpl): array
    {
        $account = new \App\Models\Account(['name' => 'Sample Client Co.', 'billing_address' => "12 Example Street\nDubai, UAE", 'tax_id' => 'TRN 100123456700003']);
        $account->setRelation('agency', $agency);
        $i = new Invoice([
            'number' => $agency->formatInvoiceNumber((int) $agency->invoiceSettings()['next_number']), 'title' => 'October social media',
            'issued_on' => today(), 'due_on' => today()->addDays(14), 'currency' => 'AED', 'draft' => false,
            'discount_type' => 'percent', 'discount_value' => 0, 'subtotal' => 3450, 'discount' => 0, 'amount' => 3622.50,
            'tax_breakdown' => [['name' => 'VAT', 'rate' => 5, 'amount' => 172.50]],
            'terms' => $agency->invoiceSettings()['terms'], 'notes' => $agency->invoiceSettings()['notes'],
        ]);
        $i->setRelation('account', $account);
        $i->setRelation('project', null);
        $i->setRelation('items', collect([
            new \App\Models\InvoiceItem(['description' => 'Social media management', 'details' => '12 posts and 4 reels', 'qty' => 1, 'unit_price' => 2500, 'discount_type' => 'percent', 'discount_value' => 10, 'discount' => 250, 'taxes' => [['name' => 'VAT', 'rate' => 5]]]),
            new \App\Models\InvoiceItem(['description' => 'Reel production', 'qty' => 4, 'unit_price' => 300, 'discount_type' => 'percent', 'discount_value' => 0, 'discount' => 0, 'taxes' => [['name' => 'VAT', 'rate' => 5]]]),
        ]));
        $cfg = $agency->invoiceSettings();
        $logo = $agency->logo && Storage::disk('local')->exists($agency->logo) ? $agency->logoUrl() : null;

        return ['i' => $i, 'agency' => $agency, 'cfg' => $cfg, 'logo' => $logo, 'client' => $account, 'seller' => $cfg['business_name'] ?: $agency->name, 'tpl' => $tpl];
    }

    public static function fileName(Invoice $i): string
    {
        return 'Invoice-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $i->number).'.pdf';
    }

    /** The team's own uploaded PDF if there is one, otherwise a generated copy. */
    public static function pdfBytes(Invoice $i): string
    {
        if ($i->file_path && Storage::disk('local')->exists($i->file_path)) {
            return Storage::disk('local')->get($i->file_path);
        }

        return Pdf::loadView('portal.invoice-pdf', self::data($i, true))->setPaper('a4')->output();
    }
}
