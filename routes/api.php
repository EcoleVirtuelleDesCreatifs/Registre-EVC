<?php

use App\Http\Controllers\Api\MyEvcController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API d'intégration My EVC (préfixe /api/v1)
|--------------------------------------------------------------------------
| Authentification : en-tête « Authorization: Bearer <EVC_API_TOKEN> »
*/
Route::prefix('v1')->middleware(['api.token', 'throttle:api'])->group(function () {
    Route::get('/documents/{reference}', [MyEvcController::class, 'show']);
    Route::get('/apprenants/{externalId}/documents', [MyEvcController::class, 'holderDocuments']);
    Route::post('/dossiers', [MyEvcController::class, 'storeDossier']);
});
