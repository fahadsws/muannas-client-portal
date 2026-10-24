<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostEvent;
use App\Models\ReviewBatch;
use App\Support\Access;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentController extends PortalController
{
    // ── review grid ──────────────────────────────────────────

    public function index(Request $r, Account $account)
    {
        $this->reach($r, $account);
        $filter = array_key_exists($r->query('status'), Post::STATUSES) ? $r->query('status') : null;
        $base = $this->visiblePosts($r, $account)
            ->when($r->filled('from'), fn ($q) => $q->whereDate('scheduled_on', '>=', $r->query('from')))
            ->when($r->filled('to'), fn ($q) => $q->whereDate('scheduled_on', '<=', $r->query('to')));
        $counts = (clone $base)->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');
        $posts = $base->when($filter, fn ($q) => $q->where('status', $filter))
            ->orderByRaw('scheduled_on is null')->orderBy('scheduled_on')->orderBy('id')->paginate(24)->withQueryString();

        return view('portal.content.index', [
            'account' => $account, 'posts' => $posts, 'filter' => $filter, 'counts' => $counts,
            'manage' => $this->canManage($r, $account), 'batches' => $this->isStaff($r) ? $account->batches()->latest('id')->limit(5)->get() : collect(),
        ]);
    }

    public function show(Request $r, Post $post)
    {
        $this->post($r, $post);
        $post->load(['comments', 'events']);

        return view('portal.content.show', [
            'post' => $post, 'account' => $post->account, 'manage' => $this->canManage($r, $post->account),
            'canAct' => $this->canAct($r, $post->account), 'video' => Post::videoInfo($post->video_url),
        ]);
    }

    public function media(Request $r, Post $post, int $i)
    {
        $this->post($r, $post);
        abort_unless(isset($post->media[$i]), 404);

        return $this->stream($post->media[$i]);
    }

    public function commentImage(Request $r, \App\Models\PostComment $comment)
    {
        $this->post($r, $comment->post);
        abort_unless($comment->image, 404);

        return $this->stream($comment->image);
    }

    // ── create / edit (team) ─────────────────────────────────

    public function create(Request $r, Account $account)
    {
        $this->manage($r, $account);

        return view('portal.content.form', ['account' => $account, 'post' => null]);
    }

    public function store(Request $r, Account $account)
    {
        $this->manage($r, $account);
        $v = $this->validated($r);
        $post = $account->posts()->create([
            'title' => $v['title'], 'platform' => $v['platform'], 'caption' => $v['caption'] ?? null,
            'scheduled_on' => $v['scheduled_on'] ?? null, 'video_url' => $v['video_url'] ?? null,
            'media' => $this->saveImages($r, $account, []), 'status' => 'draft', 'round' => 1, 'created_by' => $r->user()->id,
        ]);
        $this->retype($post);

        return redirect('/portal/posts/'.$post->id)->with('ok', 'Post saved as a draft');
    }

    public function edit(Request $r, Post $post)
    {
        $this->manage($r, $post->account);

        return view('portal.content.form', ['account' => $post->account, 'post' => $post]);
    }

    public function update(Request $r, Post $post)
    {
        $this->manage($r, $post->account);
        $v = $this->validated($r);

        $media = collect($post->media ?? [])->values();
        foreach ((array) $r->input('remove', []) as $i) {
            if ($media->has((int) $i)) {
                Storage::disk('local')->delete($media[(int) $i]);
                $media->forget((int) $i);
            }
        }
        $media = $this->saveImages($r, $post->account, $media->values()->all());

        $before = [$post->title, $post->caption, $post->video_url, count($post->media ?? [])];
        $post->fill([
            'title' => $v['title'], 'platform' => $v['platform'], 'caption' => $v['caption'] ?? null,
            'scheduled_on' => $v['scheduled_on'] ?? null, 'video_url' => $v['video_url'] ?? null, 'media' => $media,
        ])->save();
        $this->retype($post);

        // changing approved work means the client must sign it off again
        $changed = $before !== [$post->title, $post->caption, $post->video_url, count($post->media ?? [])] || $r->hasFile('images');
        $reopened = $post->status === 'approved' && $changed;
        if ($reopened) {
            $this->move($post, $r, 'draft', 'reopened');
        }

        return redirect('/portal/posts/'.$post->id)->with('ok', $reopened ? 'Saved. This post was approved, so it went back to draft for a fresh sign-off.' : 'Post updated');
    }

    public function destroy(Request $r, Post $post)
    {
        $this->manage($r, $post->account);
        foreach ($post->media ?? [] as $path) {
            Storage::disk('local')->delete($path);
        }
        $account = $post->account;
        $post->delete();

        return redirect('/portal/c/'.$account->id.'/content')->with('ok', 'Post deleted');
    }

    // ── comments ─────────────────────────────────────────────

    public function comment(Request $r, Post $post)
    {
        $this->post($r, $post, 'comment');
        $v = $r->validate(['body' => 'required|string|max:2000', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:'.config('portal.max_image_kb')]);
        $this->addComment($post, $r, $v['body']);
        if (! $this->isStaff($r)) {
            Notify::clientActed($post, $r->user(), 'commented', $v['body']);
        }

        return back()->with('ok', 'Comment added');
    }

    // ── approval flow ────────────────────────────────────────

    public function approve(Request $r, Post $post)
    {
        $this->post($r, $post, 'comment');
        abort_unless($this->canAct($r, $post->account), 403, 'Only the client can approve content.');
        abort_unless($post->status === 'pending', 422, 'This post is not waiting for approval.');
        $this->move($post, $r, 'approved', 'approved');
        Notify::clientActed($post, $r->user(), 'approved', null);

        return back()->with('ok', '"'.$post->title.'" approved');
    }

    public function requestChanges(Request $r, Post $post)
    {
        $this->post($r, $post, 'comment');
        abort_unless($this->canAct($r, $post->account), 403, 'Only the client can request changes.');
        abort_unless($post->status === 'pending', 422, 'This post is not waiting for approval.');
        $v = $r->validate(['body' => 'required|string|max:2000', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:'.config('portal.max_image_kb')], ['body.required' => 'Please tell us what to change.']);
        DB::transaction(function () use ($post, $r, $v) {
            $this->addComment($post, $r, $v['body']);
            $this->move($post, $r, 'changes', 'changes_requested');
        });
        Notify::clientActed($post, $r->user(), 'requested changes', $v['body']);

        return back()->with('ok', 'Changes requested — the team has been notified');
    }

    /** Team moves: send for review, publish, un-publish, back to draft. */
    public function status(Request $r, Post $post)
    {
        $this->manage($r, $post->account);
        $do = $r->validate(['do' => ['required', Rule::in(['send', 'publish', 'unpublish', 'reopen'])]])['do'];

        if ($do === 'send') {
            abort_unless(in_array($post->status, ['draft', 'changes']), 422, 'Only drafts and posts with requested changes can be sent.');
            $this->sendBatch($r, $post->account, collect([$post]), $post->title);

            return back()->with('ok', 'Sent to the client for review');
        }
        if ($do === 'publish') {
            abort_unless($post->status === 'approved', 422, 'Only approved posts can be marked published.');
            $this->move($post, $r, 'published', 'published');
        } elseif ($do === 'unpublish') {
            abort_unless($post->status === 'published', 422);
            $this->move($post, $r, 'approved', 'reopened');
        } else {
            abort_if($post->status === 'draft', 422);
            $this->move($post, $r, 'draft', 'reopened');
        }

        return back()->with('ok', 'Status updated');
    }

    /** Send several drafts to the client as one review batch with its own shareable link. */
    public function send(Request $r, Account $account)
    {
        $this->manage($r, $account);
        $v = $r->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer', 'title' => 'nullable|string|max:120'], ['ids.required' => 'Select at least one post to send.']);
        $posts = $account->posts()->whereIn('id', $v['ids'])->whereIn('status', ['draft', 'changes'])->get();
        abort_if($posts->isEmpty(), 422, 'None of the selected posts can be sent.');
        $batch = $this->sendBatch($r, $account, $posts, $v['title'] ?? null);

        return redirect('/portal/c/'.$account->id.'/content')->with('ok', $posts->count().' '.Str::plural('post', $posts->count()).' sent for review')->with('batch', $batch->url());
    }

    // ── shareable batch link (WhatsApp) ──────────────────────

    public function batch(Request $r, string $token)
    {
        $batch = ReviewBatch::where('token', $token)->firstOrFail();
        $this->reach($r, $batch->account);
        $posts = $batch->posts()->when(! $this->isStaff($r), fn ($q) => $q->where('status', '!=', 'draft'))->get();

        return view('portal.content.batch', [
            'batch' => $batch, 'account' => $batch->account, 'posts' => $posts,
            'canAct' => $this->canAct($r, $batch->account), 'manage' => $this->canManage($r, $batch->account),
        ]);
    }

    // ── cross-client queue (team) ────────────────────────────

    /** Admin Panel: clients & team, bulk upload, and every pending approval across clients in one view. */
    public function approvals(Request $r)
    {
        abort_unless($this->isStaff($r), 403);
        $me = $r->user();
        $accounts = Access::accountsFor($me)->keyBy('id');
        $posts = Post::with('account')->whereIn('account_id', $accounts->keys())->whereIn('status', ['pending', 'changes'])
            ->orderBy('updated_at')->get()->groupBy('account_id');

        return view('portal.admin', [
            'groups' => $posts, 'accounts' => $accounts,
            'teamCount' => \App\Models\User::where('agency_id', $me->agency_id)->whereIn('role', ['owner', 'member'])->count(),
            'editable' => $accounts->filter(fn ($a) => $a->perm === 'edit'),
        ]);
    }

    // ── bulk upload ──────────────────────────────────────────

    public function bulkForm(Request $r, Account $account)
    {
        $this->manage($r, $account);

        return view('portal.content.bulk', ['account' => $account]);
    }

    public function bulkStore(Request $r, Account $account)
    {
        $this->manage($r, $account);
        $r->validate([
            'images' => 'nullable|array|max:60', 'images.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:'.config('portal.max_image_kb'),
            'csv' => 'nullable|file|mimes:csv,txt|max:1024',
        ]);
        abort_if(! $r->hasFile('images') && ! $r->hasFile('csv'), 422, 'Add some images or a CSV file first.');

        $files = collect($r->file('images', []))->keyBy(fn ($f) => Str::lower($f->getClientOriginalName()));
        $rows = $r->hasFile('csv') ? $this->parseCsv($r->file('csv')->getRealPath()) : [];
        $errors = [];
        $plans = [];
        $used = collect();

        if (count($rows) > config('portal.bulk_max_rows')) {
            return back()->withErrors(['csv' => 'A CSV can hold up to '.config('portal.bulk_max_rows').' rows at a time.']);
        }
        foreach ($rows as $n => $row) {
            $line = $n + 2;
            $title = trim($row['title'] ?? '');
            $platform = Str::lower(trim($row['platform'] ?? 'instagram')) ?: 'instagram';
            $date = null;
            if (($row['date'] ?? '') !== '') {
                $date = $this->parseDate($row['date']);
                if (! $date) {
                    $errors[] = "Row {$line}: \"{$row['date']}\" is not a date (use YYYY-MM-DD).";
                }
            }
            if ($title === '') {
                $errors[] = "Row {$line}: title is empty.";
            }
            if (! isset(Post::PLATFORMS[$platform])) {
                $errors[] = "Row {$line}: platform \"{$platform}\" is not one of ".implode(', ', array_keys(Post::PLATFORMS)).'.';
            }
            $video = trim($row['video_url'] ?? '');
            if ($video !== '' && ! Post::videoInfo($video)) {
                $errors[] = "Row {$line}: reel link must be a YouTube, Google Drive, Frame.io or Vimeo https link.";
            }
            $names = collect(preg_split('/[|;]/', (string) ($row['images'] ?? '')))->map(fn ($s) => Str::lower(trim($s)))->filter()->values();
            foreach ($names as $name) {
                if (! $files->has($name)) {
                    $errors[] = "Row {$line}: image \"{$name}\" was not among the uploaded files.";
                }
            }
            $used = $used->merge($names);
            $plans[] = ['title' => $title, 'platform' => $platform, 'caption' => trim($row['caption'] ?? '') ?: null, 'date' => $date, 'video' => $video ?: null, 'images' => $names->all()];
        }
        // images no CSV row mentioned become one draft post each
        foreach ($files->keys()->diff($used) as $name) {
            $plans[] = ['title' => Str::of(pathinfo($files[$name]->getClientOriginalName(), PATHINFO_FILENAME))->replace(['_', '-'], ' ')->title()->limit(120, '')->toString(), 'platform' => 'instagram', 'caption' => null, 'date' => null, 'video' => null, 'images' => [$name]];
        }
        if ($errors) {
            return back()->withErrors(['csv' => implode("\n", array_slice($errors, 0, 12)).(count($errors) > 12 ? "\n…and ".(count($errors) - 12).' more.' : '')]);
        }

        $made = DB::transaction(function () use ($plans, $files, $account, $r) {
            $n = 0;
            foreach ($plans as $p) {
                $media = collect($p['images'])->map(fn ($name) => $files[$name]->store('posts/'.$account->id, 'local'))->all();
                $post = $account->posts()->create([
                    'title' => $p['title'], 'platform' => $p['platform'], 'caption' => $p['caption'], 'scheduled_on' => $p['date'],
                    'video_url' => $p['video'], 'media' => $media, 'status' => 'draft', 'round' => 1, 'created_by' => $r->user()->id,
                ]);
                $this->retype($post);
                $n++;
            }

            return $n;
        });

        return redirect('/portal/c/'.$account->id.'/content?status=draft')->with('ok', $made.' '.Str::plural('draft', $made).' created. Review them, then send to the client.');
    }

    public function bulkTemplate()
    {
        $csv = "title,caption,platform,date,images,video_url\n"
            ."Launch teaser,\"Something new is coming.\",instagram,2026-11-03,teaser-1.jpg|teaser-2.jpg|teaser-3.jpg,\n"
            ."Behind the scenes reel,\"A look inside the studio.\",instagram,2026-11-05,cover.jpg,https://www.youtube.com/watch?v=dQw4w9WgXcQ\n";

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="bulk-upload-template.csv"']);
    }

    // ── helpers ──────────────────────────────────────────────

    /** Staff who can change this client's content. */
    protected function canManage(Request $r, Account $account): bool
    {
        return $this->isStaff($r) && Access::perm($r, $account, true) === 'edit';
    }

    /** Clients (not staff) who may approve / request changes. */
    protected function canAct(Request $r, Account $account): bool
    {
        $u = $r->user();

        return $u->isClient() && in_array(Access::perm($r, $account, true), ['comment', 'edit'], true);
    }

    private function validated(Request $r): array
    {
        $v = $r->validate([
            'title' => 'required|string|max:120',
            'platform' => ['required', Rule::in(array_keys(Post::PLATFORMS))],
            'caption' => 'nullable|string|max:3000',
            'scheduled_on' => 'nullable|date',
            'video_url' => 'nullable|string|max:500',
            'images' => 'nullable|array|max:'.config('portal.max_post_images'),
            'images.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:'.config('portal.max_image_kb'),
        ]);
        if (! empty($v['video_url']) && ! Post::videoInfo($v['video_url'])) {
            throw ValidationException::withMessages(['video_url' => 'Reel links must be https links from YouTube, Google Drive, Frame.io or Vimeo.']);
        }

        return $v;
    }

    private function saveImages(Request $r, Account $account, array $existing): array
    {
        foreach ($r->file('images', []) as $f) {
            if (count($existing) >= config('portal.max_post_images')) {
                break;
            }
            $existing[] = $f->store('posts/'.$account->id, 'local');
        }

        return array_values($existing);
    }

    private function retype(Post $post): void
    {
        $post->update(['type' => $post->video_url ? 'reel' : (count($post->media ?? []) > 1 ? 'carousel' : 'image')]);
    }

    private function addComment(Post $post, Request $r, string $body): PostComment
    {
        return $post->comments()->create([
            'user_id' => $r->user()->id, 'author' => $r->user()->name, 'role' => $this->isStaff($r) ? 'team' : 'client', 'body' => $body,
            'image' => $r->hasFile('image') ? $r->file('image')->store('comments/'.$post->account_id, 'local') : null,
        ]);
    }

    /** Change status and write who/when into the audit trail. */
    private function move(Post $post, Request $r, string $to, string $action): void
    {
        $post->update(['status' => $to]);
        PostEvent::create([
            'post_id' => $post->id, 'user_id' => $r->user()->id, 'actor' => $r->user()->name,
            'role' => $this->isStaff($r) ? 'team' : 'client', 'action' => $action, 'round' => $post->round,
        ]);
    }

    private function sendBatch(Request $r, Account $account, $posts, ?string $title): ReviewBatch
    {
        return DB::transaction(function () use ($r, $account, $posts, $title) {
            $batch = ReviewBatch::create([
                'account_id' => $account->id, 'token' => Str::random(40), 'created_by' => $r->user()->id,
                'title' => $title ?: 'Content review — '.now()->format('j M Y'), 'sent_at' => now(),
            ]);
            foreach ($posts as $post) {
                // round N = the Nth time this post goes to the client
                $round = $post->events()->where('action', 'submitted')->count() + 1;
                $post->update(['batch_id' => $batch->id, 'round' => $round]);
                $this->move($post, $r, 'pending', 'submitted');
            }
            Notify::contentReady($batch, $posts->count());

            return $batch;
        });
    }

    private function parseCsv(string $path): array
    {
        $h = fopen($path, 'r');
        $head = null;
        $rows = [];
        while (($cols = fgetcsv($h)) !== false) {
            if ($cols === [null] || ! array_filter($cols, fn ($c) => trim((string) $c) !== '')) {
                continue;
            }
            if ($head === null) {
                $head = array_map(fn ($c) => Str::lower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c))), $cols);

                continue;
            }
            $rows[] = array_combine($head, array_pad(array_slice($cols, 0, count($head)), count($head), ''));
        }
        fclose($h);

        return $rows;
    }

    private function parseDate(string $s): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $f) {
            try {
                $d = Carbon::createFromFormat('!'.$f, trim($s));
                if ($d && $d->format($f) === trim($s)) {
                    return $d->toDateString();
                }
            } catch (\Throwable $e) {
            }
        }

        return null;
    }
}
