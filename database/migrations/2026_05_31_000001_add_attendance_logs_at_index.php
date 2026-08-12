<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendacelogs', function (Blueprint $table) {
            if (! Schema::hasIndex('attendacelogs', 'attendacelogs_at_index')) {
                $table->index('at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendacelogs', function (Blueprint $table) {
            if (Schema::hasIndex('attendacelogs', 'attendacelogs_at_index')) {
                $table->dropIndex('attendacelogs_at_index');
            }
        });
    }
};
