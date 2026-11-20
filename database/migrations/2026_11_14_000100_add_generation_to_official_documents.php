<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Génération automatique des documents : contenu variable, PDF figé et empreinte SHA-256. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_documents', function (Blueprint $table) {
            $table->json('details')->nullable()->after('checklist');
            $table->char('pdf_sha256', 64)->nullable()->after('pdf_path');
            $table->string('generated_pdf_path')->nullable()->after('pdf_sha256');
            $table->char('generated_sha256', 64)->nullable()->after('generated_pdf_path');
            $table->timestamp('generated_at')->nullable()->after('generated_sha256');
            $table->string('template_version', 20)->nullable()->after('generated_at');
            $table->index('pdf_sha256');
        });
    }

    public function down(): void
    {
        Schema::table('official_documents', function (Blueprint $table) {
            $table->dropIndex(['pdf_sha256']);
            $table->dropColumn(['details', 'pdf_sha256', 'generated_pdf_path', 'generated_sha256', 'generated_at', 'template_version']);
        });
    }
};
