<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('number')->unique();
            $t->string('title')->nullable();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 3)->default('AED');
            $t->date('issued_on');
            $t->date('due_on');
            $t->date('paid_on')->nullable();
            $t->string('file_path')->nullable(); // uploaded PDF; a PDF is generated when empty
            $t->timestamp('reminded_before_at')->nullable();
            $t->timestamp('reminded_overdue_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['account_id', 'due_on']);
        });

        Schema::create('reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->date('month'); // always the 1st of the month
            $t->unsignedBigInteger('reach')->default(0);
            $t->decimal('engagement', 6, 2)->default(0); // engagement rate %
            $t->integer('follower_growth')->default(0);
            $t->string('top_post')->nullable();
            $t->text('notes')->nullable();
            $t->string('file_path')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['account_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('invoices');
    }
};
