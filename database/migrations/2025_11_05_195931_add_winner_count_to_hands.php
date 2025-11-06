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
            $table->integer('winner_count')->nullable()->after('bb_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hands', function (Blueprint $table) {
            $table->removeColumn('winner_count');
        });
    }
};
