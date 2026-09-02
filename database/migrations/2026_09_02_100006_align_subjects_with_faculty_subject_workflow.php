<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && ! Schema::hasColumn('subjects', 'has_practical')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('has_practical')->default(false)->after('credit_hours');
            });
        }

        if (Schema::hasTable('subjects')) {
            $usedCodes = [];

            DB::table('subjects')->orderBy('id')->get(['id', 'code'])->each(function ($subject) use (&$usedCodes) {
                $base = strtoupper(preg_replace('/\s+/', '', trim((string) $subject->code)));
                $base = $base !== '' ? $base : 'SUB'.$subject->id;
                $base = substr($base, 0, 45);
                $code = $base;
                $suffix = 2;

                while (isset($usedCodes[$code])) {
                    $code = $base.'-'.$suffix++;
                }

                $usedCodes[$code] = true;
                DB::table('subjects')->where('id', $subject->id)->update(['code' => $code]);
            });

            Schema::table('subjects', function (Blueprint $table) {
                $table->string('code', 50)->nullable(false)->change();
            });

            $hasUniqueCode = collect(Schema::getIndexes('subjects'))->contains(function (array $index) {
                return ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['code'];
            });

            if (! $hasUniqueCode) {
                Schema::table('subjects', function (Blueprint $table) {
                    $table->unique('code');
                });
            }
        }

        if (Schema::hasTable('subject_offerings')) {
            $needsElective = ! Schema::hasColumn('subject_offerings', 'is_elective');
            $needsElectiveGroup = ! Schema::hasColumn('subject_offerings', 'elective_group');

            Schema::table('subject_offerings', function (Blueprint $table) use ($needsElective, $needsElectiveGroup) {
                if ($needsElective) {
                    $table->boolean('is_elective')->default(false)->after('subject_id');
                }
                if ($needsElectiveGroup) {
                    $table->string('elective_group', 50)->nullable()->after('is_elective');
                }
            });

            if (Schema::hasColumn('subject_offerings', 'is_compulsory')) {
                DB::table('subject_offerings')->where('is_compulsory', false)->update(['is_elective' => true]);
            }
            if (Schema::hasColumn('subject_offerings', 'slot')) {
                DB::table('subject_offerings')
                    ->where('is_elective', true)
                    ->whereNotNull('slot')
                    ->update(['elective_group' => DB::raw('slot')]);
            }
        }
    }

    public function down(): void
    {
        // These columns contain curriculum meaning and are intentionally kept.
    }
};
