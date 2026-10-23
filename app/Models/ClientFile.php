<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFile extends Model
{
    protected $guarded = [];

    public const FOLDERS = [
        'brand' => 'Brand kit', 'logos' => 'Logos', 'guidelines' => 'Guidelines',
        'deliverables' => 'Final deliverables', 'client' => 'Shared by you', 'reports' => 'Reports',
    ];

    /** Folders only the Muannas team may add to; clients upload into "client". */
    public const TEAM_FOLDERS = ['brand', 'logos', 'guidelines', 'deliverables'];

    public const ALLOWED = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'zip', 'ai', 'psd', 'eps', 'mp3', 'wav'];

    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    /** Files that belong to a report are managed from Performance Reports. */
    public function isLinked(): bool
    {
        return $this->ref_type !== null;
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function sizeLabel(): string
    {
        $b = $this->size;

        return $b >= 1048576 ? round($b / 1048576, 1).' MB' : max(1, round($b / 1024)).' KB';
    }
}
