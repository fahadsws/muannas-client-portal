<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Agency;
use App\Models\Post;
use App\Models\PostEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Extra volume for Noor Boutique so pagination, folders and the payment timeline can be tried:
 * 14 more posts, a "Campaign photos" folder with 22 images, and 10 older paid invoices.
 * Adds to existing data and is safe to run again.  php artisan db:seed --class=GmscoMoreDataSeeder
 */
class GmscoMoreDataSeeder extends Seeder
{
    public function run(): void
    {
        $agency = Agency::where('name', 'GMSCO')->first();
        $noor = $agency ? Account::where('agency_id', $agency->id)->where('name', 'Noor Boutique')->first() : null;
        if (! $noor) {
            $this->command?->warn('Run GmscoSeeder first.');

            return;
        }
        $by = User::where('email', 'sara@gmsco.test')->first() ?? User::where('agency_id', $agency->id)->first();
        $client = $noor->clientUsers()->first();

        if ($noor->posts()->count() < 24) {
            foreach (range(1, 14) as $n) {
                $post = Post::create([
                    'account_id' => $noor->id, 'title' => 'Archive post '.$n, 'type' => 'image', 'platform' => ['instagram', 'facebook', 'linkedin'][$n % 3], 'caption' => 'From the September archive.',
                    'media' => [$this->image('posts/'.$noor->id, 'archive '.$n, [40 + $n * 6, 60, 110])], 'scheduled_on' => now()->subDays(10 + $n)->toDateString(), 'status' => 'published', 'round' => 1, 'created_by' => $by->id,
                ]);
                PostEvent::create(['post_id' => $post->id, 'user_id' => $client->id, 'actor' => $client->name, 'role' => 'client', 'action' => 'approved', 'round' => 1, 'created_at' => now()->subDays(8 + $n)]);
            }
        }

        $folder = $noor->fileFolders()->firstOrCreate(['name' => 'Campaign photos']);
        $have = $noor->files()->where('folder', $folder->key())->count();
        foreach (range($have + 1, 22) as $n) {
            $bytes = Storage::disk('local')->get($tmp = $this->image('files/'.$noor->id, 'photo '.$n, [150 - $n * 3, 110, 70 + $n * 4]));
            Storage::disk('local')->delete($tmp);
            $path = 'files/'.$noor->id.'/'.Str::random(10).'-photo-'.$n.'.jpg';
            Storage::disk('local')->put($path, $bytes);
            $noor->files()->create(['folder' => $folder->key(), 'name' => 'Photo '.str_pad((string) $n, 2, '0', STR_PAD_LEFT).'.jpg', 'path' => $path, 'size' => strlen($bytes), 'mime' => 'image/jpeg', 'uploaded_by' => $by->id, 'uploader' => $by->name, 'uploader_role' => 'team']);
        }

        if ($noor->invoices()->where('title', 'like', '% retainer (archive)')->doesntExist()) {
            $vat = [['name' => 'VAT', 'rate' => 5]];
            foreach (range(1, 10) as $n) {
                $issued = now()->subMonths($n + 1)->startOfMonth();
                $invoice = $noor->invoices()->create([
                    'agency_id' => $agency->id, 'number' => $agency->allocateInvoiceNumber(), 'title' => $issued->format('F').' retainer (archive)', 'currency' => $noor->currency, 'amount' => 0,
                    'issued_on' => $issued->toDateString(), 'due_on' => $issued->copy()->addDays(14)->toDateString(), 'paid_on' => $issued->copy()->addDays(8 + $n % 5)->toDateString(),
                    'payment_method' => ['bank', 'card', 'bank', 'cash'][$n % 4], 'payment_ref' => 'TX-'.(7000 + $n), 'sent_at' => $issued, 'draft' => false, 'discount_type' => 'fixed', 'discount_value' => 0,
                    'terms' => $agency->invoiceSettings()['terms'], 'notes' => $agency->invoiceSettings()['notes'], 'created_by' => $by->id,
                ]);
                $invoice->setLines([['description' => 'Social media management', 'details' => 'Daily posting and community replies', 'qty' => 1, 'unit_price' => 3500 + $n * 50, 'taxes' => $vat]]);
            }
        }
        $this->command?->info('Extra GMSCO demo data added for Noor Boutique.');
    }

    private function image(string $dir, string $label, array $rgb): string
    {
        $im = imagecreatetruecolor(600, 600);
        for ($y = 0; $y < 600; $y++) {
            $f = $y / 600;
            imageline($im, 0, $y, 600, $y, imagecolorallocate($im, max(0, min(255, (int) ($rgb[0] * (1 - $f * .5)))), max(0, min(255, (int) ($rgb[1] * (1 - $f * .5)))), max(0, min(255, (int) ($rgb[2] * (1 - $f * .4))))));
        }
        imagestring($im, 5, 40, 290, strtoupper($label), imagecolorallocate($im, 247, 242, 232));
        ob_start();
        imagejpeg($im, null, 82);
        $path = $dir.'/demo-'.Str::slug($label).'-'.Str::random(6).'.jpg';
        Storage::disk('local')->put($path, ob_get_clean());

        return $path;
    }
}
