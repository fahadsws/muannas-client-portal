<?php

namespace App\Mail;

use App\Support\InvoiceDoc;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** One branded layout for every portal email: heading, a few lines, one button. */
class PortalNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $heading,
        public array $lines,
        public ?string $button = null,
        public ?string $url = null,
        public ?int $invoiceId = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    /** Invoice emails carry the PDF; it is built when the mail is sent so the queue never stores file bytes. */
    public function attachments(): array
    {
        $invoice = $this->invoiceId ? \App\Models\Invoice::find($this->invoiceId) : null;

        return $invoice ? [Attachment::fromData(fn () => InvoiceDoc::pdfBytes($invoice), InvoiceDoc::fileName($invoice))->withMime('application/pdf')] : [];
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notice');
    }
}
