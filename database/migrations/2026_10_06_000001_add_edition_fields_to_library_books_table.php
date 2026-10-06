<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            // A hard unique constraint rejects a second edition that happens to
            // share an ISBN with the first (publisher reuse, or a cataloger who
            // only has the old ISBN on hand) — relaxed to a plain index so
            // lookups/duplicate-detection stay fast without blocking that case.
            $table->dropUnique(['isbn']);
            $table->index('isbn');

            $table->string('volume', 60)->nullable()->after('edition');
            $table->string('language', 60)->nullable()->after('volume');

            // Optional, soft link to an earlier edition of the same work —
            // the MARC 780/785 "preceding/succeeding entry" equivalent.
            // Nullable and self-referencing: never required, never changes
            // how an unrelated book or its copies/loans behave.
            $table->foreignId('preceded_by_book_id')->nullable()->after('library_category_id')
                ->constrained('library_books')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preceded_by_book_id');
            $table->dropColumn(['volume', 'language']);
            $table->dropIndex(['isbn']);
            $table->unique('isbn');
        });
    }
};
