<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_requisition_items', function (Blueprint $table) {
            $table->foreignId('store_category_id')->nullable()->after('store_item_id')->constrained('store_categories')->nullOnDelete();
        });

        Schema::table('store_purchase_orders', function (Blueprint $table) {
            $table->foreignId('store_requisition_id')->nullable()->after('store_supplier_id')->constrained('store_requisitions')->nullOnDelete();
        });

        Schema::table('store_purchase_order_items', function (Blueprint $table) {
            $table->foreignId('store_requisition_item_id')->nullable()->after('store_item_id')->constrained('store_requisition_items')->nullOnDelete();
            $table->foreignId('store_category_id')->nullable()->after('store_requisition_item_id')->constrained('store_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('store_purchase_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_category_id');
            $table->dropConstrainedForeignId('store_requisition_item_id');
        });

        Schema::table('store_purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_requisition_id');
        });

        Schema::table('store_requisition_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_category_id');
        });
    }
};
