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
        Schema::create('jackpot_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jackpot_wallet_id')
            ->constrained()
            ->onDelete('cascade');

            // contribution (adhésion, mécénat, remboursement...) ou sortie pour un gain
            $table->enum('type', ['contribution', 'rotation_payout']);

            // in = argent qui entre dans la cagnotte, out = qui en sort
            $table->enum('direction', ['in', 'out']);

            $table->decimal('amount', 18, 2);

            // lien optionnel vers une transaction utilisateur
            $table->foreignId('related_transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->onDelete('set null');

            // on garde juste un entier nullable, sans contrainte FK pour l’instant
            $table->unsignedBigInteger('related_rotation_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jackpot_transactions');
    }
};
