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
        Schema::create('billing_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('default_rate', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no', 40)->unique();
            $table->enum('type', ['receipt', 'payment'])->default('receipt');
            $table->string('party_source_type', 40)->nullable();
            $table->unsignedBigInteger('party_source_id')->nullable();
            $table->string('party_name');
            $table->string('party_identifier')->nullable();
            $table->string('party_phone')->nullable();
            $table->string('party_email')->nullable();
            $table->string('party_address')->nullable();
            $table->string('purpose');
            $table->enum('payment_method', ['cash', 'bank', 'cheque', 'online'])->default('cash');
            $table->string('reference_no')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('amount_words')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index(['party_source_type', 'party_source_id']);
            $table->index(['type', 'issued_at']);
        });

        Schema::create('billing_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('billing_catalog_items')->insert([
            ['name' => 'Character Certificate', 'default_rate' => 300, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transcript Copy', 'default_rate' => 200, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Admission Form', 'default_rate' => 100, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('module_settings')->updateOrInsert(
            ['key' => 'billing'],
            [
                'label' => 'Billing',
                'description' => 'Cash receipts, payment vouchers, and printable bills',
                'group' => 'ERP',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        foreach (['billing.view', 'billing.create', 'billing.delete'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['super-admin', 'principal', 'administrator', 'accountant'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo(['billing.view', 'billing.create', 'billing.delete']);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invoice_items');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_catalog_items');

        DB::table('module_settings')->where('key', 'billing')->delete();
    }
};
