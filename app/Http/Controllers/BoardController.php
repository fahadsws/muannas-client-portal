<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Item;
use App\Models\Note;
use App\Models\Tab;
use App\Support\Access;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    private const STATUSES = ['todo', 'ready', 'done', 'live'];

    private const NOTE_PAGE = 10;

    // ── Pages ────────────────────────────────────────────────

    public function page(Request $r)
    {
        $u = $r->user();
        if ($u->isSuper()) {
            return redirect('/admin');
        }
        $accounts = Access::accountsFor($u)->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'perm' => $a->perm])->values();
        $agency = $u->agency;

        return view('board', [
            'mode' => 'app', 'token' => null, 'accounts' => $accounts,
            'me' => ['name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'perms' => $u->permList(), 'role_name' => $u->role === 'owner' ? 'Owner' : ($u->agencyRole?->name ?? 'Guest'), 'impersonating' => (bool) session('impersonator')],
            'agency' => $agency ? ['id' => $agency->id, 'name' => $agency->name, 'color' => $agency->brand_color] : ['id' => 0, 'name' => $u->name, 'color' => '#7c3aed'],
        ]);
    }

    public function share(string $token)
    {
        $a = Account::where('token', $token)->where('share_enabled', true)->firstOrFail();
        abort_unless($a->agency->status === 'active', 404);

        return view('board', [
            'mode' => 'client', 'token' => $token,
            'accounts' => collect([['id' => $a->id, 'name' => $a->name, 'perm' => $a->share_perm]]),
            'me' => ['name' => 'Guest', 'email' => '', 'role' => 'client', 'perms' => [], 'role_name' => 'Client', 'impersonating' => false],
            'agency' => ['id' => $a->agency->id, 'name' => $a->agency->name, 'color' => $a->agency->brand_color],
        ]);
    }

    // ── helpers ──────────────────────────────────────────────

    private function payload(Account $a, string $perm): array
    {
        $a->load(['tabs.items']);
        $recent = $a->notes()->limit(self::NOTE_PAGE)->get()->reverse()->values();
        $out = [
            'id' => $a->id, 'name' => $a->name, 'perm' => $perm, 'docs' => $a->docs ?? [],
            'tabs' => $a->tabs->map(fn ($t) => [
                'id' => $t->id, 'label' => $t->label, 'kind' => $t->kind, 'meta' => $t->meta,
                'items' => $t->items->map(fn ($i) => $this->itemOut($i))->values(),
            ])->values(),
            'notes' => $recent->map(fn ($n) => $this->noteOut($n))->values(),
            'notes_total' => $a->notes()->count(),
        ];
        if ($perm === 'edit') {
            $out['share'] = ['enabled' => $a->share_enabled, 'perm' => $a->share_perm, 'token' => $a->token];
        }

        return $out;
    }

    private function noteOut(Note $n): array
    {
        return ['id' => $n->id, 'author' => $n->author, 'role' => $n->role, 'body' => $n->body, 'at' => $n->created_at->format('d M, g:i A')];
    }

    /** Older notes, newest first window: GET /accounts/{id}/notes?before=<note id>&limit=10 */
    public function notes(Request $r, Account $account)
    {
        Access::need($r, $account, 'view');
        $limit = min(30, max(1, (int) $r->query('limit', self::NOTE_PAGE)));
        $q = $account->notes()->when($r->filled('before'), fn ($x) => $x->where('id', '<', (int) $r->query('before')));
        $batch = (clone $q)->limit($limit)->get();
        $oldest = $batch->last();

        return [
            'notes' => $batch->reverse()->map(fn ($n) => $this->noteOut($n))->values(),
            'remaining' => $oldest ? $account->notes()->where('id', '<', $oldest->id)->count() : 0,
            'total' => $account->notes()->count(),
        ];
    }

    public function itemOut(Item $i): array
    {
        return [
            'id' => $i->id, 'tab_id' => $i->tab_id, 'position' => $i->position, 'col' => $i->col,
            'status' => $i->status, 'locked' => $i->locked,
            'image' => $i->image ? asset($i->image) : null, 'data' => $i->data ?? [],
        ];
    }

    // ── API ──────────────────────────────────────────────────

    public function show(Request $r, Account $account)
    {
        return $this->payload($account, Access::need($r, $account, 'view'));
    }

    public function addTab(Request $r, Account $account)
    {
        Access::need($r, $account);
        $v = $r->validate(['label' => 'required|string|max:40', 'kind' => 'nullable|in:posts,grid,reels,podcasts']);
        $kind = $v['kind'] ?? 'posts';
        $meta = ['title' => $v['label'], 'subtitle' => match ($kind) { 'reels' => 'Short-form video', 'podcasts' => 'Episodes', default => 'Campaign posts' }];
        if ($kind === 'grid') {
            $meta['columns'] = ['Column one', 'Column two', 'Column three'];
        }
        $tab = $account->tabs()->create([
            'label' => $v['label'], 'kind' => $kind, 'position' => ($account->tabs()->max('position') ?? 0) + 1, 'meta' => $meta,
        ]);

        return ['payload' => $this->payload($account, 'edit'), 'tab' => $tab->id];
    }

    public function updateTab(Request $r, Tab $tab)
    {
        Access::need($r, $tab->account);
        $upd = [];
        if ($r->filled('label')) {
            $upd['label'] = mb_substr($r->input('label'), 0, 40);
        }
        if ($r->has('meta')) {
            $upd['meta'] = array_merge($tab->meta ?? [], (array) $r->input('meta'));
        }
        $tab->update($upd);

        return ['id' => $tab->id, 'label' => $tab->label, 'meta' => $tab->meta];
    }

    public function deleteTab(Request $r, Tab $tab)
    {
        $account = $tab->account;
        Access::need($r, $account);
        abort_if($tab->kind === 'strategy', 422, 'The strategy tab cannot be removed.');
        $tab->delete();

        return $this->payload($account, 'edit');
    }

    public function addItem(Request $r, Tab $tab)
    {
        Access::need($r, $tab->account);
        $pos = ($tab->items()->max('position') ?? -1) + 1;
        $made = [];
        if ($tab->kind === 'grid') {
            foreach ([0, 1, 2] as $c) {
                $made[] = $tab->items()->create(['position' => $pos, 'col' => $c, 'data' => [
                    'label' => sprintf('POST %02d · %s', $pos + 1, strtoupper(mb_substr(($tab->meta['columns'][$c] ?? 'Col '.($c + 1)), 0, 10))),
                    'caption' => 'New post — click edit', 'platforms' => ['IG', 'LI'], 'time' => '9:00 AM', 'notes' => '',
                ]]);
            }
        } else {
            $base = match ($tab->kind) {
                'reels' => ['label' => sprintf('REEL %02d', $pos + 1), 'format' => 'CASUAL CONVO', 'caption' => 'New reel — click edit', 'notes' => ''],
                'podcasts' => ['label' => sprintf('EP %02d', $pos + 1), 'caption' => 'New episode — click edit', 'duration' => '10–12 min', 'notes' => ''],
                default => ['label' => sprintf('POST %02d', $pos + 1), 'caption' => 'New post — click edit', 'platforms' => ['IG', 'LI'], 'time' => '9:00 AM', 'notes' => ''],
            };
            $made[] = $tab->items()->create(['position' => $pos, 'data' => $base]);
        }

        return collect($made)->map(fn ($i) => $this->itemOut($i));
    }

    public function updateItem(Request $r, Item $item)
    {
        $perm = Access::need($r, $item->tab->account, 'comment');

        if ($perm !== 'edit') { // commenters may only change the notes field
            $item->update(['data' => array_merge($item->data ?? [], ['notes' => mb_substr((string) $r->input('data.notes', ''), 0, 2000)])]);

            return $this->itemOut($item);
        }

        $upd = [];
        if ($r->has('status')) {
            $upd['status'] = in_array($r->status, self::STATUSES) ? $r->status : 'todo';
        }
        if ($r->has('locked')) {
            $upd['locked'] = (bool) $r->locked;
        }
        if ($r->has('data')) {
            abort_if($item->locked && array_keys((array) $r->data) !== ['notes'], 423, 'This item is locked.');
            $upd['data'] = array_merge($item->data ?? [], (array) $r->input('data'));
        }
        $item->update($upd);

        return $this->itemOut($item);
    }

    public function deleteItem(Request $r, Item $item)
    {
        Access::need($r, $item->tab->account);
        abort_if($item->locked, 423, 'This item is locked.');
        if ($item->tab->kind === 'grid') {
            $ids = Item::where('tab_id', $item->tab_id)->where('position', $item->position)->pluck('id');
            Item::whereIn('id', $ids)->delete();

            return ['deleted' => $ids];
        }
        $item->delete();

        return ['deleted' => [$item->id]];
    }

    public function upload(Request $r, Item $item)
    {
        Access::need($r, $item->tab->account);
        $r->validate(['image' => 'required|image|max:8192']);
        $f = $r->file('image');
        $name = $item->id.'-'.time().'.'.$f->getClientOriginalExtension();
        $f->move(public_path('uploads'), $name);
        $item->update(['image' => 'uploads/'.$name]);

        return $this->itemOut($item);
    }

    public function addNote(Request $r, Account $account)
    {
        $perm = Access::need($r, $account, 'comment');
        $v = $r->validate(['body' => 'required|string|max:2000', 'author' => 'nullable|string|max:60']);
        $u = $r->user();
        $note = $account->notes()->create([
            'author' => $u ? $u->name : ($v['author'] ?? 'Client'),
            'role' => $perm === 'edit' ? 'team' : 'client',
            'body' => $v['body'],
        ]);

        return ['note' => $this->noteOut($note), 'total' => $account->notes()->count()];
    }

    public function deleteNote(Request $r, Note $note)
    {
        Access::need($r, Account::findOrFail($note->account_id));
        $note->delete();

        return ['ok' => true];
    }

    public function setDocs(Request $r, Account $account)
    {
        Access::need($r, $account);
        $docs = collect($r->input('docs', []))->map(fn ($d) => [
            'title' => mb_substr((string) ($d['title'] ?? 'Doc'), 0, 40),
            'url' => (string) ($d['url'] ?? '#'),
        ])->filter(fn ($d) => preg_match('#^https?://#i', $d['url']))->values()->all();
        $account->update(['docs' => $docs]);

        return $docs;
    }
}
