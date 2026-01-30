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
        Schema::create('rotations', function (Blueprint $table) {
            $table->id();

             $table->foreignId('user_id')
            ->constrained()
            ->onDelete('cascade');

            $table->decimal('amount', 15, 2)->default(100000);

            // source du gain : prioritaire ou FIFO
            $table->enum('source', ['eligible_next_gain', 'fifo_queue']);

            // liens optionnels
            $table->foreignId('queue_entry_id')
                ->nullable()
                ->constrained('fifo_queue_entries')
                ->onDelete('set null');

            $table->foreignId('eligible_next_gain_id')
                ->nullable()
                ->constrained('eligible_next_gains')
                ->onDelete('set null');

            $table->timestamp('triggered_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rotations');
    }
};
