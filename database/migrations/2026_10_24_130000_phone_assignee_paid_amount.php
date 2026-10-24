<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $t) {
            $t->string('phone', 30)->nullable();
        });
        Schema::table('tasks', function (Blueprint $t) {
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('description')->nullable();
        });
        Schema::table('invoices', function (Blueprint $t) {
            $t->decimal('paid_amount', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn('paid_amount'));
        Schema::table('tasks', function (Blueprint $t) {
            $t->dropConstrainedForeignId('assignee_id');
            $t->dropColumn('description');
        });
        Schema::table('accounts', fn (Blueprint $t) => $t->dropColumn('phone'));
    }
};
