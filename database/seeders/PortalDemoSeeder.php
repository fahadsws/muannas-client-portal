<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Agency;
use App\Models\DocTemplate;
use App\Models\Post;
use App\Models\PostEvent;
use App\Models\Report;
use App\Models\ReviewBatch;
use App\Models\Role;
use App\Models\User;
use App\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data for trying the portal out. NOT for production.
 *   php artisan db:seed --class=PortalDemoSeeder
 * Logins (password for all: Portal@12345):
 *   admin@muannas.test (Admin), team@muannas.test (Team member, Noor only),
 *   client@noor.test and client@atlas.test (Clients)
 */
class PortalDemoSeeder extends Seeder
{
    private const PASSWORD = 'Portal@12345';

    public function run(): void
    {
        $agency = Agency::firstOrCreate(['name' => 'Muannas Atelier'], ['brand_color' => '#0f1e3d', 'max_workspaces' => 200, 'max_members' => 50]);
        if ($agency->templates()->doesntExist()) {
            foreach (DocTemplate::defaults() as $t) {
                $agency->templates()->create($t);
            }
        }
        Role::ensureDefaults($agency);
        $editor = Role::where('agency_id', $agency->id)->where('name', 'Editor')->first();

        $admin = $this->user('Zarnaz Owais', 'admin@muannas.test', 'owner', $agency->id);
        Role::ensureDefaults($agency);
        $team = $this->user('Sara Khan', 'team@muannas.test', 'member', $agency->id, $editor->id);

        $noor = $this->client($agency, 'Noor Boutique', 'Layla Noor', 'client@noor.test');
        $atlas = $this->client($agency, 'Atlas Fitness', 'Omar Atlas', 'client@atlas.test');
        $noor->team()->syncWithoutDetaching([$team->id]);

        $this->content($noor, $admin, 'Noor');
        $this->content($atlas, $admin, 'Atlas', light: true);
        $this->extras($noor, $admin);
        $this->command?->info('Demo ready. Sign in with admin@muannas.test / '.self::PASSWORD);
    }

