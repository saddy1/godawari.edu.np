<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_loans', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('fine_paid');
            $table->string('payment_txn')->nullable()->after('payment_method');
            $table->unsignedTinyInteger('renewal_count')->default(0)->after('payment_txn');
            $table->date('renewed_at')->nullable()->after('renewal_count');
            $table->foreignId('renewed_by')->nullable()->constrained('users')->nullOnDelete()->after('renewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('library_loans', function (Blueprint $table) {
            $table->dropForeign(['renewed_by']);
            $table->dropColumn(['payment_method', 'payment_txn', 'renewal_count', 'renewed_at', 'renewed_by']);
        });
    }
};
