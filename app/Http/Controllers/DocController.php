<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DocTemplate;
use App\Support\Access;
use App\Support\DemoData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocController extends Controller
{
    /** Build the view data for a document from the request (template, workspace, filters). */
    private function build(Request $r): array
    {
        $u = $r->user();
        abort_unless($u && ! $u->isSuper(), 403);
        abort_if(in_array($u->role, ['owner', 'member']) && ! $u->hasPerm('documents.export'), 403, 'Your role does not allow generating documents.');
        $account = Account::findOrFail($r->query('account'));
        Access::need($r, $account, 'view');
        $agency = $account->agency;

        // template: a saved one, or an unsaved draft sent by the settings editor
        if ($r->filled('cfg')) {
            $cfg = json_decode($r->query('cfg'), true) ?: [];
            $name = $r->query('name', 'Draft template');
        } else {
            $t = DocTemplate::where('agency_id', $agency->id)->findOrFail($r->query('template'));
            $cfg = $t->config;
            $name = $t->name;
        }
        $cfg += ['layout' => 'normal', 'orientation' => 'portrait', 'paper' => 'a4', 'sections' => [], 'charts' => true, 'show_notes' => true, 'header' => '', 'footer' => ''];
        if ($r->has('notes')) {
            $cfg['show_notes'] = $r->boolean('notes');
        }

        $account->load('tabs.items');
        $tabs = $account->tabs->map(fn ($t) => [
            'id' => $t->id, 'label' => $t->label, 'kind' => $t->kind, 'meta' => $t->meta ?? [],
            'items' => $t->items->map(fn ($i) => ['status' => $i->status, 'locked' => $i->locked, 'data' => $i->data ?? []])->all(),
        ])->all();

        $sample = false;
        $hasItems = collect($tabs)->sum(fn ($t) => count($t['items'])) > 0;
        if ($r->boolean('sample') || (! $hasItems && ! $r->has('sample'))) {
            $tabs = DemoData::sampleTabs();
            $sample = true;
        }

        // optional filters
        if (! $sample && $r->filled('tabs')) {
            $ids = array_map('intval', explode(',', $r->query('tabs')));
            $tabs = array_values(array_filter($tabs, fn ($t) => in_array($t['id'], $ids)));
        }
        $status = $r->query('status', 'all');
        if ($status !== 'all') {
            foreach ($tabs as &$t) {
                $t['items'] = array_values(array_filter($t['items'], fn ($i) => $i['status'] === $status));
            }
            unset($t);
        }

        return [
            'agency' => $agency, 'account' => $account, 'tpl' => $name, 'cfg' => $cfg, 'tabs' => $tabs,
            'sample' => $sample, 'color' => $agency->brand_color, 'date' => now()->format('d M Y'),
            'preview' => $r->routeIs('docs.preview'),
        ];
    }

    public function preview(Request $r)
    {
        return view('docs.document', $this->build($r));
    }

    public function export(Request $r, string $format)
    {
        $d = $this->build($r);
        $d['preview'] = false;
        $file = Str::slug($d['account']->name.' '.$d['tpl']).'-'.now()->format('Ymd');

        if ($format === 'pdf') {
            return Pdf::loadView('docs.document', $d)->setPaper($d['cfg']['paper'], $d['cfg']['orientation'])->download($file.'.pdf');
        }
        if ($format === 'doc') {
            $d['word'] = true;

            return response(view('docs.document', $d)->render(), 200, [
                'Content-Type' => 'application/msword',
                'Content-Disposition' => 'attachment; filename="'.$file.'.doc"',
            ]);
        }
        abort_unless($format === 'csv', 404);

        $rows = [['Section', 'Label', 'Day', 'Hook', 'Caption', 'Status', 'Platforms', 'Time', 'Notes']];
        foreach ($d['tabs'] as $t) {
            foreach ($t['items'] as $i) {
                $x = $i['data'];
                $rows[] = [$t['label'], $x['label'] ?? '', $x['day'] ?? '', $x['hook'] ?? '', $x['caption'] ?? '', $i['status'], implode(' ', $x['platforms'] ?? []), $x['time'] ?? ($x['duration'] ?? ''), $x['notes'] ?? ''];
            }
        }
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);

        return response(stream_get_contents($out), 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="'.$file.'.csv"']);
    }
}
