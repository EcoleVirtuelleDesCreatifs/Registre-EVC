<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\HolderController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\StudentsByDocumentController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ValidationController;
use App\Http\Controllers\Admin\VisitStatsController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Registre public
|--------------------------------------------------------------------------
*/
Route::middleware('track')->group(function () {
    Route::get('/', [PublicController::class, 'index'])->name('public.index');
    Route::get('/confidentialite', [PublicController::class, 'privacy'])->name('public.privacy');
});

Route::middleware(['throttle:verification', 'track'])->group(function () {
    Route::post('/verifier', [PublicController::class, 'check'])->name('public.check');
    Route::get('/verifier', fn () => redirect()->route('public.index'));
    Route::get('/v/{reference}', [PublicController::class, 'show'])->name('public.show');
    Route::get('/q/{token}', [PublicController::class, 'qr'])->where('token', '[A-Za-z0-9]{20,64}')->name('public.qr');
    Route::get('/v/{reference}/document.pdf', [PublicController::class, 'pdf'])->name('public.pdf');
    Route::get('/v/{reference}/apercu', [PublicController::class, 'preview'])->name('public.preview');
});

/*
|--------------------------------------------------------------------------
| Administration du registre
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login/registre', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login/registre', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.attempt');
        Route::get('/connexion', fn () => redirect()->route('admin.login'));
    });

    Route::middleware(['auth', 'active'])->group(function () {
        Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');

        // Double authentification
        Route::get('/securite/code', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
        Route::post('/securite/code', [TwoFactorController::class, 'verify'])->middleware('throttle:two-factor')->name('2fa.verify');
        Route::get('/securite/configuration', [TwoFactorController::class, 'setup'])->name('2fa.setup');
        Route::post('/securite/configuration', [TwoFactorController::class, 'enable'])->middleware('throttle:two-factor')->name('2fa.enable');
        Route::delete('/securite/configuration', [TwoFactorController::class, 'disable'])->name('2fa.disable');
        Route::get('/securite/mot-de-passe', [TwoFactorController::class, 'passwordForm'])->name('2fa.password');
        Route::put('/securite/mot-de-passe', [TwoFactorController::class, 'updatePassword'])->name('2fa.password.update');

        Route::middleware('2fa')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Registre des documents
            Route::get('/documents/prochain-numero', [DocumentController::class, 'nextReference'])->name('documents.next');
            Route::get('/documents/export', [DocumentController::class, 'export'])->name('documents.export');
            Route::get('/documents/texte-type', [DocumentController::class, 'templateText'])->name('documents.template-text');
            Route::resource('documents', DocumentController::class);
            Route::get('/documents/{document}/fichier/{kind}', [DocumentController::class, 'file'])->whereIn('kind', ['pdf', 'apercu'])->name('documents.file');
            Route::post('/documents/{document}/fichiers', [DocumentController::class, 'attach'])->name('documents.attach');
            Route::get('/documents/{document}/document', [DocumentController::class, 'render'])->name('documents.render');
            Route::post('/documents/{document}/generer', [DocumentController::class, 'regenerate'])->name('documents.generate');
            Route::patch('/documents/{document}/consultation', [DocumentController::class, 'togglePublic'])->name('documents.consultation');

            // Workflow
            Route::post('/documents/{document}/soumettre', [ValidationController::class, 'submit'])->name('documents.submit');
            Route::post('/documents/{document}/controle', [ValidationController::class, 'control'])->name('documents.control');
            Route::post('/documents/{document}/approbation', [ValidationController::class, 'approve'])->name('documents.approve');
            Route::post('/documents/{document}/delivrance', [ValidationController::class, 'deliver'])->name('documents.deliver');
            Route::post('/documents/{document}/publication', [ValidationController::class, 'publish'])->name('documents.publish');
            Route::post('/documents/{document}/revocation', [ValidationController::class, 'revoke'])->name('documents.revoke');
            Route::get('/validations', [ValidationController::class, 'index'])->name('validations.index');

            // Bénéficiaires
            Route::resource('beneficiaires', HolderController::class)->parameters(['beneficiaires' => 'holder']);

            // Étudiants par document
            Route::get('/etudiants-par-document', [StudentsByDocumentController::class, 'index'])->name('par-document.index');
            Route::get('/etudiants-par-document/export', [StudentsByDocumentController::class, 'export'])->name('par-document.export');
            Route::delete('/etudiants-par-document', [StudentsByDocumentController::class, 'destroy'])->name('par-document.destroy');

            // Statistiques de consultation du registre public
            Route::get('/statistiques', [VisitStatsController::class, 'index'])->name('stats.visits');

            // Import Excel / CSV
            Route::get('/import', [ImportController::class, 'create'])->name('imports.create');
            Route::post('/import/analyse', [ImportController::class, 'analyse'])->name('imports.analyse');
            Route::post('/import/confirmer', [ImportController::class, 'store'])->name('imports.store');
            Route::get('/import/modele.csv', [ImportController::class, 'template'])->name('imports.template');

            // Historique, utilisateurs, sauvegardes
            Route::get('/historique', [AuditController::class, 'index'])->name('audit.index');
            Route::resource('utilisateurs', UserController::class)->parameters(['utilisateurs' => 'user'])->except(['show', 'destroy']);
            Route::get('/sauvegardes', [BackupController::class, 'index'])->name('backups.index');
            Route::post('/sauvegardes', [BackupController::class, 'store'])->name('backups.store');
            Route::get('/sauvegardes/{name}', [BackupController::class, 'download'])->where('name', 'registre-evc-[0-9_\-]+\.zip')->name('backups.download');
        });
    });
});
