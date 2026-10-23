<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];

    protected $casts = ['media' => 'array', 'scheduled_on' => 'date'];

    public const STATUSES = [
        'draft' => ['Draft', 'Team still working on it'],
        'pending' => ['Pending Approval', 'Sent to client for review'],
        'changes' => ['Changes Requested', 'Client asked for edits'],
        'approved' => ['Approved', 'Signed off by client'],
        'published' => ['Published', 'Live on the platform'],
    ];

    public const PLATFORMS = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X'];

    /** Reels are shared by link only — no video uploads. */
    public const VIDEO_HOSTS = ['youtube.com', 'youtu.be', 'drive.google.com', 'frame.io', 'f.io', 'vimeo.com'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function batch()
    {
        return $this->belongsTo(ReviewBatch::class, 'batch_id');
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class)->orderBy('id');
    }

    public function events()
    {
        return $this->hasMany(PostEvent::class)->orderBy('id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? ucfirst($this->status);
    }

    public function platformLabel(): string
    {
        return self::PLATFORMS[$this->platform] ?? ucfirst($this->platform);
    }

    /** First image, used for grid tiles and calendar chips. */
    public function cover(): ?string
    {
        return ($this->media[0] ?? null) ? route('portal.media', [$this, 0]) : null;
    }

    public function mediaUrls(): array
    {
        return collect($this->media ?? [])->keys()->map(fn ($i) => route('portal.media', [$this, $i]))->all();
    }

    /** Host-checked embed target for a reel link: ['embed' => iframe src|null, 'open' => link]. */
    public static function videoInfo(?string $url): ?array
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https://#i', $url)) {
            return null;
        }
        $host = strtolower(preg_replace('/^www\./', '', parse_url($url, PHP_URL_HOST) ?? ''));
        $ok = collect(self::VIDEO_HOSTS)->contains(fn ($h) => $host === $h || str_ends_with($host, '.'.$h));
        if (! $ok) {
            return null;
        }
        $embed = null;
        if (preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,15})#i', $url, $m)) {
            $embed = 'https://www.youtube-nocookie.com/embed/'.$m[1];
        } elseif ($host === 'drive.google.com' && preg_match('#/file/d/([\w-]+)#', $url, $m)) {
            $embed = 'https://drive.google.com/file/d/'.$m[1].'/preview';
        } elseif ($host === 'vimeo.com' && preg_match('#vimeo\.com/(\d+)#', $url, $m)) {
            $embed = 'https://player.vimeo.com/video/'.$m[1];
        }

        return ['embed' => $embed, 'open' => $url, 'host' => $host];
    }
}
