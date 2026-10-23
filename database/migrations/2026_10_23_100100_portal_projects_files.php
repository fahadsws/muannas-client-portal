<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->date('due_on')->nullable();
            $t->string('status')->default('active'); // active|on_hold|completed
            $t->timestamps();
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('status')->default('todo'); // todo|doing|done
            $t->date('due_on')->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::create('client_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('folder')->default('deliverables'); // brand|logos|guidelines|deliverables|client
            $t->string('name');
            $t->string('path');
            $t->unsignedBigInteger('size')->default(0);
            $t->string('mime')->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('uploader');
            $t->string('uploader_role')->default('team'); // team|client
            $t->timestamps();
            $t->index(['account_id', 'folder']);
        });
    }

    public function down(): void
    {
        foreach (['client_files', 'tasks', 'projects'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
