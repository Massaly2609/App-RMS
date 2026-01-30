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
        Schema::create('user_states', function (Blueprint $table) {

            $table->id();

            // lien avec users
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // état dans le système RMS
            $table->enum('queue_state', [
                'none',
                'in_fifo',
                'waiting_repayment',
                'repaying',
                'rembourse_eligible',
            ])->default('none');

            $table->timestamp('last_state_changed_at')->nullable();

            $table->timestamps();

            // 1 seul état par user
            $table->unique('user_id');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_states');
    }
};
