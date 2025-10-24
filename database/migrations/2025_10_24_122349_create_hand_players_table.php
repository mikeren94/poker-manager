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
        Schema::create('hand_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hand_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->string('position')->nullable(); // e.g. "BTN", "CO"
            $table->text('action')->nullable(); // e.g. "Raise to 2bb, fold"
            $table->decimal('result', 10, 2)->nullable(); // Net result for this player
            $table->boolean('is_hero')->default(false);
            $table->boolean('is_winner')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hand_players');
    }
};
