<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $t) {
            $t->string('logo')->nullable();
        });

        // which team members look after which client
        Schema::create('account_team', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['account_id', 'user_id']);
        });

        Schema::create('review_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('token', 40)->unique();
            $t->string('title');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });

        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->foreignId('batch_id')->nullable()->constrained('review_batches')->nullOnDelete();
            $t->string('title');
            $t->string('type')->default('image'); // image|carousel|reel
            $t->string('platform')->default('instagram');
            $t->text('caption')->nullable();
            $t->json('media')->nullable(); // private-disk paths, in carousel order
            $t->string('video_url', 500)->nullable();
            $t->date('scheduled_on')->nullable();
            $t->string('status')->default('draft'); // draft|pending|changes|approved|published
            $t->unsignedSmallInteger('round')->default(1);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['account_id', 'status']);
            $t->index(['account_id', 'scheduled_on']);
        });

        Schema::create('post_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('author');
            $t->string('role')->default('team'); // team|client
            $t->text('body');
            $t->timestamps();
        });

        // audit trail: every submit / approval / change request, with who and when
        Schema::create('post_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('actor');
            $t->string('role')->default('team');
            $t->string('action'); // submitted|approved|changes_requested|published|reopened
            $t->unsignedSmallInteger('round')->default(1);
            $t->timestamp('created_at')->useCurrent();
        });

        // new permission keys: existing "Manager" roles keep full day-to-day control
        Role::where('is_system', true)->where('name', 'Manager')->each(function ($r) {
            $r->update(['permissions' => array_values(array_unique(array_merge($r->permissions, ['clients.all', 'billing.manage', 'reports.manage'])))]);
        });
    }

    public function down(): void
    {
        foreach (['post_events', 'post_comments', 'posts', 'review_batches', 'account_team'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('accounts', fn (Blueprint $t) => $t->dropColumn('logo'));
    }
};
