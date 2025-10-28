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
        Schema::create('hand_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hand_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->string('action'); // e.g. 'fold', 'call', 'raise'
            $table->decimal('amount', 8, 2)->nullable(); 
            $table->integer('street')->nullable(); // 0 = preflop, 1 = flop, 2 = turn, 3 = river
            $table->integer('action_order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hand_actions');
    }
};
