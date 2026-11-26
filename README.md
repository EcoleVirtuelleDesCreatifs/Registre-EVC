# Registre officiel de vérification des documents — EVC

Service officiel de consultation documentaire de l’**École Virtuelle des Créatifs**.
Il permet à toute personne (employeur, université, administration) de vérifier un certificat, une attestation ou une lettre de recommandation délivrés par l’EVC, à partir de sa référence ou de son QR code.

Application Laravel 11 · PHP 8.2+ · MySQL 8 (ou MariaDB 10.6+).

---

## 1. Ce que fait le registre

| Côté public | Côté administration |
|---|---|
| Vérification par référence (`EVC-DG-2026-0007`) ou par QR code | Tableau de bord, statistiques de délivrance et statistiques de consultation du site (sans cookie) |
| Résultats : **valide**, **révoqué**, **introuvable**, **indisponible** | Circuit de validation en 5 étapes, avec séparation des responsabilités |
| Consultation et téléchargement du PDF officiel (si autorisé) | Numérotation automatique, unique et sans réemploi |
| Politique de confidentialité | Bénéficiaires, liste des étudiants par document (regroupement, recherche, impression, CSV), import Excel/CSV contrôlé ligne par ligne |
| Protection contre les abus (limitation de débit, liens QR non devinables) | Historique inaltérable, rôles, double authentification, sauvegardes, API My EVC |

### Formats de référence

| Document | Nouveau format | Anciennes références (conservées) |
|---|---|---|
| Certificat Design graphique | `EVC-DG-2026-0001` | — |
| Certificat Community Management | `EVC-CM-2026-0001` | — |
| Certificat Infographie (GIA) | `EVC-GIA-2026-0001` | — |
| Attestation de formation | `EVC-ATT-2026-0001` | `ATT-EVC-2026-0001` |
| Attestation de stage | `EVC-STG-2026-0001` | `STG-EVC-2026-0001` |
| Lettre de recommandation | `EVC-REC-2026-0001` | `REC-EVC-2026-0002…0004` |

Les références déjà imprimées restent valides et vérifiables. La numérotation reprend à leur suite : la prochaine attestation de formation sera `EVC-ATT-2026-0002`.
La saisie publique est tolérante : `evc dg 2026 0007`, `EVC–DG–2026–0007` ou `evc_dg_2026_0007` sont reconnus.

---

## 2. Circuit de délivrance

```
Brouillon ──► En vérification ──► (contrôle pédagogique) ──► Approuvé ──► (délivrance : référence) ──► Publié
   ▲               │                                                                                    │
   └── Rejeté ◄────┘                                                                               Révoqué
```

1. **Création** (Secrétariat) — dossier en brouillon, sans référence.
2. **Contrôle pédagogique** (Direction pédagogique) — liste de critères à cocher (situation administrative, évaluations, assiduité, exigences pratiques, règlement). Tous sont requis pour confirmer ; un rejet exige un commentaire.
3. **Approbation** (Direction générale) — la personne qui a contrôlé ne peut pas approuver le même dossier.
4. **Délivrance** (Direction générale) — la référence officielle et le lien QR sont générés à ce moment, dans une transaction MySQL verrouillée. On imprime alors la référence et le QR sur le document, puis on le signe.
5. **Publication** (Direction générale) — exige le PDF définitif. Le document devient vérifiable publiquement.

**Révocation** : réservée à la Direction générale, avec motif obligatoire. Le document reste dans le registre avec le statut « révoqué ». Sa référence n’est jamais réattribuée.

Chaque étape est enregistrée (auteur, date, décision, commentaire, adresse IP) dans un **historique qui ne peut être ni modifié ni supprimé**.

---

## 2 bis. Génération automatique des documents

Le registre produit lui-même les quatre documents, à partir des gabarits officiels EVC (`resources/views/documents`) :

| Document | Format | Rempli automatiquement | Saisi dans le dossier |
|---|---|---|---|
| Certificat professionnel | A4 paysage | nom, formation, date en toutes lettres, référence, QR, sceau de la promotion, signataires DG et DP | mention (facultatif) |
| Attestation de formation | A4 portrait | nom, formation, référence, QR, signataire | civilité, statut (en cours / en finalisation / achevée), date d’inscription, contenu, format |
| Attestation de fin de stage | A4 portrait | nom, durée calculée, référence, QR | civilité, poste, période, missions (« Titre : description », une par ligne), appréciation |
| Lettre de recommandation | A4 portrait, en-tête | en-tête, référence, n° de certificat du titulaire, QR | civilité, signataire, destinataire, corps (texte type proposé et modifiable) |

