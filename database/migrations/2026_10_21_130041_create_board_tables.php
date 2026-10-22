<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('brand_color', 9)->default('#7c3aed');
            $t->string('status')->default('active'); // active|suspended
            $t->unsignedInteger('max_workspaces')->default(10);
            $t->unsignedInteger('max_members')->default(5);
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->json('value')->nullable();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('member'); // super|owner|member|guest
            $t->foreignId('agency_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('token', 40)->unique();
            $t->boolean('share_enabled')->default(true);
            $t->string('share_perm')->default('comment'); // comment|view
            $t->unsignedInteger('position')->default(0);
            $t->json('docs')->nullable();
            $t->timestamps();
        });

        Schema::create('account_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('perm')->default('comment'); // edit|comment
            $t->timestamps();
            $t->unique(['account_id', 'user_id']);
        });

        Schema::create('tabs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('label');
            $t->string('kind'); // strategy|grid|posts|reels|podcasts
            $t->unsignedInteger('position')->default(0);
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tab_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('position')->default(0);
            $t->unsignedTinyInteger('col')->default(0);
            $t->string('status')->default('todo'); // todo|ready|done|live
            $t->boolean('locked')->default(false);
            $t->string('image')->nullable();
            $t->json('data')->nullable();
            $t->timestamps();
        });

        Schema::create('notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('author');
            $t->string('role')->default('team'); // team|client
            $t->text('body');
            $t->timestamps();
        });

        Schema::create('doc_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->json('config');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'doc_templates', 'notes', 'items', 'tabs', 'account_user', 'accounts'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('agency_id');
            $t->dropColumn('role');
        });
        Schema::dropIfExists('agencies');
    }
};
