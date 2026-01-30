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
        Schema::create('fifo_queue_entries', function (Blueprint $table) {
            $table->id();

             $table->foreignId('user_id')
            ->constrained()
            ->onDelete('cascade');

            // date d’entrée dans la file, sert pour l’ordre FIFO
            $table->timestamp('entered_at');

            // true = dans la file, false = sorti (gain ou autre)
            $table->boolean('active')->default(true);

            // info optionnelle (debug / stats)
            $table->unsignedBigInteger('position_snapshot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fifo_queue_entries');
    }
};
