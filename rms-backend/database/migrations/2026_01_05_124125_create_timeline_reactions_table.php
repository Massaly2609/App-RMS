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
        Schema::create('timeline_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                ->constrained('timeline_posts')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // type de réaction : like ou commentaire
            $table->enum('type', ['like', 'comment']);

            // texte pour les commentaires
            $table->text('comment_text')->nullable();

            $table->timestamps();

            // un seul like par user/post
            $table->unique(['post_id', 'user_id', 'type']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline_reactions');
    }
};
