<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('document', 20)->nullable();
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('type', 20)->index();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'type', 'active']);
            $table->unique(['id', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
