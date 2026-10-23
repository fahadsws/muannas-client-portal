<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Report extends Model
{
    protected $guarded = [];

    protected $casts = ['month' => 'date', 'engagement' => 'decimal:2'];

    /** Keep the report PDF listed in the client's Reports folder. */
    public function syncFile(?User $by = null): void
    {
        $file = ClientFile::where('ref_type', 'report')->where('ref_id', $this->id)->first();
        if (! $this->file_path) {
            $file?->delete();

            return;
        }
        $size = Storage::disk('local')->exists($this->file_path) ? Storage::disk('local')->size($this->file_path) : 0;
        $data = ['account_id' => $this->account_id, 'folder' => 'reports', 'name' => 'Performance report '.$this->month->format('Y-m').'.pdf', 'path' => $this->file_path, 'size' => $size, 'mime' => 'application/pdf',
            'uploaded_by' => $by?->id ?? $this->created_by, 'uploader' => $by?->name ?? 'Team', 'uploader_role' => 'team', 'ref_type' => 'report', 'ref_id' => $this->id];
        $file ? $file->update($data) : ClientFile::create($data);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
