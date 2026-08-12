<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->string('eyebrow_ne')->nullable()->after('eyebrow');
            $table->string('title_ne')->nullable()->after('title');
            $table->text('subtitle_ne')->nullable()->after('subtitle');
            $table->string('primary_label_ne')->nullable()->after('primary_label');
            $table->string('secondary_label_ne')->nullable()->after('secondary_label');
        });
    }

    public function down(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->dropColumn(['eyebrow_ne', 'title_ne', 'subtitle_ne', 'primary_label_ne', 'secondary_label_ne']);
        });
    }
};
