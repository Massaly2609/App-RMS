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
        Schema::create('repayment_installments', function (Blueprint $table) {
            $table->id();

            // juste des colonnes entières, sans contraintes FK (on garde la logique dans Eloquent)
            $table->unsignedBigInteger('repayment_id');
            $table->unsignedBigInteger('transaction_id');

            $table->decimal('amount', 15, 2);

            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayment_installments');
    }
};
