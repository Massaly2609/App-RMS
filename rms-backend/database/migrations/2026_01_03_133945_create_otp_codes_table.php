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
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
             // téléphone concerné par l'OTP
            $table->string('phone');

            // code OTP (ex: 6 chiffres)
            $table->string('code');

            // date/heure d'expiration
            $table->timestamp('expires_at');

            // nombre de tentatives de vérification déjà faites
            $table->unsignedInteger('attempts')->default(0);

            // statut : utilisé ou non
            $table->boolean('used')->default(false);

            $table->timestamps();

            // index pour rechercher rapidement par téléphone
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
