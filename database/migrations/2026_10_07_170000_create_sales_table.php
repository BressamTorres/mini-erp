<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('seller_id');
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign(['customer_id', 'business_id'])
                ->references(['id', 'business_id'])
                ->on('persons')
                ->restrictOnDelete();
            $table->foreign(['seller_id', 'business_id'])
                ->references(['id', 'business_id'])
                ->on('persons')
                ->restrictOnDelete();
            $table->unique(['id', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
