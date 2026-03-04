# CLAUDE.md — Contexte module infrasdiscount

## Aperçu (Overview)

`infrasdiscount` est un module externe Dolibarr de gestion avancée des remises sur documents commerciaux :

- remises multiples en cascade (pourcentage cumulé),
- remises en montant fixe avec répartition prorata par taux de TVA,
- remises par valeur cible TTC (calcul automatique des montants),
- support multi-devises,
- règles de remises automatiques sur validation de commande.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand
- Numéro module : `500058`
- Licence : GPL v3+
- Compatibilité Dolibarr : `15.0.0` à `22.0.2`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `15.3.1` (2026-02)
- Dépendance obligatoire : aucune (extension PHP `xml` requise)
- Emplacement : `htdocs/custom/infrasdiscount/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrasdiscount/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrasdiscountsetup.php
├── class/
│   └── actions_infrasdiscount.class.php
├── config.php
├── core/
│   ├── lib/
│   │   ├── infrasdiscount.lib.php
│   │   └── infrasdiscountAdmin.lib.php
│   ├── modules/
│   │   └── modinfrasdiscount.class.php
│   └── triggers/
│       └── interface_99_modinfrasdiscount_Infrasdiscounttrigger.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrasdiscount.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── img/
├── langs/
│   ├── en_US/infrasdiscount.lang
│   ├── es_ES/infrasdiscount.lang
│   ├── fr_FR/infrasdiscount.lang
│   └── it_IT/infrasdiscount.lang
└── sql/
    └── data.sql
```

## Descripteur module (Module descriptor : `modinfrasdiscount`)

Dans `core/modules/modinfrasdiscount.class.php` :

- **Module parts** :
	- hooks : `propalcard`, `ordercard`, `invoicecard`
	- triggers : 1
	- CSS : `/infrasdiscount/css/infrasdiscount.css.php`
- **Dépendances** : aucune dépendance obligatoire
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **ExtraFields** : `specialtype` (int) sur `propaldet`, `commandedet`, `facturedet`
- **Constantes** : `INVOICE_KEEP_DISCOUNT_LINES_AS_IN_ORIGIN = 1` (préserve les lignes de remise lors de la transformation de documents)
- **Permissions** : 4 permissions
	- `paramMenu` (défaut : activée)
	- `paramInfrasdiscount`
	- `paramBkpRest`
	- `use`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrasdiscount/sql/')`)
2. Restauration des constantes module (`infrasdiscount_restore_module`)
3. Initialisation de constantes clés :
	 - `INFRASDISCOUNT_DOL_VERSION`
	 - `INFRASDISCOUNT_MAIN_VERSION`
4. Création des ExtraFields `specialtype` sur `propaldet`, `commandedet`, `facturedet`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue :

- sauvegarde module (`infrasdiscount_bkup_module`),
- suppression des constantes `INFRASDISCOUNT_%` de l'entité courante.

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `actions_infrasdiscount.class.php` pour les hooks d'injection des boutons de remise, formulaires popup et logique CRUD sur les documents,
- `infrasdiscount.lib.php` pour le moteur de calcul des remises (cascade, prorata, multi-devises, valeur cible),
- `infrasdiscountAdmin.lib.php` pour les fonctions admin (onglets, changelog, backup/restore, vérification de mise à jour),
- le trigger `interface_99_modinfrasdiscount_Infrasdiscounttrigger.class.php` pour les remises automatiques sur validation de commande et appel API de paiement.

### Types de remises

Trois modes de calcul :

1. **Pourcentage en cascade** (`specialtype=1` produits, `specialtype=2` services) : les remises se cumulent sur le total ou sous-total du document (ex. 10% puis 5% = 14,5% au total)
2. **Montant fixe** (`specialtype=3` produits, `specialtype=4` services) : montant distribué au prorata entre produits et services par taux de TVA pour préserver la cohérence comptable
3. **Valeur cible** : calcul automatique des remises en montant pour atteindre un TTC cible, répartition prorata produits/services par taux de TVA

### Gestion multi-devises

Toutes les fonctions de remise supportent le multi-devises lorsque le module Dolibarr `multicurrency` est activé :

- `infrasdiscount_multicurrency_enabled()` — vérifie activation et taux de change valide
- `infrasdiscount_multicurrency_rate()` — retourne le taux de change ou 1.0
- `infrasdiscount_to_foreign()` / `infrasdiscount_to_base()` — conversions avec protection division par zéro

### Préservation des marges

Les lignes de remise sont associées à des références produit/service configurées (`INFRASDISCOUNT_REM_PRODUCT` / `INFRASDISCOUNT_REM_SERVICE`) pour :

- assurer les calculs de marge sur documents,
- permettre une catégorisation comptable spécifique,
- suivre les remises par type (produit vs service).

## Hooks et comportement (Hook behavior)

La classe `ActionsInfraSDiscount` intervient sur les contextes `propalcard`, `ordercard`, `invoicecard` :

- `addMoreActionsButtons` : injection des boutons de remise sur les fiches document,
- `doActions` : traitement des soumissions de formulaire de remise (création, modification, suppression, recalcul),
- `formObjectOptions` : injection du formulaire popup de remise dans la fiche document.

## Trigger (Trigger behavior)

Le trigger `interface_99_modinfrasdiscount_Infrasdiscounttrigger` écoute les actions suivantes :

