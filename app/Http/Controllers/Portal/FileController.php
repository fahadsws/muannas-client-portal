<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use App\Models\ClientFile;
use App\Models\FileFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Files & Assets: a grid of folders; opening one lists its files (paginated). */
class FileController extends PortalController
{
    private const PER_PAGE = 20;

    private function isTeam(Request $r, Account $account): bool
    {
        return $this->isStaff($r) && $this->reach($r, $account) === 'edit';
    }

    /** Resolve a folder key (system key or "c{id}") to its label and, for custom folders, the model. */
    private function folder(Account $account, string $key): array
    {
        if (array_key_exists($key, ClientFile::FOLDERS)) {
            return [ClientFile::FOLDERS[$key], null];
        }
        abort_unless(preg_match('/^c(\d+)$/', $key, $m), 404);
        $f = $account->fileFolders()->whereKey((int) $m[1])->firstOrFail();

        return [$f->name, $f];
    }

    public function index(Request $r, Account $account)
    {
        $this->reach($r, $account);
        $team = $this->isTeam($r, $account);
        $counts = $account->files()->selectRaw('folder, count(*) n, max(created_at) latest')->groupBy('folder')->get()->keyBy('folder');
        $folders = collect(ClientFile::FOLDERS)->map(fn ($label, $key) => ['key' => $key, 'name' => $label, 'custom' => null, 'n' => (int) ($counts[$key]->n ?? 0), 'latest' => $counts[$key]->latest ?? null])
            ->merge($account->fileFolders()->get()->mapWithKeys(fn ($f) => [$f->key() => ['key' => $f->key(), 'name' => $f->name, 'custom' => $f, 'n' => (int) ($counts[$f->key()]->n ?? 0), 'latest' => $counts[$f->key()]->latest ?? null]]))
            // clients see a system folder only when it has something in it (their own folder is always there)
            ->filter(fn ($f) => $team || $f['custom'] || $f['n'] > 0 || $f['key'] === 'client')
            ->values();

        return view('portal.files', ['account' => $account, 'folders' => $folders, 'staff' => $team, 'canUpload' => $team || $r->user()->isClient()]);
    }

    public function show(Request $r, Account $account, string $folder)
    {
        $this->reach($r, $account);
        [$label, $custom] = $this->folder($account, $folder);
        $team = $this->isTeam($r, $account);
        $files = $account->files()->where('folder', $folder)->paginate(self::PER_PAGE)->withQueryString();
        $canUpload = $folder !== 'reports' && ($team || ($folder === 'client' && $r->user()->isClient()));

        return view('portal.files-folder', ['account' => $account, 'key' => $folder, 'label' => $label, 'custom' => $custom, 'files' => $files, 'staff' => $team, 'canUpload' => $canUpload]);
    }

    public function store(Request $r, Account $account)
    {
        $perm = $this->reach($r, $account, 'comment');
        $staff = $this->isStaff($r);
        abort_unless($staff ? $perm === 'edit' : $r->user()->isClient(), 403);
        $v = $r->validate([
            'folder' => 'nullable|string', 'files' => 'required|array|max:20', 'files.*' => 'file|max:'.config('portal.max_file_kb'),
        ], ['files.required' => 'Choose at least one file.']);
        // clients can only add to their own folder; nothing is uploaded by hand into Reports
        $folder = 'client';
        if ($staff && ! empty($v['folder']) && $v['folder'] !== 'reports') {
            $this->folder($account, $v['folder']); // aborts for unknown folders
            $folder = $v['folder'];
        }

        $bad = [];
        foreach ($r->file('files') as $f) {
            $ext = Str::lower($f->getClientOriginalExtension());
            if (! in_array($ext, ClientFile::ALLOWED, true)) {
                $bad[] = $f->getClientOriginalName();

                continue;
            }
            $path = $f->storeAs('files/'.$account->id, Str::random(32).'.'.$ext, 'local');
            $account->files()->create([
                'folder' => $folder, 'name' => Str::limit($f->getClientOriginalName(), 200, ''), 'path' => $path,
                'size' => $f->getSize(), 'mime' => $f->getClientMimeType(), 'uploaded_by' => $r->user()->id,
                'uploader' => $r->user()->name, 'uploader_role' => $staff ? 'team' : 'client',
            ]);
        }

        return $bad
            ? back()->withErrors(['files' => 'Not uploaded (file type not allowed): '.implode(', ', $bad)])
            : back()->with('ok', 'Uploaded');
    }

    // ── custom folders (team only) ───────────────────────────

    public function storeFolder(Request $r, Account $account)
    {
        $this->manage($r, $account);
        $v = $r->validate(['name' => 'required|string|max:80']);
        $name = trim($v['name']);
        if ($account->fileFolders()->where('name', $name)->exists() || in_array(Str::lower($name), array_map('strtolower', ClientFile::FOLDERS), true)) {
            return back()->withErrors(['name' => 'A folder called “'.$name.'” already exists.'])->withInput();
        }
        $folder = $account->fileFolders()->create(['name' => $name]);

        return redirect('/portal/c/'.$account->id.'/files/folder/'.$folder->key())->with('ok', 'Folder created');
    }

    public function updateFolder(Request $r, FileFolder $folder)
    {
        $this->manage($r, $folder->account);
        $v = $r->validate(['name' => 'required|string|max:80']);
        $name = trim($v['name']);
        if ($folder->account->fileFolders()->where('name', $name)->where('id', '!=', $folder->id)->exists() || in_array(Str::lower($name), array_map('strtolower', ClientFile::FOLDERS), true)) {
            return back()->withErrors(['name' => 'A folder called “'.$name.'” already exists.']);
        }
        $folder->update(['name' => $name]);

        return back()->with('ok', 'Folder renamed');
    }

    public function destroyFolder(Request $r, FileFolder $folder)
    {
        $this->manage($r, $folder->account);
        if ($folder->account->files()->where('folder', $folder->key())->exists()) {
            return back()->with('err', 'Empty the folder first: move or delete its files, then delete it.');
        }
        $account = $folder->account_id;
        $folder->delete();

        return redirect('/portal/c/'.$account.'/files')->with('ok', 'Folder deleted');
    }

    // ── single files ─────────────────────────────────────────

    public function download(Request $r, ClientFile $file)
    {
        $this->reach($r, $file->account);

        return $this->stream($file->path, $file->name, ! $r->boolean('view'));
    }

    public function destroy(Request $r, ClientFile $file)
    {
        $this->reach($r, $file->account, 'comment');
        $own = $r->user()->isClient() && $file->uploaded_by === $r->user()->id;
        abort_unless($own || ($this->isStaff($r) && $this->reach($r, $file->account) === 'edit'), 403);
        if ($file->isLinked()) {
            return back()->with('err', 'This file belongs to a report. Delete or replace it from Performance Reports.');
        }
        Storage::disk('local')->delete($file->path);
        $file->delete();

        return back()->with('ok', 'File deleted');
    }
}
