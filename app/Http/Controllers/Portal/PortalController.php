<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Post;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Shared rules for every client-portal page. Portal URLs never accept the legacy public share token. */
abstract class PortalController extends Controller
{
    /** Abort unless the caller can reach this client workspace at the given level. */
    protected function reach(Request $r, Account $account, string $need = 'view'): string
    {
        return Access::need($r, $account, $need, true);
    }

    /** Muannas staff who may change this client's content (and, optionally, hold an extra permission). */
    protected function manage(Request $r, Account $account, ?string $perm = null): void
    {
        $this->reach($r, $account, 'edit');
        $u = $r->user();
        abort_unless($u->isStaff() && ($perm === null || $u->hasPerm($perm)), 403, 'Your role does not allow that.');
    }

    protected function isStaff(Request $r): bool
    {
        return (bool) $r->user()?->isStaff();
    }

    /** Clients never see work the team has not sent for review yet. */
    protected function visiblePosts(Request $r, Account $account)
    {
        return $account->posts()->when(! $this->isStaff($r), fn ($q) => $q->where('status', '!=', 'draft'));
    }

    /** Load a post and make sure the caller may reach its client (and, for clients, that it is not a draft). */
    protected function post(Request $r, Post $post, string $need = 'view'): Post
    {
        $this->reach($r, $post->account, $need);
        abort_if(! $this->isStaff($r) && $post->status === 'draft', 404);

        return $post;
    }

    protected function keep(Request $r, string $key, string $dir, int $accountId): string
    {
        return $r->file($key)->store($dir.'/'.$accountId, 'local');
    }

    /** Stream a private file inline or as a download, never letting the browser guess its type. */
    protected function stream(string $path, ?string $name = null, bool $download = false)
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=3600'];
        $mime = Storage::disk('local')->mimeType($path);
        // anything that could run script in our origin is always forced to download
        $inlineOk = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'], true);

        return $download || ! $inlineOk
            ? Storage::disk('local')->download($path, $name ?? basename($path), $headers)
            : Storage::disk('local')->response($path, $name ?? basename($path), $headers + ['Content-Type' => $mime]);
    }

    protected function clientName(Request $r): string
    {
        return $r->user()->name;
    }
}
