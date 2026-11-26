<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mesure d'audience du registre public, sans cookie :
     * aucune adresse IP n'est conservée, seulement une empreinte irréversible.
     */
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('page', 20)->index();          // accueil | verification | qr | pdf | confidentialite
            $table->string('visitor_hash', 64)->index();  // empreinte IP + navigateur (irréversible)
            $table->string('device', 10)->nullable();     // ordinateur | mobile | tablette
            $table->string('browser', 20)->nullable();
            $table->string('os', 20)->nullable();
            $table->string('lang', 5)->nullable();
            $table->string('referrer', 120)->nullable();  // domaine d'origine (null = accès direct)
            $table->unsignedSmallInteger('status')->default(200);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
