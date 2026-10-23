<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', fn (Blueprint $t) => $t->json('invoice_settings')->nullable());
        Schema::table('accounts', function (Blueprint $t) {
            $t->text('billing_address')->nullable();
            $t->string('tax_id', 60)->nullable();
        });

        Schema::table('invoices', function (Blueprint $t) {
            $t->unsignedBigInteger('agency_id')->nullable()->index()->after('id');
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->string('discount_type', 8)->default('fixed'); // fixed|percent
            $t->decimal('discount_value', 12, 2)->default(0);
            $t->decimal('discount', 12, 2)->default(0);        // resulting amount taken off
            $t->string('tax_label', 30)->nullable();
            $t->decimal('tax_rate', 5, 2)->default(0);
            $t->decimal('tax_amount', 12, 2)->default(0);
            $t->text('terms')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('draft')->default(false);
            $t->timestamp('sent_at')->nullable();
            $t->string('payment_method', 40)->nullable();
            $t->string('payment_ref', 120)->nullable();
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->string('description', 255);
            $t->string('details', 500)->nullable();
            $t->decimal('qty', 10, 2)->default(1);
            $t->decimal('unit_price', 12, 2)->default(0);
            $t->unsignedInteger('position')->default(0);
        });

        // numbers are unique per agency, not across the whole platform
        Schema::table('invoices', fn (Blueprint $t) => $t->dropUnique(['number']));
        DB::table('invoices')->orderBy('id')->each(function ($i) {
            $agency = DB::table('accounts')->where('id', $i->account_id)->value('agency_id');
            DB::table('invoices')->where('id', $i->id)->update(['agency_id' => $agency, 'subtotal' => $i->amount, 'sent_at' => $i->created_at]);
            DB::table('invoice_items')->insert(['invoice_id' => $i->id, 'description' => $i->title ?: 'Services', 'qty' => 1, 'unit_price' => $i->amount, 'position' => 0]);
        });
        Schema::table('invoices', fn (Blueprint $t) => $t->unique(['agency_id', 'number']));
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $t) {
            $t->dropUnique(['agency_id', 'number']);
        });
        Schema::dropIfExists('invoice_items');
        Schema::table('invoices', function (Blueprint $t) {
            $t->dropColumn(['agency_id', 'subtotal', 'discount_type', 'discount_value', 'discount', 'tax_label', 'tax_rate', 'tax_amount', 'terms', 'notes', 'draft', 'sent_at', 'payment_method', 'payment_ref']);
            $t->unique('number');
        });
        Schema::table('accounts', fn (Blueprint $t) => $t->dropColumn(['billing_address', 'tax_id']));
        Schema::table('agencies', fn (Blueprint $t) => $t->dropColumn('invoice_settings'));
    }
};
