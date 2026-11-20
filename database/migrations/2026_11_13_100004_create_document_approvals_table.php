<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('official_documents')->cascadeOnDelete();
            $table->string('step', 30);      // soumission | controle_pedagogique | approbation | delivrance | publication
            $table->string('decision', 20);  // approuve | rejete | effectue
            $table->text('comment')->nullable();
            $table->json('checklist')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};
