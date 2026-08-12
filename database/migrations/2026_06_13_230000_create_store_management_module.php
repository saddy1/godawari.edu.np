<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('tax_registration_type', 10)->default('pan');
            $table->string('pan_vat_no', 80)->nullable();
            $table->string('registration_no', 80)->nullable();
            $table->string('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('store_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('name');
            $table->string('code', 40)->nullable()->unique();
            $table->boolean('is_consumable')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('store_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('store_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 30);
            $table->boolean('allow_decimal')->default(false);
            $table->timestamps();
        });

        Schema::create('store_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->foreignId('store_brand_id')->nullable()->constrained('store_brands')->nullOnDelete();
            $table->foreignId('store_unit_id')->nullable()->constrained('store_units')->nullOnDelete();
            $table->string('item_code', 60)->unique();
            $table->string('name');
            $table->text('specification')->nullable();
            $table->string('model_no')->nullable();
            $table->string('serial_no')->nullable();
            $table->enum('asset_type', ['consumable', 'non_consumable', 'fixed_asset'])->default('consumable');
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('opening_quantity', 12, 2)->default(0);
            $table->decimal('opening_rate', 12, 2)->default(0);
            $table->decimal('current_quantity', 12, 2)->default(0);
            $table->decimal('current_value', 14, 2)->default(0);
            $table->string('storage_location')->nullable();
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['asset_type', 'is_active']);
        });

        Schema::create('store_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_no', 50)->unique();
            $table->string('requested_by_name');
            $table->string('requested_by_designation')->nullable();
            $table->string('purpose')->nullable();
            $table->string('fiscal_year', 20)->nullable();
            $table->enum('status', ['draft', 'approved', 'issued', 'cancelled'])->default('draft');
            $table->date('requested_at')->nullable();
            $table->date('approved_at')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('store_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_requisition_id')->constrained('store_requisitions')->cascadeOnDelete();
            $table->foreignId('store_item_id')->nullable()->constrained('store_items')->nullOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->string('unit', 40)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('store_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->unique();
            $table->foreignId('store_supplier_id')->nullable()->constrained('store_suppliers')->nullOnDelete();
            $table->string('supplier_name');
            $table->string('supplier_address')->nullable();
            $table->string('supplier_phone', 40)->nullable();
            $table->string('fiscal_year', 20)->nullable();
            $table->string('decision_no')->nullable();
            $table->date('decision_date')->nullable();
            $table->date('order_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->enum('status', ['draft', 'ordered', 'received', 'cancelled'])->default('ordered');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('store_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_purchase_order_id')->constrained('store_purchase_orders')->cascadeOnDelete();
            $table->foreignId('store_item_id')->nullable()->constrained('store_items')->nullOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->string('unit', 40)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('store_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 50)->unique();
            $table->foreignId('store_purchase_order_id')->nullable()->constrained('store_purchase_orders')->nullOnDelete();
            $table->foreignId('store_supplier_id')->nullable()->constrained('store_suppliers')->nullOnDelete();
            $table->string('received_from')->nullable();
            $table->string('challan_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('fiscal_year', 20)->nullable();
            $table->date('received_at')->nullable();
            $table->string('received_by_name')->nullable();
            $table->string('verified_by_name')->nullable();
            $table->enum('status', ['draft', 'posted'])->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('store_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_receipt_id')->constrained('store_receipts')->cascadeOnDelete();
            $table->foreignId('store_item_id')->nullable()->constrained('store_items')->nullOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->string('unit', 40)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('condition')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('store_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_no', 50)->unique();
            $table->foreignId('store_requisition_id')->nullable()->constrained('store_requisitions')->nullOnDelete();
            $table->string('issued_to_name');
            $table->string('issued_to_designation')->nullable();
            $table->string('purpose')->nullable();
            $table->string('fiscal_year', 20)->nullable();
            $table->date('issued_at')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->string('store_keeper_name')->nullable();
            $table->enum('status', ['draft', 'posted'])->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('store_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_issue_id')->constrained('store_issues')->cascadeOnDelete();
            $table->foreignId('store_item_id')->nullable()->constrained('store_items')->nullOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->string('unit', 40)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('store_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_item_id')->constrained('store_items')->cascadeOnDelete();
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->enum('movement_type', ['opening', 'receipt', 'issue', 'adjustment', 'transfer'])->default('receipt');
            $table->date('movement_date')->nullable();
            $table->string('fiscal_year', 20)->nullable();
            $table->decimal('quantity_in', 12, 2)->default(0);
            $table->decimal('quantity_out', 12, 2)->default(0);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('balance_quantity', 12, 2)->default(0);
            $table->decimal('balance_value', 14, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['movement_type', 'movement_date']);
        });

        DB::table('store_units')->insert([
            ['name' => 'Piece', 'symbol' => 'pcs', 'allow_decimal' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Packet', 'symbol' => 'pkt', 'allow_decimal' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'allow_decimal' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Litre', 'symbol' => 'ltr', 'allow_decimal' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('store_categories')->insert([
            ['name' => 'Stationery', 'code' => 'STN', 'is_consumable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Furniture', 'code' => 'FUR', 'is_consumable' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ICT Equipment', 'code' => 'ICT', 'is_consumable' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('module_settings')->updateOrInsert(
            ['key' => 'store'],
            [
                'label' => 'Store',
                'description' => 'Inventory, suppliers, stock ledgers, and government store forms',
                'group' => 'ERP',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        foreach (['store.view', 'store.create', 'store.edit', 'store.delete', 'store.approve', 'store.reports'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['super-admin', 'principal', 'administrator', 'accountant'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo(['store.view', 'store.create', 'store.edit', 'store.delete', 'store.approve', 'store.reports']);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_stock_movements');
        Schema::dropIfExists('store_issue_items');
        Schema::dropIfExists('store_issues');
        Schema::dropIfExists('store_receipt_items');
        Schema::dropIfExists('store_receipts');
        Schema::dropIfExists('store_purchase_order_items');
        Schema::dropIfExists('store_purchase_orders');
        Schema::dropIfExists('store_requisition_items');
        Schema::dropIfExists('store_requisitions');
        Schema::dropIfExists('store_items');
        Schema::dropIfExists('store_units');
        Schema::dropIfExists('store_brands');
        Schema::dropIfExists('store_categories');
        Schema::dropIfExists('store_suppliers');

        DB::table('module_settings')->where('key', 'store')->delete();
    }
};
