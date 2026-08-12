<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('store_fiscal_years');

        Schema::create('store_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->string('starts_on_bs', 10)->nullable();
            $table->string('ends_on_bs', 10)->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        DB::table('store_fiscal_years')->insert([
            'name' => '2082/83',
            'starts_on_bs' => '2082-04-01',
            'ends_on_bs' => '2083-03-32',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('store_requisitions', function (Blueprint $table) {
            $table->string('requested_at_bs', 10)->nullable()->after('requested_at');
            $table->string('approved_at_bs', 10)->nullable()->after('approved_at');
        });

        Schema::table('store_purchase_orders', function (Blueprint $table) {
            $table->string('decision_date_bs', 10)->nullable()->after('decision_date');
            $table->string('order_date_bs', 10)->nullable()->after('order_date');
            $table->string('expected_date_bs', 10)->nullable()->after('expected_date');
        });

        Schema::table('store_receipts', function (Blueprint $table) {
            $table->string('invoice_date_bs', 10)->nullable()->after('invoice_date');
            $table->string('received_at_bs', 10)->nullable()->after('received_at');
        });

        Schema::table('store_issues', function (Blueprint $table) {
            $table->string('issued_at_bs', 10)->nullable()->after('issued_at');
        });

        Schema::table('store_stock_movements', function (Blueprint $table) {
            $table->string('movement_date_bs', 10)->nullable()->after('movement_date');
        });
    }

    public function down(): void
    {
        Schema::table('store_stock_movements', function (Blueprint $table) {
            $table->dropColumn('movement_date_bs');
        });

        Schema::table('store_issues', function (Blueprint $table) {
            $table->dropColumn('issued_at_bs');
        });

        Schema::table('store_receipts', function (Blueprint $table) {
            $table->dropColumn(['invoice_date_bs', 'received_at_bs']);
        });

        Schema::table('store_purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['decision_date_bs', 'order_date_bs', 'expected_date_bs']);
        });

        Schema::table('store_requisitions', function (Blueprint $table) {
            $table->dropColumn(['requested_at_bs', 'approved_at_bs']);
        });

        Schema::dropIfExists('store_fiscal_years');
    }
};
