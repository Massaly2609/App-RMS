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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            // lien avec user et wallet
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('wallet_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            // prestataire de paiement (Wave, OM, Free, Stripe...)
            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('payment_providers')
                ->onDelete('set null');

            // type métier: adhesion, remboursement, mecenat, diaspora_contribution, gain_payout...
            $table->string('type');

            // montant et devise
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('XOF');

            // sens du flux: in (vers RMS / membre), out (depuis RMS / membre)
            $table->enum('direction', ['in', 'out']);

            // statut du paiement
            $table->enum('status', ['pending', 'succeeded', 'failed'])
                ->default('pending');

            // id de transaction chez l’agrégateur / Stripe
            $table->string('external_reference')->nullable();

            // données supplémentaires (JSON)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // pour éviter les doublons de callback
            $table->unique(['provider_id', 'external_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