- `ORDER_VALIDATE` : déclenchement des remises automatiques sur validation de commande,
- `BILL_PAYED` : appel API de paiement Sort&Group (si activé),
- `LINEPROPAL_*`, `LINEORDER_*`, `LINEBILL_*` : recalcul automatique des remises après ajout, modification ou suppression de lignes,
- nettoyage automatique des lignes de remise à montant nul après recalcul,
- régénération PDF après recalcul des remises (respecte `MAIN_DISABLE_PDF_AUTOUPDATE`),
- prévention de récursion infinie via flag statique `$isUpdating` dans un bloc `try/finally`.

## Données / SQL (Data model)

Le module ne crée aucune table SQL propre. Toute la configuration est stockée dans `llx_const`.

Les métadonnées de remise sont stockées via ExtraFields sur les lignes de documents :

| ExtraField | Table cible    | Type | Valeurs                                                  |
|------------|----------------|------|----------------------------------------------------------|
| `specialtype` | `propaldet`    | int  | 1=% produit, 2=% service, 3=montant produit, 4=montant service |
| `specialtype` | `commandedet`  | int  | idem                                                     |
| `specialtype` | `facturedet`   | int  | idem                                                     |

Les lignes standards utilisent `special_code` pour identifier les lignes spéciales (ex. sous-totaux du module Subtotal), exclues des calculs de remise.

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `INFRASDISCOUNT_ON_PROPALE` / `INFRASDISCOUNT_ON_ORDER` / `INFRASDISCOUNT_ON_INVOICE` — activation par type de document
- `INFRASDISCOUNT_LABEL_PROPAL` / `INFRASDISCOUNT_LABEL_ORDER` / `INFRASDISCOUNT_LABEL_INVOICE` — libellés par défaut
- `INFRASDISCOUNT_REM_PRODUCT` / `INFRASDISCOUNT_REM_SERVICE` — références produit/service associées aux remises
- `INFRASDISCOUNT_DEFAULT_REM_VALUE` — valeur par défaut dans les formulaires (10,00)
- `INFRASDISCOUNT_AUTO_DISCOUNT_ENABLE` — activation des remises automatiques
- `INFRASDISCOUNT_AUTO_DISCOUNT_NB_ORDER` — limite de commandes par client pour remise auto
- `INFRASDISCOUNT_AUTO_DISCOUNT_QTY` — quantité offerte par référence produit
- `INFRASDISCOUNT_AUTO_DISCOUNT_PRODUCTS` — liste de références produit éligibles (séparées par virgule)
- `INFRASDISCOUNT_AUTO_DISCOUNT_PONDERAT` — référence produit utilisée comme pondérateur
- `INFRASDISCOUNT_AUTO_DISCOUNT_COMMENT` — commentaire ajouté aux descriptions de remise auto
- `INFRASDISCOUNT_SORTANDGROUP` — utilisation de l'API de payement SortAndGroup
- `INFRASDISCOUNT_OAUTH_CLIENT_ID` / `INFRASDISCOUNT_OAUTH_CLIENT_SECRET` / `INFRASDISCOUNT_OAUTH_URL` — identifiants OAuth2 pour l'API de paiement (externalisés en base)

Point de vigilance : les constantes OAuth2 doivent être configurées en base via `dolibarr_set_const` et ne jamais être écrites en dur dans le code.

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / hooks / triggers :

1. Désactiver puis réactiver le module
2. Vérifier les ExtraFields `specialtype` sur `propaldet`, `commandedet`, `facturedet`
3. Vérifier les constantes module (`INFRASDISCOUNT_*`)
4. Tester la création de remise (pourcentage, montant, valeur cible) sur un devis, commande et facture
5. Tester le recalcul automatique après ajout/suppression de lignes
6. Si remises automatiques activées : tester la validation d'une commande

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrasdiscount_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module est auto-désactivé si la version Dolibarr est inférieure au minimum requis
- La position des lignes de remise dans le document compte pour le calcul en cascade
- Les lignes du module Subtotal (titres, sous-totaux, textes libres) sont exclues via `infrasdiscount_isSubtotalLine()`
- La constante `INVOICE_KEEP_DISCOUNT_LINES_AS_IN_ORIGIN` est activée automatiquement pour préserver les remises lors de la transformation devis → commande → facture

## Dernières mises à jour (Recent updates)

- `15.3.2` (2026-03) : Modification du trigger `BILL_PAYED` ajout d'une option pour l'utilisation du lien SortAndGroup
- `15.3.1` (2026-02) : correction du trigger `BILL_PAYED` quand l'authentification Sort&Group n'est pas activée
- `15.3.0` (2026-02) : durcissements sécurité — sanitisation GETPOST, protection XSS sur `PHP_SELF` et `SERVER_SOFTWARE`, restriction regex des constantes, externalisation OAuth2, contrôles permissions, remplacement `addslashes()` par `$db->escape()`
- `15.3.0` (2026-02) : correction de l'appel `infraspackplus_print_input()` → `infrasdiscount_print_input()`, du modulepart backup et du slash manquant dans `dol_buildpath()`
- `15.3.0` (2026-02) : isolation du cookie JS (`infrasdiscount_tblPSexp`) et alignement sur les conventions des modules InfraS
- `15.3.0` (2026-02) : remplacement syntaxe dépréciée `$user->rights` par `$user->hasRight()`
- `15.3.0` (2026-02) : ajout du fichier CLAUDE.md
