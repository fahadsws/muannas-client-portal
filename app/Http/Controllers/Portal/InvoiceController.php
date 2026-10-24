<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use App\Models\Invoice;
use App\Support\InvoiceDoc;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoiceController extends PortalController
{
    private const PER_PAGE = 10;

    private const PAYMENTS_PER_PAGE = 15;

    private function canManage(Request $r, Account $account): bool
    {
        return $this->isStaff($r) && $this->reach($r, $account) === 'edit' && $r->user()->hasPerm('billing.manage');
    }

    /** Load an invoice the caller may see: clients never reach drafts. */
    private function invoice(Request $r, Invoice $invoice): Invoice
    {
        $this->reach($r, $invoice->account);
        abort_if($invoice->draft && ! $this->isStaff($r), 404);

        return $invoice;
    }

    public function index(Request $r, Account $account)
    {
        $this->reach($r, $account);
        $manage = $this->canManage($r, $account);
        $all = $account->invoices()->when(! $manage, fn ($q) => $q->visibleToClient())->get();
        $filter = array_key_exists($r->query('status'), Invoice::STATUSES) ? $r->query('status') : ($r->query('status') === 'draft' && $manage ? 'draft' : null);
        $live = $all->where('draft', false);
        $shown = $filter ? $all->filter(fn ($i) => $filter === 'draft' ? $i->draft : (! $i->draft && $i->status() === $filter))->values() : $all->values();
        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $invoices = new \Illuminate\Pagination\LengthAwarePaginator($shown->forPage($page, self::PER_PAGE)->values(), $shown->count(), self::PER_PAGE, $page, ['path' => $r->url(), 'query' => $r->query()]);

        return view('portal.invoices', [
            'account' => $account, 'all' => $all, 'filter' => $filter, 'manage' => $manage, 'currency' => $account->currency,
            'invoices' => $invoices, 'recentPaid' => $live->whereNotNull('paid_on')->sortByDesc('paid_on')->take(5)->values(),
            'outstanding' => $live->whereNull('paid_on')->sum('amount'), 'paid' => $live->whereNotNull('paid_on'), 'live' => $live,
        ]);
    }

    /** Every payment received, newest first, as a timeline grouped by month. */
    public function payments(Request $r, Account $account)
    {
        $this->reach($r, $account);
        $base = $account->invoices()->visibleToClient()->whereNotNull('paid_on')
            ->when($r->filled('from'), fn ($q) => $q->whereDate('paid_on', '>=', $r->query('from')))
            ->when($r->filled('to'), fn ($q) => $q->whereDate('paid_on', '<=', $r->query('to')));
        $payments = (clone $base)->reorder()->orderByDesc('paid_on')->orderByDesc('id')->paginate(self::PAYMENTS_PER_PAGE)->withQueryString();
        $months = $payments->getCollection()->groupBy(fn ($i) => $i->paid_on->format('Y-m'));
        $monthTotals = (clone $base)->reorder()->get(['paid_on', 'amount'])->groupBy(fn ($i) => $i->paid_on->format('Y-m'))->map(fn ($g) => $g->sum('amount'));

        return view('portal.payments', [
            'account' => $account, 'payments' => $payments, 'months' => $months, 'monthTotals' => $monthTotals, 'currency' => $account->currency,
            'total' => (clone $base)->reorder()->sum('amount'), 'count' => (clone $base)->count(), 'manage' => $this->canManage($r, $account),
        ]);
    }

    public function show(Request $r, Invoice $invoice)
    {
        $this->invoice($r, $invoice);
        $account = $invoice->account;
        $manage = $this->canManage($r, $account);

        return view('portal.invoice-show', InvoiceDoc::data($invoice) + [
            'account' => $account, 'manage' => $manage, 'recipients' => $manage ? $account->clientUsers()->get() : collect(), 'print' => $r->boolean('print'),
        ]);
    }

    public function create(Request $r, Account $account)
    {
        $this->manage($r, $account, 'billing.manage');
        $cfg = $account->agency->invoiceSettings();
        $invoice = new Invoice([
            'currency' => $account->currency, 'issued_on' => today(), 'due_on' => today()->addDays((int) $cfg['due_days']),
            'terms' => $cfg['terms'], 'notes' => $cfg['notes'], 'discount_type' => 'percent', 'discount_value' => 0,
        ]);

        return view('portal.invoice-form', $this->formData($account, $invoice, collect(), $account->agency->formatInvoiceNumber((int) $cfg['next_number'], $cfg)));
    }

    public function store(Request $r, Account $account)
    {
        $this->manage($r, $account, 'billing.manage');
        $v = $this->rules($r, $account);
        $agency = $account->agency;
        if (! empty($v['number'] ?? null)) {
            validator(['number' => $v['number']], ['number' => Rule::unique('invoices', 'number')->where('agency_id', $agency->id)], ['number.unique' => 'That invoice number is already used.'])->validate();
        }
        $send = $r->input('action') === 'send';
        $invoice = $account->invoices()->make($this->fields($v) + ['agency_id' => $agency->id, 'created_by' => $r->user()->id, 'currency' => $account->currency]);
        $invoice->number = ($v['number'] ?? null) ?: $agency->allocateInvoiceNumber();
        $invoice->draft = ! $send;
        $invoice->amount = 0;
        if ($r->hasFile('file')) {
            $invoice->file_path = $r->file('file')->store('invoices/'.$account->id, 'local');
        }
        $invoice->save();
        $invoice->setLines($v['items']);

        if ($send) {
            $sent = $this->deliver($invoice, $account->clientUsers()->pluck('email')->all(), null);

            return redirect('/portal/invoices/'.$invoice->id)->with('ok', $sent ? 'Invoice '.$invoice->number.' created and emailed' : 'Invoice '.$invoice->number.' created — this client has no login to email yet');
        }

        return redirect('/portal/invoices/'.$invoice->id)->with('ok', 'Draft '.$invoice->number.' saved. The client cannot see it until you send it.');
    }

    public function edit(Request $r, Invoice $invoice)
    {
        $this->manage($r, $invoice->account, 'billing.manage');
        abort_unless($invoice->editable(), 403, 'Paid invoices cannot be edited. Mark it unpaid first.');

        return view('portal.invoice-form', $this->formData($invoice->account, $invoice, $invoice->lines(), $invoice->number));
    }

    public function update(Request $r, Invoice $invoice)
    {
        $this->manage($r, $invoice->account, 'billing.manage');
        abort_unless($invoice->editable(), 403, 'Paid invoices cannot be edited. Mark it unpaid first.');
        $v = $this->rules($r, $invoice->account);
        $number = ($v['number'] ?? null) ?: $invoice->number;
        validator(['number' => $number], ['number' => Rule::unique('invoices', 'number')->where('agency_id', $invoice->agency_id)->ignore($invoice->id)], ['number.unique' => 'That invoice number is already used.'])->validate();
        $invoice->fill($this->fields($v));
        $invoice->number = $number;
        if ($r->hasFile('file')) {
            if ($invoice->file_path) {
                Storage::disk('local')->delete($invoice->file_path);
            }
            $invoice->file_path = $r->file('file')->store('invoices/'.$invoice->account_id, 'local');
        } elseif ($r->boolean('remove_file') && $invoice->file_path) {
            Storage::disk('local')->delete($invoice->file_path);
            $invoice->file_path = null;
        }
        $invoice->save();
        $invoice->setLines($v['items']);

        return redirect('/portal/invoices/'.$invoice->id)->with('ok', 'Invoice updated');
    }

    public function send(Request $r, Invoice $invoice)
    {
        $this->manage($r, $invoice->account, 'billing.manage');
        $v = $r->validate(['to' => 'array', 'to.*' => 'email', 'extra' => 'nullable|string|max:300', 'message' => 'nullable|string|max:1500']);
        $extra = collect(preg_split('/[\s,;]+/', (string) ($v['extra'] ?? ''), -1, PREG_SPLIT_NO_EMPTY));
        $bad = $extra->first(fn ($e) => ! filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($bad) {
            return back()->withErrors(['extra' => "“{$bad}” is not a valid email address."]);
        }
        $emails = collect($v['to'] ?? [])->merge($extra)->map(fn ($e) => strtolower(trim($e)))->unique()->values()->all();
        if (! $emails) {
            return back()->withErrors(['to' => 'Choose at least one email address to send to.']);
        }
        $this->deliver($invoice, $emails, $v['message'] ?? null);

        return back()->with('ok', 'Invoice '.$invoice->number.' emailed to '.implode(', ', $emails));
    }

    public function markPaid(Request $r, Invoice $invoice)
    {
        $this->manage($r, $invoice->account, 'billing.manage');
        $v = $r->validate(['paid_on' => 'nullable|date', 'paid_amount' => 'nullable|numeric|min:0|max:99999999', 'payment_method' => ['nullable', Rule::in(array_keys(Invoice::METHODS))], 'payment_ref' => 'nullable|string|max:120']);
        $invoice->update(['paid_on' => $v['paid_on'] ?? now()->toDateString(), 'paid_amount' => $v['paid_amount'] ?? $invoice->amount, 'payment_method' => $v['payment_method'] ?? null, 'payment_ref' => $v['payment_ref'] ?? null, 'draft' => false, 'sent_at' => $invoice->sent_at ?? now()]);

        return back()->with('ok', 'Receipt recorded');
    }

    /** Mark several invoices received in full, or delete several, in one go. */
    public function bulk(Request $r, Account $account)
    {
        $this->manage($r, $account, 'billing.manage');
        $v = $r->validate(['bulk' => ['required', Rule::in(['paid', 'delete'])], 'ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'], ['ids.required' => 'Select at least one invoice.']);
        $invoices = $account->invoices()->whereIn('id', $v['ids'])->get();
        $n = 0;
        foreach ($invoices as $i) {
            if ($v['bulk'] === 'paid' && ! $i->paid_on) {
                $i->update(['paid_on' => now()->toDateString(), 'paid_amount' => $i->amount, 'draft' => false, 'sent_at' => $i->sent_at ?? now()]);
                $n++;
            } elseif ($v['bulk'] === 'delete' && $i->editable()) {
                if ($i->file_path) {
                    Storage::disk('local')->delete($i->file_path);
                }
                $i->delete();
                $n++;
            }
        }
        $skipped = $invoices->count() - $n;

        return back()->with('ok', $n.' '.\Illuminate\Support\Str::plural('invoice', $n).($v['bulk'] === 'paid' ? ' marked as received' : ' deleted').($skipped ? ' · '.$skipped.' skipped' : ''));
    }

    public function markUnpaid(Request $r, Invoice $invoice)
    {
        $this->manage($r, $invoice->account, 'billing.manage');
        $invoice->update(['paid_on' => null, 'paid_amount' => null, 'payment_method' => null, 'payment_ref' => null, 'reminded_before_at' => null, 'reminded_overdue_at' => null]);

        return back()->with('ok', 'Marked as unpaid');
    }

    public function destroy(Request $r, Invoice $invoice)
    {
        $this->manage($r, $invoice->account, 'billing.manage');
        abort_unless($invoice->editable(), 403, 'Paid invoices cannot be deleted. Mark it unpaid first.');
        if ($invoice->file_path) {
            Storage::disk('local')->delete($invoice->file_path);
        }
        $account = $invoice->account_id;
        $invoice->delete();

        return redirect('/portal/c/'.$account.'/invoices')->with('ok', 'Invoice deleted');
    }

    /** Uploaded PDF if the team attached one, otherwise a generated copy. */
    public function pdf(Request $r, Invoice $invoice)
    {
        $this->invoice($r, $invoice);
        $name = InvoiceDoc::fileName($invoice);
        $headers = ['Content-Type' => 'application/pdf', 'Content-Disposition' => ($r->boolean('view') ? 'inline' : 'attachment').'; filename="'.$name.'"', 'X-Content-Type-Options' => 'nosniff'];

        return response(InvoiceDoc::pdfBytes($invoice), 200, $headers);
    }

    // ── helpers ──────────────────────────────────────────────

    private function deliver(Invoice $invoice, array $emails, ?string $message): int
    {
        if (! $emails) {
            return 0;
        }
        Notify::invoiceSent($invoice, $emails, $message);
        $invoice->update(['draft' => false, 'sent_at' => now()]);

        return count($emails);
    }

    private function formData(Account $account, Invoice $invoice, $items, string $number): array
    {
        $agency = $account->agency;

        return [
            'account' => $account, 'invoice' => $invoice, 'number' => $number, 'cfg' => $agency->invoiceSettings(),
            'taxes' => $agency->invoiceTaxes()->get(), 'projects' => $account->projects()->get(['id', 'name']),
            'rows' => old('items', $items->map(fn ($l) => [
                'description' => $l->description, 'details' => $l->details, 'qty' => (float) $l->qty, 'unit_price' => (float) $l->unit_price,
                'discount_type' => $l->discount_type ?: 'percent', 'discount_value' => (float) $l->discount_value, 'taxes' => $l->taxes ?? [],
            ])->all()),
        ];
    }

    private function rules(Request $r, Account $account): array
    {
        // rows left completely blank are ignored
        $r->merge(['items' => collect($r->input('items', []))->filter(fn ($l) => filled($l['description'] ?? null) || filled($l['unit_price'] ?? null))->values()->all()]);

        return $r->validate([
            'number' => 'nullable|string|max:40', 'title' => 'nullable|string|max:160',
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('account_id', $account->id)],
            'issued_on' => 'required|date', 'due_on' => 'required|date|after_or_equal:issued_on',
            'items' => 'required|array|min:1|max:60', 'items.*.description' => 'required|string|max:255', 'items.*.details' => 'nullable|string|max:500',
            'items.*.qty' => 'required|numeric|min:0.01|max:1000000', 'items.*.unit_price' => 'required|numeric|min:0|max:99999999',
            'items.*.discount_type' => ['nullable', Rule::in(['percent', 'fixed'])], 'items.*.discount_value' => 'nullable|numeric|min:0|max:99999999',
            'items.*.taxes' => 'nullable|array|max:10', 'items.*.taxes.*.name' => 'required|string|max:60', 'items.*.taxes.*.rate' => 'required|numeric|min:0|max:100',
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])], 'discount_value' => 'nullable|numeric|min:0|max:99999999',
            'terms' => 'nullable|string|max:10000', 'notes' => 'nullable|string|max:10000',
            'file' => 'nullable|file|mimes:pdf|max:'.config('portal.max_file_kb'),
        ], ['items.required' => 'Add at least one line item.', 'items.min' => 'Add at least one line item.']);
    }

    private function fields(array $v): array
    {
        return [
            'title' => $v['title'] ?? null, 'project_id' => $v['project_id'] ?? null, 'issued_on' => $v['issued_on'], 'due_on' => $v['due_on'],
            'discount_type' => $v['discount_type'], 'discount_value' => $v['discount_value'] ?? 0,
            'terms' => \App\Support\Ui::clean($v['terms'] ?? null) ?: null, 'notes' => \App\Support\Ui::clean($v['notes'] ?? null) ?: null,
        ];
    }
}
