<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Agency;
use App\Models\DocTemplate;
use App\Models\Invoice;
use App\Models\Post;
use App\Models\PostEvent;
use App\Models\Report;
use App\Models\ReviewBatch;
use App\Models\Role;
use App\Models\User;
use App\Support\DemoData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fresh start for testing the whole brief:
 *   php artisan migrate:fresh --seed --seeder=GmscoSeeder
 * Creates the platform admin, the GMSCO agency and everything needed to walk through every point of the client portal brief.
 * See GMSCO_TEST_GUIDE.txt for logins and what to check. Password for every GMSCO login: Gmsco@12345
 */
class GmscoSeeder extends Seeder
{
    private const PASSWORD = 'Gmsco@12345';

    private Agency $agency;

    private User $owner;

    public function run(): void
    {
        foreach (['posts', 'files', 'logos', 'invoices', 'reports', 'agency-logos'] as $dir) {
            Storage::disk('local')->deleteDirectory($dir);
        }

        User::firstOrCreate(['email' => config('board.admin_email')], ['name' => 'Platform Admin', 'password' => config('board.admin_password'), 'role' => 'super']);

        $this->agency = Agency::create(['name' => 'GMSCO', 'brand_color' => '#0f1e3d', 'max_workspaces' => 200, 'max_members' => 50]);
        foreach (DocTemplate::defaults() as $t) {
            $this->agency->templates()->create($t);
        }
        Role::ensureDefaults($this->agency);
        $role = fn (string $n) => Role::where('agency_id', $this->agency->id)->where('name', $n)->value('id');

        $this->agency->update(['logo' => $this->logo('agency-logos/'.$this->agency->id, 'GM', [15, 30, 61])]);
        $this->owner = $this->user('Zarnaz Owais', 'owner@gmsco.test', 'owner', $this->agency->id);
        Role::ensureDefaults($this->agency);
        $manager = $this->user('Dana Manager', 'manager@gmsco.test', 'member', $this->agency->id, $role('Manager'));
        $sara = $this->user('Sara Khan', 'sara@gmsco.test', 'member', $this->agency->id, $role('Editor'));
        $omar = $this->user('Omar Ali', 'omar@gmsco.test', 'member', $this->agency->id, $role('Editor'));
        $this->user('Vera Viewer', 'viewer@gmsco.test', 'member', $this->agency->id, $role('Viewer'));

        $this->invoiceSetup();

        $noor = $this->client('Noor Boutique', 'AED', 'Layla Noor', 'client@noor.test', [150, 110, 70], "Noor Boutique FZ-LLC\nShop 12, Souk Al Bahar\nDubai, UAE", 'TRN 100234567800003');
        $atlas = $this->client('Atlas Fitness', 'USD', 'Omar Atlas', 'client@atlas.test', [30, 90, 90], "Atlas Fitness Inc.\n88 Market Street\nSan Francisco, CA 94105", 'EIN 94-1234567');
        $pearl = $this->client('Pearl Dental', 'GBP', 'Dr. Priya Pearl', 'client@pearl.test', [60, 45, 90], "Pearl Dental Ltd\n14 Harley Street\nLondon W1G 9PF", 'VAT GB 123 4567 89');
        $noor->team()->sync([$sara->id]);
        $atlas->team()->sync([$omar->id]);
        $second = $this->user('Hamad Noor', 'hamad@noor.test', 'guest', null);
        $noor->people()->syncWithoutDetaching([$second->id => ['perm' => 'comment']]);

        $this->content($noor, 'Noor', rich: true, team: $sara);
        $this->content($atlas, 'Atlas', rich: false, team: $omar);
        $this->work($noor, $sara);
        $this->work($atlas, $omar, light: true);
        $this->billing($noor, $sara);
        $this->billing($atlas, $omar, light: true);
        $this->reports($noor, $sara);
        $this->reports($atlas, $omar, light: true);
        DemoData::seed($noor); // the Campaign board workspace
        $noor->notes()->create(['author' => 'Sara Khan', 'role' => 'team', 'body' => 'Client prefers warm photos. Shoot is booked for Thursday.']);
        $noor->notes()->create(['author' => 'Zarnaz Owais', 'role' => 'team', 'body' => 'Please keep every caption under 150 characters.']);
        Storage::disk('local')->deleteDirectory('files/tmp');
        $this->call(GmscoMoreDataSeeder::class);
        $this->command?->info('GMSCO ready. Owner: owner@gmsco.test / '.self::PASSWORD.'. See GMSCO_TEST_GUIDE.txt');
    }

