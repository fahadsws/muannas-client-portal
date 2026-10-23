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
