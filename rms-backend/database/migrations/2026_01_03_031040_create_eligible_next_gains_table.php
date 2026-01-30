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
        Schema::create('eligible_next_gains', function (Blueprint $table) {
            $table->id();

             $table->foreignId('user_id')
            ->constrained()
            ->onDelete('cascade');

            // moment où il est devenu remboursé-éligible
            $table->timestamp('became_eligible_at');

            // passé à true quand il a reçu son gain prioritaire
            $table->boolean('processed')->default(false);

            $table->timestamps();

            // un seul enregistrement actif par user dans cette liste
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eligible_next_gains');
    }
};
