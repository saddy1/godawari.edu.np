<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_receipt_items', function (Blueprint $table) {
            $table->foreignId('store_purchase_order_item_id')
                ->nullable()
                ->after('store_receipt_id')
                ->constrained('store_purchase_order_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('store_receipt_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_purchase_order_item_id');
        });
    }
};
