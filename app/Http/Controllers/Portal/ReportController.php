<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use App\Models\Report;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends PortalController
{
    public function index(Request $r, Account $account)
    {
        $this->reach($r, $account);
        $reports = $account->reports()->get();

        return view('portal.reports', [
            'account' => $account, 'reports' => $reports,
            'manage' => $this->isStaff($r) && $this->reach($r, $account) === 'edit' && $r->user()->hasPerm('reports.manage'),
        ]);
    }

    /** One report per client per month: saving the same month again updates it. */
    public function store(Request $r, Account $account)
    {
        $this->manage($r, $account, 'reports.manage');
        $v = $r->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'], 'reach' => 'required|integer|min:0|max:4000000000',
            'engagement' => 'required|numeric|min:0|max:100', 'follower_growth' => 'required|integer|min:-100000000|max:100000000',
            'top_post' => 'nullable|string|max:200', 'notes' => 'nullable|string|max:2000',
            'file' => 'nullable|file|mimes:pdf|max:'.config('portal.max_file_kb'), 'notify' => 'nullable|boolean',
        ]);
        $month = Carbon::createFromFormat('!Y-m', $v['month'])->startOfMonth();
        $report = $account->reports()->whereDate('month', $month->toDateString())->first() ?? new Report(['account_id' => $account->id, 'month' => $month->toDateString()]);
        $isNew = ! $report->exists;

        $report->fill([
            'reach' => $v['reach'], 'engagement' => $v['engagement'], 'follower_growth' => $v['follower_growth'],
            'top_post' => $v['top_post'] ?? null, 'notes' => $v['notes'] ?? null, 'created_by' => $report->created_by ?? $r->user()->id,
        ]);
        if ($r->hasFile('file')) {
            if ($report->file_path) {
                Storage::disk('local')->delete($report->file_path);
            }
            $report->file_path = $r->file('file')->store('reports/'.$account->id, 'local');
        }
        $report->save();
        $report->syncFile($r->user());

        if ($isNew || $r->boolean('notify')) {
            Notify::reportReady($report);
        }

        return back()->with('ok', $month->format('F Y').' report '.($isNew ? 'added and the client was emailed' : 'updated'));
    }

    public function download(Request $r, Report $report)
    {
        $this->reach($r, $report->account);
        abort_unless($report->file_path, 404);

        return $this->stream($report->file_path, $report->account->name.' - '.$report->month->format('Y-m').' report.pdf', ! $r->boolean('view'));
    }

    public function destroy(Request $r, Report $report)
    {
        $this->manage($r, $report->account, 'reports.manage');
        \App\Models\ClientFile::where('ref_type', 'report')->where('ref_id', $report->id)->delete();
        if ($report->file_path) {
            Storage::disk('local')->delete($report->file_path);
        }
        $report->delete();

        return back()->with('ok', 'Report deleted');
    }
}
