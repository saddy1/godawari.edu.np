<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examination_departments', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('department_id');
            $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
        });

        // Exams already marked "published" as a whole (the old all-or-nothing
        // status) should keep showing results to every department they cover —
        // backfill so the new per-department gate doesn't hide anything that
        // was already visible.
        $now = now();
        DB::table('examination_departments')
            ->whereIn('examination_id', DB::table('examinations')->where('status', 'published')->pluck('id'))
            ->update(['published_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('examination_departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn('published_at');
        });
    }
};
