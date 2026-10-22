<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperReportController extends Controller
{
    public const TYPES = [
        'summary' => 'Platform summary',
        'directory' => 'Agency directory',
        'usage' => 'Usage & limits',
        'agency' => 'Agency profile',
    ];

    private function build(Request $r): array
    {
        $type = $r->query('type', 'summary');
        abort_unless(isset(self::TYPES[$type]), 404);
        $st = Setting::all_();
        $sup = new SuperController();
        $sections = $r->filled('sections') ? explode(',', $r->query('sections')) : ['kpis', 'charts', 'table', 'notes'];
        $d = [
            'type' => $type, 'title' => self::TYPES[$type], 'brand' => $st['brand'], 'header' => $st['report_header'],
            'footer' => str_replace('{brand}', $st['brand'], $st['report_footer']), 'sections' => $sections,
            'orientation' => $r->query('orientation', 'portrait'), 'paper' => $r->query('paper', $st['report_paper']),
            'date' => now()->format('d M Y, H:i'), 'preview' => $r->routeIs('admin.report.preview'),
            'filter' => ucfirst($r->query('status', 'all')).' agencies',
        ];

        if ($type === 'summary') {
            $d['dash'] = $sup->dashboardData();
        } elseif ($type === 'agency') {
            $agency = ($r->filled('agency') ? Agency::find($r->query('agency')) : null) ?? Agency::orderBy('name')->first();
            if ($agency) {
                $d['detail'] = $sup->showData($agency);
                $d['title'] = 'Agency profile · '.$agency->name;
            } else {
                $d['detail'] = null;
            }
        } else {
            $rows = $sup->filtered($r)->get();
            $counts = SuperController::itemCounts($rows->pluck('id')->all());
            $d['rows'] = $rows->map(fn ($a) => SuperController::row($a, $counts[$a->id] ?? []))->values()->all();
        }

        return $d;
    }

    public function preview(Request $r)
    {
        return view('docs.platform', $this->build($r));
    }

    public function export(Request $r, string $format)
    {
        $d = $this->build($r);
        $d['preview'] = false;
        $file = Str::slug($d['title']).'-'.now()->format('Ymd');

        if ($format === 'pdf') {
            return Pdf::loadView('docs.platform', $d)->setPaper($d['paper'], $d['orientation'])->download($file.'.pdf');
        }
        if ($format === 'doc') {
            $d['word'] = true;

            return response(view('docs.platform', $d)->render(), 200, ['Content-Type' => 'application/msword', 'Content-Disposition' => 'attachment; filename="'.$file.'.doc"']);
        }
        abort_unless($format === 'csv', 404);

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        if ($d['type'] === 'agency') {
            fputcsv($out, ['Workspace', 'Items', 'Completion %', 'Link sharing', 'Created']);
            foreach (($d['detail']['workspaces'] ?? []) as $w) {
                fputcsv($out, [$w['name'], $w['items'], $w['pct'], $w['shared'] ? 'On' : 'Off', $w['created']]);
            }
        } elseif ($d['type'] === 'summary') {
            fputcsv($out, ['Agency', 'Status', 'Workspaces', 'Team', 'Items', 'Completion %']);
            foreach ($d['dash']['top'] as $a) {
                fputcsv($out, [$a['name'], $a['status'], $a['workspaces'], $a['members'], $a['items'], $a['pct']]);
            }
        } else {
            fputcsv($out, ['Agency', 'Owner', 'Email', 'Status', 'Workspaces', 'Workspace limit', 'Team', 'Team limit', 'Items', 'Completion %', 'Created']);
            foreach ($d['rows'] as $a) {
                fputcsv($out, [$a['name'], $a['owner'], $a['email'], $a['status'], $a['workspaces'], $a['max_workspaces'], $a['members'], $a['max_members'], $a['items'], $a['pct'], $a['created']]);
            }
        }
        rewind($out);

        return response(stream_get_contents($out), 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="'.$file.'.csv"']);
    }
}
