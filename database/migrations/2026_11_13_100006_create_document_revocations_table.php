<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_revocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('official_documents')->cascadeOnDelete();
            $table->text('reason');
            $table->string('decision_reference', 120)->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_revocations');
    }
};