**Circuit**
1. Au brouillon, la fiche affiche un **aperçu de travail** filigrané « SPÉCIMEN · NON VALABLE ». Il est aussi disponible en PDF.
2. Contrôle et approbation se font sur ce même aperçu.
3. À la **délivrance**, le PDF officiel est généré : référence, QR code et date imprimés. Il est stocké, et son **empreinte SHA-256** est enregistrée.
4. On l’imprime et on le signe à la main (aucune signature ni cachet n’est imprimé). Une version scannée signée peut remplacer le PDF généré : sa propre empreinte est alors enregistrée.
5. Après publication, le PDF est figé : il n’est plus jamais regénéré.

**Contrôle d’intégrité public** : sur la page de vérification, le visiteur peut sélectionner le PDF qu’il a reçu. Son navigateur calcule l’empreinte et la compare à l’exemplaire officiel. Le fichier n’est jamais envoyé.

**Moteur PDF** : dompdf (PHP pur, compatible hébergement mutualisé). Il nécessite l’extension PHP `gd`. Les polices Lora et Libre Baskerville (licence OFL) sont fournies dans `public/documents-assets/fonts`. Les signataires, l’en-tête de lettre et les contenus types se règlent dans `config/evc.php` (`signataires`, `letterhead`, `contenus_defaut`).

Les 12 documents historiques ne sont pas regénérés : leurs PDF d’origine font foi.

---

## 3. Rôles et permissions

| Rôle | Peut |
|---|---|
| **Secrétariat** | créer et modifier les brouillons, gérer les bénéficiaires, joindre les fichiers, importer des brouillons |
| **Direction pédagogique** | tout ce que fait le secrétariat + contrôle pédagogique |
| **Direction générale** | approuver, délivrer, publier, révoquer, reprise historique, historique, utilisateurs, sauvegardes |
| **Administrateur système** | utilisateurs (hors rôles de direction), historique, sauvegardes, consultation |
| **Lecteur** | consultation seule |

La matrice complète est visible dans *Administration › Rôles et utilisateurs* et se règle dans `config/evc.php`.
La **double authentification** (Google Authenticator, Microsoft Authenticator…) est obligatoire pour la Direction générale et l’administrateur système (variable `EVC_2FA_ROLES`), et facultative pour les autres.

---

## 4. Installation sur un hébergement mutualisé (cPanel, o2switch, Hostinger…)

Prérequis : PHP 8.2 ou plus récent avec les extensions `pdo_mysql`, `mbstring`, `zip`, `fileinfo` et `openssl`, une base MySQL, un accès SSH (recommandé) et Composer.

```bash
# 1. Copier le projet hors du dossier public, ex. /home/UTILISATEUR/evc-registre
cd ~/evc-registre
composer install --no-dev --optimize-autoloader

# 2. Configuration
cp .env.example .env
php artisan key:generate
nano .env        # APP_URL, DB_*, ADMIN_EMAIL, ADMIN_PASSWORD (12 caractères min.)

# 3. Base de données + reprise des 12 documents déjà délivrés + compte Direction générale
php artisan migrate --force
php artisan db:seed --force

# 4. Optimisation
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

5. **Dossier public** : faites pointer le sous-domaine (ex. `verification.ecolevirtuelledescreatifs.com`) vers `evc-registre/public`. Si l’hébergeur ne le permet pas, copiez le contenu de `public/` dans `public_html/verification` et corrigez les deux chemins de `index.php` (`__DIR__.'/../'` → `'/home/UTILISATEUR/evc-registre/'`).
6. **HTTPS** : activez le certificat SSL (Let’s Encrypt / AutoSSL).
7. **Tâche planifiée** : voir `deploy/crontab.txt`. Dans *cPanel › Tâches Cron*, ajoutez une tâche « une fois par minute » :
   `cd /home/UTILISATEUR/evc-registre && php artisan schedule:run`
8. Connectez-vous sur `/admin` et configurez la double authentification (elle est demandée dès la première connexion).

Créer les autres comptes :

```bash
php artisan evc:user "Amandine Kra" amandine_kra@ecolevirtuelledescreatifs.com direction_pedagogique
php artisan evc:user "Secrétariat EVC" secretariat@ecolevirtuelledescreatifs.com secretariat
```

Un serveur VPS avec Nginx est aussi possible : voir `deploy/nginx.conf` et `deploy/deploy.sh` pour les mises à jour.

---

## 5. Import Excel / CSV

*Administration › Import Excel / CSV* — modèle téléchargeable sur la page.
Dans Excel : *Enregistrer sous › CSV (séparateur : point-virgule)*. Les encodages UTF-8 et Windows-1252 sont acceptés.

Colonnes : `type ; formation ; titulaire ; promotion ; email ; matricule ; date_delivrance ; reference ; note ; civilite`

Chaque ligne est contrôlée **avant** l’enregistrement : champs obligatoires, dates, types et formations, doublons dans le fichier, doublons dans le registre, titulaires existants (par matricule ou par nom). Un aperçu ligne par ligne s’affiche ; seules les lignes valides sont importées après confirmation.

- **Nouveaux dossiers** : créés en brouillon, puis suivent le circuit normal.
- **Reprise historique** (Direction générale) : documents déjà délivrés, publiés directement avec leur référence imprimée. Les séquences de numérotation sont recalées automatiquement.

---

## 6. API My EVC

Authentification : en-tête `Authorization: Bearer <EVC_API_TOKEN>` (jeton de 32 caractères minimum dans `.env`). Limite : 120 requêtes par minute.

| Méthode | Route | Usage |
|---|---|---|
| `GET` | `/api/v1/documents/{reference}` | Statut d’un document publié |
| `GET` | `/api/v1/apprenants/{matricule}/documents` | Documents d’un apprenant et leur statut (espace My EVC) |
| `POST` | `/api/v1/dossiers` | Créer un dossier en brouillon depuis My EVC |

Exemple de création :

```json
POST /api/v1/dossiers
{ "matricule": "EVC-2026-0153", "nom": "Awa Koné", "email": "awa@example.com",
  "promotion": "2026-A", "type": "certificat", "formation": "DG",
  "commentaire": "Fin de parcours validée sur My EVC" }
