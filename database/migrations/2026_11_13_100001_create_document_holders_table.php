<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_holders', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->index();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('promotion', 40)->nullable();
            // Identifiant de l'apprenant dans My EVC (intégration future)
            $table->string('external_id', 80)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_holders');
    }
};
