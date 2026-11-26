<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    public const UPDATED_AT = null;

    /** Libellés des pages publiques suivies. */
    public const PAGES = [
        'accueil' => 'Accueil (formulaire de vérification)',
        'verification' => 'Résultat par référence',
        'qr' => 'Résultat par QR code',
        'pdf' => 'Consultation du PDF officiel',
        'confidentialite' => 'Politique de confidentialité',
    ];

    protected $fillable = ['page', 'visitor_hash', 'device', 'browser', 'os', 'lang', 'referrer', 'status'];
}