```

My EVC ne peut **jamais** délivrer ni publier : il crée un dossier que l’EVC valide dans le circuit habituel.

---

## 7. Sauvegardes et restauration

- **Automatique** : chaque nuit à 02 h 30 (planificateur). Le fichier ZIP contient la base de données au format JSON et tous les PDF et aperçus. Les 30 plus récentes sont conservées (`EVC_BACKUPS_KEEP`).
- **Manuelle** : *Administration › Sauvegardes › Créer une sauvegarde*, ou `php artisan evc:backup`.
- **Hors serveur** : téléchargez régulièrement une sauvegarde. `deploy/sauvegarde.sh` produit en plus un dump SQL natif.
- **Restauration** (serveur uniquement) :

```bash
php artisan evc:restore storage/app/private/backups/registre-evc-2026-11-13_023000.zip
```

Une sauvegarde de sécurité de l’état actuel est créée avant toute restauration.

---

## 8. Sécurité et confidentialité

- Mots de passe hachés (bcrypt), 12 caractères minimum ; secrets de double authentification chiffrés.
- Limitation de débit : 20 vérifications par minute et par IP ; tentatives de connexion et de code limitées.
- Les liens QR utilisent un jeton aléatoire de 40 caractères, distinct de la référence.
- Les adresses IP des vérifications publiques sont enregistrées sous forme hachée.
- En-têtes de sécurité (HSTS en production, `X-Frame-Options`, `nosniff`, `Referrer-Policy`) ; administration non indexée par les moteurs de recherche et jamais mise en cache.
- Le registre public n’affiche que le nom du titulaire, le document, la formation, la date et le statut. E-mail, téléphone, date de naissance et notes internes ne sont jamais publiés.
- Un document qui n’est pas encore publié répond « introuvable » : rien n’est divulgué avant la publication.

Commandes utiles : `php artisan evc:reset-2fa email@…` (téléphone perdu).

---

## 9. Documents repris au démarrage

`php artisan db:seed` importe les 12 documents déjà délivrés, avec leurs références d’origine, leurs PDF et leurs aperçus.

- **11 sont publiés.**
- Le certificat `EVC-DG-2026-0005` (Mourifidate Eunice Mama) est placé **en vérification**, car sa formation est en cours de finalisation. Après l’avis du jury : contrôle pédagogique, approbation, puis publication. Il garde sa référence déjà imprimée.

Les PDF marqués « à remplacer par la version signée » dans leurs notes internes peuvent être remplacés depuis la fiche du document. L’ancien fichier est conservé et le remplacement est tracé.

---

## 10. Tests

```bash
composer install && php artisan test
```

Cette version a été installée et exécutée en conditions réelles avant livraison (PHP 8.3, SQLite) :
- les 9 tests automatisés passent ;
- un parcours complet a été joué dans un navigateur : création par le secrétariat, contrôle pédagogique, approbation, délivrance avec génération du PDF, publication, vérification publique par référence et par QR code, contrôle d’intégrité, révocation, import CSV, sauvegarde et restauration, API My EVC, droits de chaque rôle, double authentification et limitations anti-abus.

**Essai en local** (sans HTTPS) : dans `.env`, mettez `SESSION_SECURE_COOKIE=false`, `DB_CONNECTION=sqlite` et créez `database/database.sqlite`, puis `php artisan migrate --seed` et `php artisan serve`. En production, gardez `SESSION_SECURE_COOKIE=true`.

Les tests couvrent : la normalisation des références, la continuité de numérotation avec les anciens formats, le circuit complet et la séparation des responsabilités, la non-divulgation des documents non publiés, l’immuabilité de l’historique et la protection de l’administration.

Contact : info@ecolevirtuelledescreatifs.com · amandine_kra@ecolevirtuelledescreatifs.com
