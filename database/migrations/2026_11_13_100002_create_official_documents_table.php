<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_documents', function (Blueprint $table) {
            $table->id();
            // Référence attribuée à la délivrance, jamais réutilisée
            $table->string('reference', 40)->nullable()->unique();
            // Identifiant aléatoire du QR code
            $table->string('public_token', 64)->nullable()->unique();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->foreignId('holder_id')->constrained('document_holders');
            $table->string('formation', 10)->nullable()->index();
            $table->string('promotion', 40)->nullable()->index();
            $table->date('issued_at')->nullable()->index();
            $table->string('status', 20)->default('brouillon')->index();
            $table->string('pdf_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->boolean('pdf_public')->default(true);
            $table->json('checklist')->nullable();
            $table->timestamp('pedagogical_validated_at')->nullable();
            $table->foreignId('pedagogical_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_legacy')->default(false);
            $table->text('internal_note')->nullable();
            $table->string('source', 20)->default('manuel'); // manuel | import | my_evc | historique
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_documents');
    }
};
