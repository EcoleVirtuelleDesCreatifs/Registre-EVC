<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('query', 80);
            $table->string('method', 12)->default('reference'); // reference | qr | api
            $table->foreignId('document_id')->nullable()->constrained('official_documents')->nullOnDelete();
            $table->string('result', 20)->index(); // valide | revoque | introuvable
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
