<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('label');
            // Code de la référence : null = code de la formation (certificats)
            $table->string('ref_code', 10)->nullable();
            $table->boolean('requires_formation')->default(false);
            $table->boolean('is_certificate')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('document_types')->insert([
            ['code' => 'certificat', 'label' => 'Certificat professionnel', 'ref_code' => null, 'requires_formation' => true, 'is_certificate' => true, 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'attestation_formation', 'label' => 'Attestation de formation', 'ref_code' => 'ATT', 'requires_formation' => true, 'is_certificate' => false, 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'attestation_stage', 'label' => 'Attestation de fin de stage', 'ref_code' => 'STG', 'requires_formation' => false, 'is_certificate' => false, 'sort' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'recommandation', 'label' => 'Lettre de recommandation', 'ref_code' => 'REC', 'requires_formation' => false, 'is_certificate' => false, 'sort' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
