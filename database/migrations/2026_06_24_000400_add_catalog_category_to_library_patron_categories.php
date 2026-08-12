<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_patron_categories', function (Blueprint $table) {
            // Optional catalog category scope (e.g. "Reference books → different rules")
            $table->foreignId('library_category_id')
                ->nullable()
                ->after('slug')
                ->constrained('library_categories')
                ->nullOnDelete();

            // Structured class range (replaces free-text class_range)
            $table->unsignedTinyInteger('class_from')->nullable()->after('class_range');
            $table->unsignedTinyInteger('class_to')->nullable()->after('class_from');
        });

        // Migrate existing class_range values into class_from / class_to
        DB::table('library_patron_categories')
            ->whereNotNull('class_range')
            ->get()
            ->each(function ($row) {
                if (str_contains((string) $row->class_range, '-')) {
                    [$from, $to] = explode('-', $row->class_range, 2);
                    DB::table('library_patron_categories')
                        ->where('id', $row->id)
                        ->update([
                            'class_from' => (int) trim($from),
                            'class_to'   => (int) trim($to),
                        ]);
                }
            });

        // Add a catch-all "All Patrons (Default)" category as the global fallback
        // patron_type = null + class_from = null means it matches everyone
        // sort_order = 99 so it is always checked last
        if (! DB::table('library_patron_categories')->where('slug', 'default-all')->exists()) {
            DB::table('library_patron_categories')->insert([
                'name'             => 'All Patrons (Default)',
                'slug'             => 'default-all',
                'patron_type'      => null,
                'class_range'      => null,
                'class_from'       => null,
                'class_to'         => null,
                'library_category_id' => null,
                'max_active_books' => 3,
                'loan_days'        => 14,
                'fine_per_day'     => 2,
                'block_same_title' => true,
                'description'      => 'Global fallback — applies when no other patron category matches.',
                'is_active'        => true,
                'sort_order'       => 99,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('library_patron_categories')->where('slug', 'default-all')->delete();

        Schema::table('library_patron_categories', function (Blueprint $table) {
            $table->dropForeign(['library_category_id']);
            $table->dropColumn(['library_category_id', 'class_from', 'class_to']);
        });
    }
};