    private function user(string $name, string $email, string $role, ?int $agencyId, ?int $roleId = null): User
    {
        return User::create(['name' => $name, 'email' => $email, 'password' => self::PASSWORD, 'role' => $role, 'agency_id' => $agencyId, 'role_id' => $roleId]);
    }

    private function client(string $name, string $currency, string $contact, string $email, array $rgb, string $address, string $taxId): Account
    {
        $account = Account::create([
            'agency_id' => $this->agency->id, 'name' => $name, 'currency' => $currency, 'billing_address' => $address, 'tax_id' => $taxId,
            'token' => Str::random(24), 'share_enabled' => false, 'docs' => [], 'position' => Account::count() + 1,
        ]);
        DemoData::blank($account);
        $account->update(['logo' => $this->logo('logos/'.$account->id, Str::upper(Str::substr($name, 0, 2)), $rgb)]);
        $user = $this->user($contact, $email, 'guest', null);
        $account->people()->attach($user->id, ['perm' => 'comment']);

        return $account;
    }

    /** A square logo made from initials, so the demo needs no downloads. */
    private function logo(string $dir, string $text, array $rgb): string
    {
        $im = imagecreatetruecolor(400, 400);
        imagefill($im, 0, 0, imagecolorallocate($im, ...$rgb));
        $cream = imagecolorallocate($im, 247, 242, 232);
        imagerectangle($im, 24, 24, 375, 375, $cream);
        $font = 5;
        $scale = 8;
        $small = imagecreatetruecolor(imagefontwidth($font) * strlen($text), imagefontheight($font));
        imagefill($small, 0, 0, imagecolorallocate($small, ...$rgb));
        imagestring($small, $font, 0, 0, $text, imagecolorallocate($small, 247, 242, 232));
        $w = imagesx($small) * $scale;
        $h = imagesy($small) * $scale;
        imagecopyresized($im, $small, (int) ((400 - $w) / 2), (int) ((400 - $h) / 2), 0, 0, $w, $h, imagesx($small), imagesy($small));
        ob_start();
        imagepng($im);
        $path = $dir.'/logo-'.Str::random(6).'.png';
        Storage::disk('local')->put($path, ob_get_clean());

        return $path;
    }

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
        $path = 'posts/'.$accountId.'/demo-'.Str::slug($label).'-'.Str::random(6).'.jpg';
        Storage::disk('local')->put($path, ob_get_clean());

