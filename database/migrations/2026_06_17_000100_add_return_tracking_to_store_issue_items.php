<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_issue_items', function (Blueprint $table) {
            $table->decimal('returned_quantity', 12, 2)->default(0)->after('quantity');
            $table->string('returned_at_bs', 20)->nullable()->after('returned_quantity');
            $table->timestamp('returned_at')->nullable()->after('returned_at_bs');
            $table->foreignId('returned_by')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('store_issue_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn(['returned_quantity', 'returned_at_bs', 'returned_at']);
        });
    }
};
