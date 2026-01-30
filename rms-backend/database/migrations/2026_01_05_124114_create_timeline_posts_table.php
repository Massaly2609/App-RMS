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
        Schema::create('timeline_posts', function (Blueprint $table) {
            $table->id();
             // auteur du post
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // type de contenu (texte, photo, video, system, mecene, diaspora...)
            $table->enum('type', [
                'text',
                'photo',
                'video',
                'audio',
                'system_rotation',
                'system_repayment_completed',
                'system_mecene',
                'system_diaspora',
                'system_adhesion',
            ])->default('text');

            // contenu texte principal
            $table->text('content')->nullable();

            // URL de média (pour simplifier pour l’instant)
            $table->string('media_url')->nullable();

            // données supplémentaires (JSON) pour payload système
            $table->json('metadata')->nullable();

            // visibilité globale (public, admin-only etc. si on veut évoluer)
            $table->enum('visibility', ['public', 'admin', 'hidden'])
                ->default('public');

            // ville / pays pour filtres timeline
            $table->string('country', 5)->nullable();
            $table->string('city', 100)->nullable();

            // statut modération
            $table->enum('status', ['published', 'pending_review', 'rejected'])
                ->default('published');

            $table->timestamps();

            $table->index(['type', 'visibility', 'status']);
            $table->index(['country', 'city']);
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline_posts');
    }
};