        return $path;
    }

    // ── content: dashboard, review, calendar, notifications ──────────

    private function content(Account $a, string $brand, bool $rich, User $team): void
    {
        $client = $a->clientUsers()->first();
        $palette = [[15, 30, 61], [60, 45, 90], [150, 110, 70], [30, 90, 90], [110, 50, 60], [40, 70, 120]];
        // title, type, platform, status, date, round, caption
        $rows = [
            ['Autumn collection teaser', 'image', 'instagram', 'draft', '2026-10-30', 1, 'Autumn is here. Something warm is arriving this week.'],
            ['Behind the seams', 'carousel', 'instagram', 'draft', '2026-11-02', 1, 'Swipe to see how every piece is made, from sketch to final stitch.'],
            ['New arrivals, week 1', 'carousel', 'instagram', 'pending', '2026-10-26', 1, 'Three new pieces, one calm palette. Which is your favourite?'],
            ['Studio tour reel', 'reel', 'instagram', 'pending', '2026-10-28', 1, 'A minute inside the studio. Sound on.'],
            ['Customer story: Aisha', 'image', 'facebook', 'changes', '2026-10-24', 2, '"I wear it every week." Aisha, Dubai'],
            ['Founder note', 'image', 'linkedin', 'changes', '2026-10-29', 3, 'A few words from our founder on slow fashion.'],
            ['Weekend offer', 'image', 'instagram', 'approved', '2026-10-25', 1, 'This weekend only: complimentary gift wrapping on every order.'],
            ['Care guide', 'reel', 'tiktok', 'approved', '2026-11-04', 2, 'How to keep linen looking new, in 30 seconds.'],
            ['Summer lookbook', 'carousel', 'instagram', 'published', '2026-10-12', 1, 'The summer lookbook, in case you missed it.'],
            ['Brand film', 'reel', 'youtube', 'published', '2026-10-15', 1, 'Our story in two minutes.'],
            ['Eid greetings', 'image', 'facebook', 'published', '2026-10-05', 1, 'Warm wishes from all of us.'],
            ['Team hiring post', 'image', 'linkedin', 'published', '2026-10-08', 1, 'We are growing. Come and join the studio.'],
        ];
        if (! $rich) {
            $rows = array_slice($rows, 2, 6);
        }
        $batch = ReviewBatch::create(['account_id' => $a->id, 'token' => Str::random(40), 'created_by' => $this->owner->id, 'title' => 'October review', 'sent_at' => now()->subDays(2)]);

        foreach ($rows as $i => [$title, $type, $platform, $status, $date, $round, $caption]) {
            $n = $type === 'carousel' ? 3 : 1;
            $media = [];
            for ($k = 0; $k < $n; $k++) {
                $media[] = $this->image($a->id, $brand.' '.($i + 1).($n > 1 ? '.'.($k + 1) : ''), $palette[($i + $k) % count($palette)]);
            }
            $reelUrls = ['https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'https://vimeo.com/76979871', 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/view'];
            $post = Post::create([
                'account_id' => $a->id, 'title' => $title, 'type' => $type, 'platform' => $platform, 'caption' => $caption, 'media' => $media,
                'video_url' => $type === 'reel' ? $reelUrls[$i % 3] : null, 'scheduled_on' => $date, 'status' => $status, 'round' => $round,
                'batch_id' => $status === 'draft' ? null : $batch->id, 'created_by' => $team->id,
            ]);
            if ($status !== 'draft') {
                $this->event($post, $team, 'team', 'submitted', now()->subDays(4), 1);
            }
            if ($round > 1) {
                for ($r = 1; $r < $round; $r++) {
                    $this->event($post, $client, 'client', 'changes_requested', now()->subDays(4 - $r), $r);
                    $post->comments()->create(['user_id' => $client->id, 'author' => $client->name, 'role' => 'client', 'body' => $r === 1 ? 'Could we use a warmer photo and shorten the caption?' : 'Closer, but the logo is too small.']);
                    $post->comments()->create(['user_id' => $team->id, 'author' => $team->name, 'role' => 'team', 'body' => 'Done, uploaded a new version (round '.($r + 1).').']);
                    $this->event($post, $team, 'team', 'submitted', now()->subDays(3 - $r), $r + 1);
                }
            }
            if (in_array($status, ['approved', 'published'])) {
                $this->event($post, $client, 'client', 'approved', now()->subDays(1), $round);
                $post->comments()->create(['user_id' => $client->id, 'author' => $client->name, 'role' => 'client', 'body' => 'Looks great, approved.']);
            }
            if ($status === 'changes') {
                $post->comments()->create(['user_id' => $client->id, 'author' => $client->name, 'role' => 'client', 'body' => 'Please make the text bigger and use the blue version.']);
                $this->event($post, $client, 'client', 'changes_requested', now()->subHours(6), $round);
            }
            if ($status === 'published') {
                $this->event($post, $team, 'team', 'published', now()->subHours(2), $round);
            }
        }
    }

    private function event(Post $p, User $u, string $role, string $action, $at, int $round): void
    {
        PostEvent::create(['post_id' => $p->id, 'user_id' => $u->id, 'actor' => $u->name, 'role' => $role, 'action' => $action, 'round' => $round, 'created_at' => $at]);
    }

    // ── projects, tasks and files ────────────────────────────────────

    private function work(Account $a, User $by, bool $light = false): void
    {
        $p = $a->projects()->create(['name' => 'October Content', 'description' => 'Monthly social content plan', 'status' => 'active', 'due_on' => '2026-10-31']);
        $tasks = [['Plan content calendar', 'done', -10], ['Photoshoot', 'done', -5], ['Design posts', 'doing', -1], ['Client review', 'todo', 2], ['Schedule & publish', 'todo', 8]];
        foreach ($tasks as $i => [$t, $s, $d]) {
            $p->tasks()->create(['title' => $t, 'status' => $s, 'position' => $i, 'due_on' => now()->addDays($d)->toDateString()]);
        }
        $b = $a->projects()->create(['name' => 'Brand Identity', 'description' => 'Refresh of logo and guidelines', 'status' => 'on_hold']);
        foreach ([['Moodboard', 'done'], ['Logo concepts', 'doing'], ['Guidelines document', 'todo']] as $i => [$t, $s]) {
            $b->tasks()->create(['title' => $t, 'status' => $s, 'position' => $i]);
        }
        if (! $light) {
            $c = $a->projects()->create(['name' => 'Summer Campaign', 'description' => 'Wrapped up in September', 'status' => 'completed']);
            foreach ([['Concept', 'done'], ['Shoot', 'done'], ['Launch', 'done']] as $i => [$t, $s]) {
                $c->tasks()->create(['title' => $t, 'status' => $s, 'position' => $i]);
            }
        }

        $this->file($a, 'brand', 'Brand kit.pdf', $this->pdf($a->name.' brand kit', 'Palette: navy, cream and gold. Typeface: Playfair Display + Inter.'), $by, 'team');
        $this->file($a, 'logos', 'Logo primary.png', $this->logoBytes(), $by, 'team');
        $this->file($a, 'guidelines', 'Tone of voice.pdf', $this->pdf($a->name.' tone of voice', 'Warm, calm and clear. Short sentences. No slang.'), $by, 'team');
        $this->file($a, 'deliverables', 'October creatives.pdf', $this->pdf($a->name.' October creatives', 'Final approved creatives, ready to publish.'), $by, 'team');
        $client = $a->clientUsers()->first();
        $this->file($a, 'client', 'Raw photo shoot.jpg', Storage::disk('local')->get($this->image($a->id, 'raw photo', [90, 90, 90])), $client, 'client');
        $this->file($a, 'client', 'Reference ideas.txt', "Colours we like: sand, olive.\nAccounts we admire: @example\n", $client, 'client');
    }

    private function logoBytes(): string
    {
        return Storage::disk('local')->get($this->logo('files/tmp', 'LG', [15, 30, 61]));
    }

    private function pdf(string $title, string $body): string
    {
        return Pdf::loadHTML('<html><body style="font-family:DejaVu Sans;padding:30px"><h1>'.e($title).'</h1><p>'.e($body).'</p></body></html>')->setPaper('a4')->output();
    }

    private function file(Account $a, string $folder, string $name, string $bytes, User $by, string $role): void
    {
        $path = 'files/'.$a->id.'/'.Str::random(8).'-'.Str::slug(pathinfo($name, PATHINFO_FILENAME)).'.'.pathinfo($name, PATHINFO_EXTENSION);
        Storage::disk('local')->put($path, $bytes);
        $mime = ['pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'txt' => 'text/plain'][pathinfo($name, PATHINFO_EXTENSION)] ?? 'application/octet-stream';
        $a->files()->create(['folder' => $folder, 'name' => $name, 'path' => $path, 'size' => strlen($bytes), 'mime' => $mime, 'uploaded_by' => $by->id, 'uploader' => $by->name, 'uploader_role' => $role]);
    }

    // ── invoices ─────────────────────────────────────────────────────

    private function invoiceSetup(): void
    {
        $this->agency->update(['invoice_settings' => [
            'prefix' => 'GMS-', 'padding' => 4, 'next_number' => 1, 'due_days' => 14,
            'business_name' => 'GMSCO Marketing FZ-LLC', 'address' => "Office 504, Business Bay Tower\nDubai, United Arab Emirates", 'tax_id' => 'TRN 100987654300003',
            'email' => 'billing@gmsco.test', 'phone' => '+971 4 555 0100',
            'bank' => "Account name: GMSCO Marketing FZ-LLC\nBank: Emirates Example Bank\nIBAN AE07 0331 2345 6789 0123 456\nSWIFT EEBKAEAD",
            'terms' => 'Payment is due by the due date shown above. Please use the invoice number as the payment reference. Late payments may be charged 1.5% a month.',
            'notes' => 'Thank you for your business.', 'email_message' => 'Reply to this email if you have any questions about this invoice.',
        ]]);
        $this->agency->invoiceTaxes()->create(['name' => 'VAT', 'rate' => 5, 'is_default' => true, 'position' => 1]);
        $this->agency->invoiceTaxes()->create(['name' => 'Service charge', 'rate' => 10, 'is_default' => false, 'position' => 2]);
    }

    private function billing(Account $a, User $by, bool $light = false): void
    {
        $this->agency->refresh();
        $vat = [['name' => 'VAT', 'rate' => 5]];
        $project = $a->projects()->first();
        // title, issued, due, paid, draft, lines, discount
        $set = [
            ['September retainer', '-40 days', '-26 days', '-27 days', false, [['Social media management', 'Daily posting and community replies', 1, 3500], ['Monthly report', '', 1, 1000]], [], 'bank', 'TX-88121'],
            ['October retainer', '-8 days', '-3 days', null, false, [['Social media management', 'Daily posting and community replies', 1, 3500], ['Content calendar', 'October plan and review', 1, 1000]], [], null, null],
            ['Photoshoot day', '-1 day', '+2 days', null, false, [['Photoshoot', 'Half-day studio session', 1, 1800], ['Retouching', 'Per final image', 12, 35]], ['percent', 10], null, null],
            ['November retainer', 'today', '+20 days', null, false, [['Social media management', '', 1, 3500], ['Reels production', 'Short reels with captions', 4, 450]], [], null, null],
            ['December plan (draft)', 'today', '+30 days', null, true, [['Strategy workshop', '', 1, 2500]], [], null, null],
        ];
        if ($light) {
            $set = array_slice($set, 1, 3);
        }
        foreach ($set as $n => [$title, $issued, $due, $paid, $draft, $lines, $disc, $method, $ref]) {
            $inv = $a->invoices()->create([
                'agency_id' => $this->agency->id, 'number' => $this->agency->allocateInvoiceNumber(), 'title' => $title, 'currency' => $a->currency, 'amount' => 0,
                'project_id' => $n === 2 ? $project?->id : null, 'issued_on' => now()->modify($issued)->toDateString(), 'due_on' => now()->modify($due)->toDateString(),
                'paid_on' => $paid ? now()->modify($paid)->toDateString() : null, 'payment_method' => $method, 'payment_ref' => $ref, 'draft' => $draft,
                'sent_at' => $draft ? null : now()->modify($issued), 'discount_type' => $disc[0] ?? 'fixed', 'discount_value' => $disc[1] ?? 0,
                'terms' => $this->agency->invoiceSettings()['terms'], 'notes' => $this->agency->invoiceSettings()['notes'], 'created_by' => $by->id,
            ]);
            $inv->setLines(array_map(fn ($l) => ['description' => $l[0], 'details' => $l[1], 'qty' => $l[2], 'unit_price' => $l[3], 'taxes' => $vat], $lines));
        }
    }

    // ── reports ──────────────────────────────────────────────────────

    private function reports(Account $a, User $by, bool $light = false): void
    {
        $rows = [['2026-09-01', 48200, 4.8, 310, 'Studio tour reel'], ['2026-08-01', 39100, 4.1, 180, 'Summer lookbook'], ['2026-07-01', 31500, 3.6, 95, 'Eid greetings']];
        foreach ($light ? array_slice($rows, 0, 1) : $rows as $n => [$m, $reach, $eng, $fg, $top]) {
            $path = null;
            if ($n < 2) {
                $path = 'reports/'.$a->id.'/'.Str::random(8).'-'.substr($m, 0, 7).'.pdf';
                Storage::disk('local')->put($path, $this->pdf($a->name.' performance report '.substr($m, 0, 7), "Reach {$reach}, engagement {$eng}%, follower growth {$fg}. Top post: {$top}."));
            }
            $report = Report::create(['account_id' => $a->id, 'month' => $m, 'reach' => $reach, 'engagement' => $eng, 'follower_growth' => $fg, 'top_post' => $top, 'notes' => 'Steady growth month on month.', 'file_path' => $path, 'created_by' => $by->id]);
            $report->syncFile($by);
        }
    }
}
