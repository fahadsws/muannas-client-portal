<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', fn (Blueprint $t) => $t->string('currency', 3)->default('AED'));

        Schema::create('invoice_taxes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $t->string('name', 60);
            $t->decimal('rate', 6, 2);
            $t->boolean('is_default')->default(false);
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::table('invoice_items', function (Blueprint $t) {
            $t->string('discount_type', 8)->default('percent'); // percent|fixed
            $t->decimal('discount_value', 12, 2)->default(0);
            $t->decimal('discount', 12, 2)->default(0);        // resulting amount taken off this line
            $t->json('taxes')->nullable();                      // snapshot: [{name, rate}]
        });

        Schema::table('invoices', function (Blueprint $t) {
            $t->unsignedBigInteger('project_id')->nullable()->index();
            $t->json('tax_breakdown')->nullable();             // snapshot: [{name, rate, amount}]
        });

        // carry over the single tax from the first version of the module
        foreach (DB::table('agencies')->get() as $a) {
            $cfg = json_decode($a->invoice_settings ?? '[]', true) ?: [];
            if ((float) ($cfg['tax_rate'] ?? 0) > 0) {
                DB::table('invoice_taxes')->insert(['agency_id' => $a->id, 'name' => $cfg['tax_label'] ?: 'Tax', 'rate' => $cfg['tax_rate'], 'is_default' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach (DB::table('invoices')->where('tax_rate', '>', 0)->get() as $i) {
            $tax = [['name' => $i->tax_label ?: 'Tax', 'rate' => (float) $i->tax_rate]];
            DB::table('invoice_items')->where('invoice_id', $i->id)->update(['taxes' => json_encode($tax)]);
            DB::table('invoices')->where('id', $i->id)->update(['tax_breakdown' => json_encode([$tax[0] + ['amount' => (float) $i->tax_amount]])]);
        }
        // every existing client keeps the currency their invoices were already in
        foreach (DB::table('invoices')->select('account_id', 'currency')->distinct()->get() as $row) {
            DB::table('accounts')->where('id', $row->account_id)->update(['currency' => $row->currency]);
        }
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn(['project_id', 'tax_breakdown']));
        Schema::table('invoice_items', fn (Blueprint $t) => $t->dropColumn(['discount_type', 'discount_value', 'discount', 'taxes']));
        Schema::dropIfExists('invoice_taxes');
        Schema::table('accounts', fn (Blueprint $t) => $t->dropColumn('currency'));
    }
};
