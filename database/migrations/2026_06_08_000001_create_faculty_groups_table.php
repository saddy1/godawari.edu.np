<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('faculties', function (Blueprint $table) {
            $table->foreignId('faculty_group_id')
                ->nullable()
                ->after('id')
                ->constrained('faculty_groups')
                ->nullOnDelete();
        });

        $categories = DB::table('faculties')
            ->select('category', DB::raw('MIN(`order`) as sort_order'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('sort_order')
            ->get();

        foreach ($categories as $category) {
            if (! $category->category) {
                continue;
            }

            $groupId = DB::table('faculty_groups')->insertGetId([
                'name' => $category->category,
                'description' => null,
                'sort_order' => (int) $category->sort_order,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('faculties')
                ->where('category', $category->category)
                ->update(['faculty_group_id' => $groupId]);
        }
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('faculty_group_id');
        });

        Schema::dropIfExists('faculty_groups');
    }
};
