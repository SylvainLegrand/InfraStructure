# CLAUDE.md — Contexte module infraspackplus

## Aperçu (Overview)

`infraspackplus` est un module externe Dolibarr orienté génération documentaire PDF avancée :

- modèles PDF étendus pour de nombreux objets (devis, commandes, factures, contrats, expéditions, etc.),
- gestion multi-adresses émetteur,
- gestion des mentions et notes publiques via dictionnaires,
- options avancées de génération (CGV/CGA/CGI, signatures, images, filigranes, colonnes),
- mécanisme de substitution de pages selon version Dolibarr.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS
- Numéro module : `550000`
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `23.0.4`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `18.14.8` (2026-02)
- Dépendance obligatoire : `modECM`
- Emplacement : `htdocs/custom/infraspackplus/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infraspackplus/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── adresses.php
│   ├── changelog.php
│   ├── dictionaries.php
│   ├── extrafields.php
│   ├── generalpdf.php
│   ├── generation.php
│   ├── images.php
│   ├── infrasplussetup.php
│   ├── mentions.php
│   └── notes.php
├── backport/
├── class/
│   ├── actions_infraspackplus.class.php
│   └── address.class.php
├── comm/
├── config.php
├── core/
│   ├── lib/
│   │   ├── infraspackplus.lib.php
│   │   ├── infraspackplus.pdf.lib.php
│   │   └── infraspackplusAdmin.lib.php
│   ├── modules/
│   │   └── modinfraspackplus.class.php
│   ├── tpl/
│   └── triggers/
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infraspackplus.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── fonts/
├── img/
├── includes/
├── langs/
│   ├── en_US/infraspackplus.lang
│   ├── es_ES/infraspackplus.lang
│   ├── fr_FR/infraspackplus.lang
│   └── it_IT/infraspackplus.lang
├── sql/
│   ├── clean_from_infraspack.sql
│   ├── data.sql
│   ├── llx_c_infraspackplus_mention.sql
│   ├── llx_c_infraspackplus_mention.key.sql
│   ├── llx_c_infraspackplus_note.sql
│   ├── llx_c_infraspackplus_note.key.sql
│   ├── llx_infraspackplus_societe_address.sql
│   ├── llx_infraspackplus_societe_address.key.sql
│   ├── llx_societe-logo_emet.sql
│   └── updates.sql
├── substitutionpages/
└── ttf/
```

## Descripteur module (Module descriptor : `modinfraspackplus`)

Dans `core/modules/modinfraspackplus.class.php` :

- **Module parts** :
	- `models`, `tpl`, `triggers`
	- hooks : `main`, `login`, `formfile`, `pdfgeneration`, `thirdpartycard`, `globalcard`, contextes `*note`
	- CSS : `/infraspackplus/css/infraspackplus.css.php`
- **Dépendances** : `modECM`
- **Dictionnaires** : 2 dictionnaires (`c_infraspackplus_mention`, `c_infraspackplus_note`)
- **Boxes** : aucune
- **Cron** : aucune tâche dans le descripteur
- **Permissions** : 13 permissions
	- `paramMenu`, `paramDolibarr`, `paramInfraSPlus`, `paramImages`, `paramAdresses`
	- `paramExtraFields`, `paramMentions`, `paramNotes`, `paramDict`
	- `paramGeneration`, `paramBkpRest`, `paramLastOpt`, `paramCGV`

### Initialisation (Lifecycle : `init()`)

`init()` effectue notamment :

1. Chargement SQL module
2. Synchronisation de ressources (polices, templates selon version)
3. Migration/contrôle de configuration
4. Restauration de constantes sauvegardées
5. Activation des modèles et mécanismes liés

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue sauvegarde module, nettoyage des constantes et retrait des éléments injectés par le module.

## Fonctionnement principal (Core behavior)

Le module s’appuie sur :

- `actions_infraspackplus.class.php` pour les hooks de génération PDF et substitutions,
- `infraspackplus.lib.php` pour la logique transverse,
- `infraspackplus.pdf.lib.php` pour le rendu PDF,
- `address.class.php` pour la gestion multi-adresses tiers,
- le trigger `interface_90_modinfraspackplus_Infraspackplustrigger.class.php` (évènements société).

## Hooks et comportement (Hook behavior)

La classe `actions_infraspackplus` intervient principalement sur :

- les contextes de génération PDF (`pdfgeneration`) avant/après production,
- les formulaires de documents (`formfile`) pour enrichir les options,
- les contextes tiers/globaux (`thirdpartycard`, `globalcard`) pour les informations complémentaires,
- les contextes de notes (`*note`) et la logique transversale (`main`, `login`) selon configuration.

## Données / SQL (Data model)

Tables principales :

- `llx_infraspackplus_societe_address`
- `llx_c_infraspackplus_mention`
- `llx_c_infraspackplus_note`

Éléments SQL importants :

- `llx_societe-logo_emet.sql` (colonne `logo_emet`),
- `data.sql` (constantes module et données dictionnaires),
- `updates.sql` (évolutions),
- `clean_from_infraspack.sql` (migration/historique).

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `INFRASPLUS_*` (famille principale de paramètres d’affichage et de génération),
- constantes liées aux options de documents (CGV/CGA/CGI, signatures, images, colonnes),
- constantes liées aux dictionnaires de mentions/notes,
- constantes de versions/migrations utilisées au chargement du module.

Point de vigilance : conserver la cohérence globale des constantes `INFRASPLUS_*` avant toute modification massive.

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / hooks / templates PDF :

1. Désactiver puis réactiver le module
2. Vérifier tables et dictionnaires (`mention`, `note`, `societe_address`)
3. Vérifier chargement des modèles PDF InfraSPlus
4. Vérifier hooks de génération (`formBuilddocOptions`, `beforePDFCreation`, `afterPDFCreation`)
5. Vérifier un cas de génération réel (devis/facture) avec options actives

## Points d’attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infraspackplus_getLocalVersionMinDoli`)
- L’extension PHP XML est nécessaire
- Le module applique des substitutions de pages selon version Dolibarr (répertoire `substitutionpages/`)
- Les constantes `INFRASPLUS_*` sont nombreuses ; éviter les changements massifs sans test de génération PDF

## Dernières mises à jour (Recent updates)

- `18.14.8` (2026-02) : durcissements sécurité sur les URLs/formulaires basés sur `PHP_SELF` (échappement HTML)
- `18.14.8` (2026-02) : échappement de l’affichage de `SERVER_SOFTWARE`
- `18.14.8` (2026-02) : typage `GETPOST(..., 'alpha')` sur les options radio de génération
- `18.14.8` (2026-02) : isolation du cookie JS de l'état des panneaux (`infraspackplus_tblPSexp` au lieu de `tblPSexp`)
- `18.14.8` (2026-02) : variable `cookieName` déplacée au scope script (hors `jQuery(document).ready()`) pour accès inter-closures