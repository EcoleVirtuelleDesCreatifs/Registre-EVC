<?php

return [

    'school' => 'École Virtuelle des Créatifs',

    'contact_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('EVC_CONTACT_EMAILS', 'info@ecolevirtuelledescreatifs.com,amandine_kra@ecolevirtuelledescreatifs.com'))
    ))),

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrateur EVC'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Authentification à deux facteurs obligatoire pour ces rôles
    'two_factor_required_roles' => array_filter(explode(',', env('EVC_2FA_ROLES', 'direction_generale,admin_systeme'))),

    // Jeton d'accès de l'API My EVC (laisser vide pour désactiver l'API)
    'api_token' => env('EVC_API_TOKEN'),

    // Sauvegardes conservées
    'backups_keep' => (int) env('EVC_BACKUPS_KEEP', 30),

    /*
    |--------------------------------------------------------------------------
    | Rôles et permissions
    |--------------------------------------------------------------------------
    | Séparation des pouvoirs : celui qui saisit n'approuve pas, le contrôle
    | pédagogique précède l'approbation, l'administrateur technique ne délivre rien.
    */
    'roles' => [
        'secretariat' => 'Secrétariat / Administration',
        'direction_pedagogique' => 'Direction pédagogique',
        'direction_generale' => 'Direction générale',
        'admin_systeme' => 'Administrateur technique',
        'lecteur' => 'Lecteur (consultation)',
    ],

    'permissions' => [
        'documents.view' => ['secretariat', 'direction_pedagogique', 'direction_generale', 'admin_systeme', 'lecteur'],
        'documents.create' => ['secretariat', 'direction_pedagogique', 'direction_generale'],
        'documents.edit' => ['secretariat', 'direction_pedagogique', 'direction_generale'],
        'documents.submit' => ['secretariat', 'direction_pedagogique', 'direction_generale'],
        'documents.control' => ['direction_pedagogique'],
        'documents.approve' => ['direction_generale'],
        'documents.deliver' => ['direction_generale'],
        'documents.publish' => ['direction_generale'],
        'documents.revoke' => ['direction_generale'],
        'documents.attach' => ['secretariat', 'direction_pedagogique', 'direction_generale'],
        'holders.manage' => ['secretariat', 'direction_pedagogique', 'direction_generale'],
        'imports.drafts' => ['secretariat', 'direction_pedagogique', 'direction_generale'],
        'imports.history' => ['direction_generale'],
        'audit.view' => ['direction_generale', 'admin_systeme'],
        'users.manage' => ['direction_generale', 'admin_systeme'],
        'backups.manage' => ['direction_generale', 'admin_systeme'],
        'stats.view' => ['secretariat', 'direction_pedagogique', 'direction_generale', 'admin_systeme', 'lecteur'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Statuts internes et libellés
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'brouillon' => 'Brouillon',
        'en_verification' => 'En vérification',
        'approuve' => 'Approuvé',
        'publie' => 'Publié',
        'rejete' => 'Rejeté',
        'revoque' => 'Révoqué',
    ],

    'formations' => [
        'DG' => 'Infographie et Design Graphique',
        'CM' => 'Community Management et Social Media Management',
        'GIA' => 'Bureautique et informatique (GIA)',
    ],

    /*
    | Critères du contrôle pédagogique (tous obligatoires pour un certificat).
    */
    'criteres_certificat' => [
        'situation_administrative' => 'Situation administrative en règle (inscription, paiements)',
        'evaluations' => 'Évaluations et travaux pratiques validés',
        'assiduite' => 'Assiduité suffisante',
        'exigences_pratiques' => 'Exigences pratiques remplies (Studio Creative, RECO 60…)',
        'reglement' => 'Respect du règlement intérieur',
    ],

    'criteres_autres' => [
        'informations_verifiees' => 'Informations vérifiées dans le dossier de l’apprenant',
    ],

    'max_pdf_kb' => 15360,
    'max_image_kb' => 5120,
    'max_import_kb' => 2048,

    /*
    |--------------------------------------------------------------------------
    | Génération automatique des documents
    |--------------------------------------------------------------------------
    | Les documents sont produits à partir des gabarits resources/views/documents
    | (mise en page officielle EVC). Le PDF est figé et son empreinte SHA-256
    | enregistrée au moment de la délivrance.
    */
    'template_version' => '2026.1',

    'verification_host' => env('EVC_VERIFICATION_HOST', 'verification.ecolevirtuelledescreatifs.com'),

    // Cérémonie de remise des certificats (présence obligatoire en Côte d'Ivoire)
    'ceremonie' => [
        'date' => env('EVC_CEREMONIE_DATE'),
        'lieu' => env('EVC_CEREMONIE_LIEU', 'Abidjan'),
    ],

    'signataires' => [
        'dg' => ['name' => 'Bossombra Bile', 'title' => 'Directeur Général', 'feminine' => false, 'service' => 'Direction générale'],
        'dp' => ['name' => 'Amandine Kra', 'title' => 'Directrice Pédagogique', 'feminine' => true, 'service' => 'Direction pédagogique'],
    ],

    'letterhead' => [
        'École Virtuelle des Créatifs · Bloom Square Akwaba, Palmeraie, Abidjan',
        '+225 07 47 25 95 07 · info@ecolevirtuelledescreatifs.com',
    ],

    'contenus_defaut' => [
        'DG' => 'Adobe Photoshop, Illustrator et InDesign',
        'CM' => '',
        'GIA' => '',
    ],

    'statuts_formation' => [
        'en_cours' => 'En cours',
        'finalisation' => 'En cours de finalisation',
        'achevee' => 'Achevée',
    ],
];
