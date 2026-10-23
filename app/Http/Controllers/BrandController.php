<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/** Platform logo and favicon: the file uploaded in the admin settings, or the built-in Atelo mark. */
class BrandController extends Controller
{
    public function show(string $kind)
    {
        abort_unless(in_array($kind, ['logo', 'favicon'], true), 404);
        $path = (string) (Setting::all_()[$kind.'_path'] ?? '');
        $headers = ['Cache-Control' => 'public, max-age=300', 'X-Content-Type-Options' => 'nosniff'];
        if ($path !== '' && Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path, null, $headers);
        }

        return response()->file(public_path('img/atelo-mark.png'), $headers);
    }
}
