<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');               // book_issued, book_returned, fine_collected, book_added, rule_updated, patron_category_updated
            $table->foreignId('loan_id')->nullable()->constrained('library_loans')->nullOnDelete();
            $table->foreignId('book_copy_id')->nullable()->constrained('library_book_copies')->nullOnDelete();
            $table->foreignId('book_id')->nullable()->constrained('library_books')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();   // borrower
            $table->string('borrower_name')->nullable();
            $table->string('borrower_identifier')->nullable();
            $table->string('borrower_type')->nullable();
            $table->string('book_title')->nullable();
            $table->string('accession_no')->nullable();
            $table->decimal('fine_amount', 10, 2)->nullable();
            $table->text('details')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_activity_logs');
    }
};
