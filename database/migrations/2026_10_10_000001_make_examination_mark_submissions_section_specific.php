<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL applies DDL immediately, so tolerate a retry after a failed
        // deployment that has already added this column.
        if (! Schema::hasColumn('examination_mark_submissions', 'section_id')) {
            Schema::table('examination_mark_submissions', function (Blueprint $table) {
                $table->foreignId('section_id')->nullable()->after('teacher_id')->constrained('sections')->nullOnDelete();
            });
        }

        Schema::table('examination_mark_submissions', function (Blueprint $table) {
            // The old two-column unique key currently supports the subject FK.
            // Keep that FK valid while replacing it with the section-aware key.
            $table->index('examination_subject_id', 'exam_mark_submission_subject_index');
            $table->dropUnique('exam_mark_submission_teacher_unique');
        });

        // Older submissions contained several section IDs in one row. Split
        // them so each completed section retains its own locked status.
        DB::table('examination_mark_submissions')->orderBy('id')->get()->each(function ($submission) {
            $sectionIds = collect(json_decode($submission->section_ids ?? '[]', true))
                ->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->values();
            if ($sectionIds->isEmpty()) return;

            DB::table('examination_mark_submissions')->where('id', $submission->id)->update([
                'section_id' => $sectionIds->first(),
            ]);
            foreach ($sectionIds->slice(1) as $sectionId) {
                DB::table('examination_mark_submissions')->insert([
                    'examination_subject_id' => $submission->examination_subject_id,
                    'teacher_id' => $submission->teacher_id,
                    'section_id' => $sectionId,
                    'section_ids' => json_encode([$sectionId]),
                    'locked_at' => $submission->locked_at,
                    'unlock_requested_at' => $submission->unlock_requested_at,
                    'unlock_reason' => $submission->unlock_reason,
                    'unlocked_at' => $submission->unlocked_at,
                    'unlocked_by' => $submission->unlocked_by,
                    'created_at' => $submission->created_at,
                    'updated_at' => $submission->updated_at,
                ]);
            }
        });

        Schema::table('examination_mark_submissions', function (Blueprint $table) {
            $table->unique(['examination_subject_id', 'teacher_id', 'section_id'], 'exam_mark_submission_section_unique');
        });
    }

    public function down(): void
    {
        Schema::table('examination_mark_submissions', function (Blueprint $table) {
            $table->dropUnique('exam_mark_submission_section_unique');
            $table->dropConstrainedForeignId('section_id');
            $table->dropIndex('exam_mark_submission_subject_index');
            $table->unique(['examination_subject_id', 'teacher_id'], 'exam_mark_submission_teacher_unique');
        });
    }
};