    private function user(string $name, string $email, string $role, ?int $agencyId, ?int $roleId = null): User
    {
        return User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => self::PASSWORD, 'role' => $role, 'agency_id' => $agencyId, 'role_id' => $roleId]);
    }

    private function client(Agency $agency, string $name, string $contact, string $email): Account
    {
        $account = Account::firstOrCreate(['agency_id' => $agency->id, 'name' => $name], ['token' => Str::random(24), 'share_enabled' => false, 'docs' => []]);
        if ($account->tabs()->doesntExist()) {
            DemoData::blank($account);
        }
        $user = $this->user($contact, $email, 'guest', null);
        $account->people()->syncWithoutDetaching([$user->id => ['perm' => 'comment']]);

        return $account;
    }

    /** A plain branded placeholder image, so the demo needs no downloads. */
    private function image(int $accountId, string $label, array $rgb): string
    {
        $im = imagecreatetruecolor(900, 900);
        for ($y = 0; $y < 900; $y++) {
            $f = $y / 900;
            imageline($im, 0, $y, 900, $y, imagecolorallocate($im, (int) ($rgb[0] * (1 - $f * .5)), (int) ($rgb[1] * (1 - $f * .5)), (int) ($rgb[2] * (1 - $f * .4))));
        }
        $w = imagecolorallocate($im, 247, 242, 232);
        imagerectangle($im, 60, 60, 840, 840, $w);
        imagestring($im, 5, 90, 420, strtoupper($label), $w);
        ob_start();
        imagejpeg($im, null, 85);
        $bin = ob_get_clean();
        $path = 'posts/'.$accountId.'/demo-'.Str::slug($label).'-'.Str::random(6).'.jpg';
        Storage::disk('local')->put($path, $bin);

        return $path;
    }

    private function content(Account $a, User $by, string $brand, bool $light = false): void
    {
        if ($a->posts()->exists()) {
            return;
        }
        $palette = [[15, 30, 61], [60, 45, 90], [150, 110, 70], [30, 90, 90], [110, 50, 60], [40, 70, 120]];
        $rows = [
            ['Autumn collection teaser', 'image', 'draft', '2026-10-30', 'Autumn is here. Something warm is arriving this week.'],
            ['Behind the seams', 'carousel', 'draft', '2026-11-02', 'Swipe to see how every piece is made, from sketch to final stitch.'],
            ['New arrivals — week 1', 'carousel', 'pending', '2026-10-26', 'Three new pieces, one calm palette. Which is your favourite?'],
            ['Studio tour reel', 'reel', 'pending', '2026-10-28', 'A minute inside the studio. Sound on.'],
            ['Customer story: Aisha', 'image', 'changes', '2026-10-24', "\"I wear it every week.\" — Aisha, Dubai"],
            ['Weekend offer', 'image', 'approved', '2026-10-25', 'This weekend only: complimentary gift wrapping on every order.'],
            ['Founder note', 'image', 'approved', '2026-10-27', 'A few words from our founder on slow fashion.'],
            ['Summer lookbook', 'carousel', 'published', '2026-10-12', 'The summer lookbook, in case you missed it.'],
            ['Care guide', 'image', 'published', '2026-10-15', 'How to keep linen looking new.'],
            ['Eid greetings', 'image', 'published', '2026-10-05', 'Warm wishes from all of us.'],
        ];
        if ($light) {
            $rows = array_slice($rows, 2, 5);
        }
        $batch = ReviewBatch::create(['account_id' => $a->id, 'token' => Str::random(40), 'created_by' => $by->id, 'title' => 'October review', 'sent_at' => now()->subDays(2)]);

        foreach ($rows as $i => [$title, $type, $status, $date, $caption]) {
            $n = $type === 'carousel' ? 3 : 1;
            $media = [];
            for ($k = 0; $k < $n; $k++) {
                $media[] = $this->image($a->id, $brand.' '.($i + 1).($n > 1 ? '.'.($k + 1) : ''), $palette[($i + $k) % count($palette)]);
            }
            $post = Post::create([
                'account_id' => $a->id, 'title' => $title, 'type' => $type, 'platform' => 'instagram', 'caption' => $caption, 'media' => $media,
                'video_url' => $type === 'reel' ? 'https://www.youtube.com/watch?v=aqz-KE-bpKQ' : null,
                'scheduled_on' => $date, 'status' => $status, 'round' => $status === 'changes' ? 1 : ($status === 'draft' ? 1 : 1),
                'batch_id' => $status === 'draft' ? null : $batch->id, 'created_by' => $by->id,
            ]);
            if ($status !== 'draft') {
                $this->event($post, $by, 'team', 'submitted', now()->subDays(2));
            }
            if (in_array($status, ['approved', 'published'])) {
                $this->event($post, $a->clientUsers()->first(), 'client', 'approved', now()->subDay());
            }
            if ($status === 'changes') {
                $client = $a->clientUsers()->first();
                $post->comments()->create(['user_id' => $client->id, 'author' => $client->name, 'role' => 'client', 'body' => 'Could we use a warmer photo and shorten the quote?']);
                $this->event($post, $client, 'client', 'changes_requested', now()->subDay());
            }
            if ($status === 'published') {
                $this->event($post, $by, 'team', 'published', now());
            }
        }
    }

    private function event(Post $p, User $u, string $role, string $action, $at): void
    {
        PostEvent::create(['post_id' => $p->id, 'user_id' => $u->id, 'actor' => $u->name, 'role' => $role, 'action' => $action, 'round' => $p->round, 'created_at' => $at]);
    }

    private function extras(Account $a, User $by): void
    {
        if ($a->projects()->exists()) {
            return;
        }
        $p = $a->projects()->create(['name' => 'October Content', 'description' => 'Monthly social content plan', 'due_on' => '2026-10-31']);
        foreach ([['Plan content calendar', 'done'], ['Photoshoot', 'done'], ['Design posts', 'doing'], ['Client review', 'todo'], ['Schedule & publish', 'todo']] as $i => [$t, $s]) {
            $p->tasks()->create(['title' => $t, 'status' => $s, 'position' => $i, 'due_on' => now()->addDays($i * 3)->toDateString()]);
        }
        $b = $a->projects()->create(['name' => 'Brand Identity', 'description' => 'Refresh of logo and guidelines']);
        foreach ([['Moodboard', 'done'], ['Logo concepts', 'doing'], ['Guidelines document', 'todo']] as $i => [$t, $s]) {
            $b->tasks()->create(['title' => $t, 'status' => $s, 'position' => $i]);
        }

        $path = 'files/'.$a->id.'/demo-guidelines.txt';
        Storage::disk('local')->put($path, "Noor Boutique — brand notes\nPalette: cream, navy, gold.\n");
        $a->files()->create(['folder' => 'guidelines', 'name' => 'Brand notes.txt', 'path' => $path, 'size' => 60, 'mime' => 'text/plain', 'uploaded_by' => $by->id, 'uploader' => $by->name, 'uploader_role' => 'team']);

        $inv = [['INV-2026-0001', 'September retainer', 4500, '-40 days', '-26 days', '-27 days'], ['INV-2026-0002', 'October retainer', 4500, '-8 days', '-1 day', null],
            ['INV-2026-0003', 'Photoshoot', 2200, '-1 day', '+2 days', null], ['INV-2026-0004', 'November retainer', 4500, 'today', '+20 days', null]];
        foreach ($inv as [$no, $t, $amt, $issued, $due, $paid]) {
            $a->invoices()->create(['number' => $no, 'title' => $t, 'amount' => $amt, 'currency' => 'AED', 'issued_on' => now()->modify($issued)->toDateString(), 'due_on' => now()->modify($due)->toDateString(), 'paid_on' => $paid ? now()->modify($paid)->toDateString() : null, 'created_by' => $by->id]);
        }

        foreach ([['2026-09-01', 48200, 4.8, 310, 'Studio tour reel'], ['2026-08-01', 39100, 4.1, 180, 'Summer lookbook']] as [$m, $reach, $eng, $fg, $top]) {
            Report::create(['account_id' => $a->id, 'month' => $m, 'reach' => $reach, 'engagement' => $eng, 'follower_growth' => $fg, 'top_post' => $top, 'created_by' => $by->id]);
        }
    }
}
