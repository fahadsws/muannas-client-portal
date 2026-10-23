<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_folders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('name', 80);
            $t->timestamps();
        });

        Schema::table('client_files', function (Blueprint $t) {
            $t->string('ref_type', 20)->nullable();           // 'report': the file belongs to a monthly report
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->index(['ref_type', 'ref_id']);
        });

        // reports that already have a PDF show up in the Reports folder
        foreach (DB::table('reports')->whereNotNull('file_path')->get() as $r) {
            $size = 0;
            DB::table('client_files')->insert([
                'account_id' => $r->account_id, 'folder' => 'reports', 'name' => 'Performance report '.substr($r->month, 0, 7).'.pdf', 'path' => $r->file_path,
                'size' => $size, 'mime' => 'application/pdf', 'uploaded_by' => $r->created_by, 'uploader' => 'Team', 'uploader_role' => 'team',
                'ref_type' => 'report', 'ref_id' => $r->id, 'created_at' => $r->updated_at, 'updated_at' => $r->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('client_files', function (Blueprint $t) {
            $t->dropIndex(['ref_type', 'ref_id']);
            $t->dropColumn(['ref_type', 'ref_id']);
        });
        Schema::dropIfExists('file_folders');
    }
};
