<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_purchase_orders', function (Blueprint $table) {
            $table->string('tax_mode', 10)->default('vat')->after('expected_date_bs');
        });

        DB::statement("
            UPDATE store_purchase_orders po
            SET tax_mode = CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM store_purchase_order_items poi
                    WHERE poi.store_purchase_order_id = po.id
                        AND poi.tax_rate > 0
                ) THEN 'vat'
                ELSE 'pan'
            END
        ");

        DB::table('store_purchase_order_items')->update([
            'amount' => DB::raw('ROUND(quantity * rate, 2)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('store_purchase_orders', function (Blueprint $table) {
            $table->dropColumn('tax_mode');
        });
    }
};
