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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
                // chaque wallet appartient à un user
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // solde actuel du portefeuille
            $table->decimal('balance', 15, 2)->default(0);

            // monnaie, on part sur XOF par défaut
            $table->string('currency', 3)->default('XOF');

            $table->timestamps();

            // 1 wallet par user
            $table->unique('user_id');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('wallets');
        }
};
