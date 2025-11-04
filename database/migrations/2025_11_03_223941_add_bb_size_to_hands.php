<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hands', function (Blueprint $table) {
            $table->float('bb_size', 8, 2)->nullable()->after('rake');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hands', function (Blueprint $table) {
            $table->dropColumn('bb_size');
        });
    }
};
