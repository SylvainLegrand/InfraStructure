# CLAUDE.md — Contexte module infrastructure

## Aperçu (Overview)

`infrastructure` est un module externe Dolibarr de structuration et organisation des documents commerciaux :

- ajout de titres, sous-titres et sous-totaux (jusqu'à 9 niveaux hiérarchiques) sur propositions, commandes, factures et documents fournisseurs,
- insertion de lignes de texte libre entre les lignes standards,
- réorganisation des lignes par glisser-déposer (drag & drop),
- options d'affichage PDF : masquage, impression en liste ou en mode condensé, répétition d'en-tête,
- dictionnaire de textes libres réutilisables (`c_infrastructure_free_text`),
- sommaire rapide flottant (depuis 3.30.1) pour naviguer entre titres dans les documents longs,
- support des factures de situation (avancement de travaux) avec préservation des structures.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand (fork maintenu, basé sur l'original ATM Consulting)
- Numéro module : `550090`
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `23.x.x`
- Compatibilité PHP : `7.0` à `8.4`
- Dernière version locale : `18.3.0` (2026-04)
- Schéma de numérotation : depuis `18.1.0`, le module aligne sa version majeure sur la version minimale de Dolibarr supportée (même convention que `infraspackplus`). Format : `<dolibarrMin>.<mineur>.<patch>`. Les versions antérieures (jusqu'à `3.30.1`) suivaient une numérotation indépendante.
- Dépendance obligatoire : aucune
- Conflit : module **Milestone/Jalon** (iNodbox) — les deux modules ne peuvent pas être activés simultanément
- Emplacement : `htdocs/custom/infrastructure/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrastructure/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrastructuresetup.php
├── backport/
│   └── v19/
│       └── core/
│           └── class/
│               └── commonhookactions.class.php
├── class/
│   ├── actions_infrastructure.class.php
│   ├── api_infrastructure.class.php
│   ├── infrastructure.class.php
│   ├── staticPdf.model.php
│   └── subInfrastructureJsonResponse.class.php
├── config.php
├── core/
│   ├── lib/
│   │   ├── infrastructure.lib.php
│   │   ├── infrastructureAdmin.lib.php
│   │   └── infrastructureMigrateSubtotal.lib.php
│   ├── modules/
│   │   └── modInfrastructure.class.php
│   ├── tpl/
│   │   ├── infrastructureline_edit.tpl.php
│   │   ├── infrastructureline_infrastructure.tpl.php
│   │   ├── infrastructureline_row_document.tpl.php
│   │   ├── infrastructureline_row_shipment.tpl.php
│   │   ├── infrastructureline_row_shipping.tpl.php
│   │   ├── infrastructureline_view.tpl.php
│   │   └── originproductline.tpl.php
│   └── triggers/
│       └── interface_90_modInfrastructure_infrastructuretrigger.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── puentebold.ttf
│   ├── infrastructure.css.php
│   └── summary-menu.css.php
├── docs/changelog.xml
├── img/
├── js/
│   ├── infrastructure.lib.js
│   └── summary-menu.js
├── langs/
│   ├── en_US/infrastructure.lang
│   ├── es_ES/infrastructure.lang
│   ├── fr_FR/infrastructure.lang
│   └── it_IT/infrastructure.lang
├── script/
│   ├── interface.php
│   └── migrate-from-subtotal.php
└── sql/
    ├── data.sql
    ├── llx_c_infrastructure_free_text.sql
    └── update.sql
```

## Descripteur module (Module descriptor : `modInfrastructure`)

Dans `core/modules/modInfrastructure.class.php` :

- **Module parts** :
	- `triggers` : 1 trigger (priorité 90)
	- `tpl` : override `originproductline.tpl.php` + 6 templates dédiés (`infrastructureline_*.tpl.php`)
	- `css` : `/infrastructure/css/infrastructure.css.php` (le CSS `summary-menu.css.php` est chargé à la volée par `actions_infrastructure`)
	- `hooks` : 25 contextes (`invoicecard`, `invoicesuppliercard`, `propalcard`, `supplier_proposalcard`, `ordercard`, `ordersuppliercard`, `odtgeneration`, `orderstoinvoice`, `orderstoinvoicesupplier`, `admin`, `invoicereccard`, `consumptionthirdparty`, `ordershipmentcard`, `expeditioncard`, `deliverycard`, `paiementcard`, `referencelettersinstacecard`, `shippableorderlist`, `propallist`, `orderlist`, `invoicelist`, `supplierorderlist`, `supplierinvoicelist`, `cron`, `pdfgeneration`, `checkmarginlist`)
- **Dépendances** : aucune
- **Conflit** : `modMilestone` (iNodbox) — `conflictwith = array('modMilestone')`
- **Dictionnaires** : 1 (`c_infrastructure_free_text` — colonnes `rowid`, `label`, `content`, `active`, `entity`)
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : aucune (accès via les droits Dolibarr standards des documents concernés)
- **ExtraFields** : créés automatiquement à l'activation sur `propaldet`, `commandedet`, `facturedet`, `supplier_proposaldet`, `commande_fournisseurdet`, `facture_fourn_det`
- **Famille** : `Modules InfraS` (ou `easya` si la constante `EASYA_VERSION` est présente)
- **Constantes prédéfinies** (dans `$this->const`) :
	- `INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES` (défaut `I`)
	- `INFRASTRUCTURE_ALLOW_ADD_BLOCK` / `INFRASTRUCTURE_ALLOW_EDIT_BLOCK` / `INFRASTRUCTURE_ALLOW_REMOVE_BLOCK` (défaut `1`)
	- `INFRASTRUCTURE_TITLE_STYLE` (défaut `BU`) — sert aussi de fallback PDF si `INFRASTRUCTURE_PDF_TITLE_STYLE` est vide
	- `INFRASTRUCTURE_TOTAL_STYLE` (défaut `B`) — idem fallback PDF si `INFRASTRUCTURE_PDF_TOTAL_STYLE` est vide
	- D'autres constantes par défaut (~30) sont chargées via `sql/data.sql` lors de l'activation

### Initialisation (Lifecycle : `init()`)

`init()` effectue dans l'ordre :

1. Vérification du conflit avec `modMilestone` (blocage si activé)
2. Chargement SQL via `_load_tables('/infrastructure/sql/')` : création de `llx_c_infrastructure_free_text` et insertion des constantes `data.sql`
3. **Migration depuis le module `subtotal` si `isModEnabled('subtotal')`** (voir section dédiée). En cas d'échec, `$this->error` est positionné et `init()` retourne `0` → activation annulée
4. Création des ExtraFields sur les 6 tables de lignes :
	- `show_total_ht` / `show_reduc` / `infrastructure_show_qty` (sur sous-totaux)
	- `hideblock` / `show_table_header_before` / `print_as_list` / `print_condensed` (sur titres)
5. Désactivation conditionnelle du sommaire rapide si `oblyon` actif avec `MAIN_MENU_INVERT`
6. Appel de `$this->_init()` standard

### Désactivation (Lifecycle : `remove()`)

`remove()` délègue à `_remove()` sans nettoyage spécifique : ExtraFields et constantes sont conservés pour permettre une réactivation sans perte de données.

### Migration depuis le module subtotal (Migration from subtotal)

Le module `infrastructure` est un fork/remplacement du module `subtotal` (ATM Consulting). À l'activation, si `isModEnabled('subtotal')` est vrai, une migration automatique est déclenchée depuis `init()`.

**Fichiers impliqués** :

- `core/lib/infrastructureMigrateSubtotal.lib.php` :
	- `infrastructure_migrateFromSubtotal($db, $conf, $dryRun, $logger)` — migration atomique (transaction), retour `['success' => bool, 'errors' => string[]]`
	- `infrastructure_cleanupSubtotal($db, $conf, $logger)` — désactivation + nettoyage, retour `1` / `0`
- `script/migrate-from-subtotal.php` — wrapper CLI/web (admin requis, simulation par défaut)

**Séquence dans `init()`** : dry-run → exécution réelle → cleanup. Toute étape qui échoue annule l'activation.

**Opérations de migration** :

| Étape | Opération |
|-------|-----------|
| 1/3 — Constantes `llx_const` | `SUBTOTAL_*` → `INFRASTRUCTURE_*` (gestion des doubles occurrences `SUBTOTAL_SUBTOTAL_*`). Cas particulier : `NO_TITLE_SHOW_ON_EXPED_GENERATION` → `INFRASTRUCTURE_NO_TITLE_SHOW_ON_EXPED_GENERATION`. Si la constante cible existe déjà pour la même entity, l'ancienne est supprimée |
| 2/3 — ExtraField `subtotal_show_qty` → `infrastructure_show_qty` | Sur les 6 tables de lignes : `UPDATE llx_extrafields` + `ALTER TABLE ..._extrafields CHANGE COLUMN`. Les autres ExtraFields (`show_total_ht`, `show_reduc`, `hideblock`, `show_table_header_before`, `print_as_list`, `print_condensed`) ont des noms identiques |
| 3/3 — Dictionnaire `c_subtotal_free_text` → `c_infrastructure_free_text` | Copie des lignes absentes de la cible (dédoublonnage `label` + `entity`) |

**Opérations de cleanup** : appel de `modSubtotal->remove('')`, suppression des résidus `MAIN_MODULE_SUBTOTAL*` et `SUBTOTAL_*` dans `llx_const`, `DROP TABLE IF EXISTS llx_c_subtotal_free_text`.

**Utilisation manuelle** :

- Web simulation (par défaut) : `.../custom/infrastructure/script/migrate-from-subtotal.php`
- Web exécution : `?confirm=yes` (+ `&cleanup=yes` pour nettoyer)
- CLI : `php migrate-from-subtotal.php confirm [cleanup]`

Clés de traduction : `InfrastructureMigrateSubtotalFailed`, `InfrastructureMigrateSubtotalRealRunFailed`, `InfrastructureCleanupSubtotalFailed`.

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `class/actions_infrastructure.class.php` — classe `ActionsInfrastructure`, hooks d'injection sur les documents,
- `class/infrastructure.class.php` — classe métier `TInfrastructure` (identification des lignes, calculs, manipulations — toutes méthodes statiques),
- `class/api_infrastructure.class.php` — classe `Infrastructure` (API REST),
- `core/lib/infrastructure.lib.php` — helpers génériques,
- `core/lib/infrastructureAdmin.lib.php` — helpers des pages d'administration (onglets, backup/restore, changelog),
- `core/tpl/originproductline.tpl.php` — override du rendu des lignes spéciales lors de la copie depuis un document d'origine,
- `core/tpl/infrastructureline_*.tpl.php` — templates dédiés (vue / édition / sous-total / row document / row shipment / row shipping),
- `core/triggers/interface_90_modInfrastructure_infrastructuretrigger.class.php` — préservation des structures sur événements documentaires,
- `js/infrastructure.lib.js` — helpers drag & drop et titres,
- `js/summary-menu.js` — sommaire rapide flottant (depuis 3.30.1),
- `css/infrastructure.css.php` + `css/summary-menu.css.php` — styles (adaptation oblyon automatique).

### Types de lignes spéciales

Le module ajoute 3 types de lignes spéciales identifiées par `special_code = 550090` (numéro du module) et `product_type = 9`. Le type est distingué par la valeur de `qty` :

| Type | Valeur `qty` | Description |
|------|-------------|-------------|
| **Titre** | 1 à 9 | En-tête de section (niveaux 1 à 9) |
| **Sous-total** | 91 à 99 | Ligne de totalisation intermédiaire (niveaux 1 à 9) |
| **Texte libre** | 50 | Bloc de texte explicatif |

Le niveau d'un titre/sous-total est accessible via `TInfrastructure::getNiveau(&$line)`.

### Logique de calcul des sous-totaux

Pour un sous-total à la position N, on remonte dans les lignes précédentes jusqu'au titre parent (ou au sous-total de même niveau), puis on somme :

- **Total HT** — lignes standards du bloc,
- **Quantité totale** — si option `infrastructure_show_qty` activée,
- **Réduction totale** — si option `show_reduc` activée,
- **TVA** — répartition par taux.

Le calcul exclut automatiquement :

- les lignes spéciales du module (titres, sous-totaux, textes libres) — détection via `TInfrastructure::isModInfrastructureLine()`,
- les lignes masquées par `hideblock = 1` sur le titre parent,
- les lignes de remise du module `infrasdiscount` (via leur `special_code`).

Implémentation principale : `TInfrastructure::getTotalBlockFromTitle(&$object, &$line)`.

### Gestion du drag & drop

Drag & drop natif Dolibarr (`ajaxBlockOrderJs($object)`) renforcé par `js/infrastructure.lib.js` pour la détection des lignes filles d'un titre (`getInfrastructureTitleChilds`). Les sauvegardes de rang passent par `script/interface.php` (endpoint AJAX). Sous-totaux recalculés automatiquement après réorganisation.

### Sommaire rapide flottant (depuis 3.30.1)

Bouton flottant (coin inférieur droit) dépliant un menu listant tous les titres du document. Désactivable via `INFRASTRUCTURE_DISABLE_SUMMARY` et adapté automatiquement au thème `oblyon` (compensation des barres sticky `FIX_AREAREF_CARD` et `FIX_STICKY_TABS_CARD`).

## Hooks et comportement (Hook behavior)

La classe `ActionsInfrastructure` (`class/actions_infrastructure.class.php`) expose les méthodes de hook suivantes :

| Méthode | Contextes utilisés | Rôle |
|---------|-------------------|------|
| `printFieldListSelect` | `consumptionthirdparty` | Injection dans la liste de consommation du tiers |
| `printFieldListWhere` | `propallist`, `orderlist`, `invoicelist`, `supplierorderlist`, `supplierinvoicelist`, `shippableorderlist`, `checkmarginlist` | Exclusion des lignes spéciales des listes / recherches |
| `editDictionaryFieldlist` / `createDictionaryFieldlist` | `admin` | Champs spécifiques du dictionnaire `c_infrastructure_free_text` |
| `formObjectOptions` | cartes de documents | Injection de formulaires et du sommaire JS |
| `formBuilddocOptions` | cartes de documents | Options dans la zone de génération PDF (récap, etc.) |
| `addMoreActionsButtons` | cartes de documents | Boutons d'action : ajouter titre, sous-total, texte libre, dupliquer |
| `doActions` | cartes de documents | Traitement add/edit/remove de blocs, duplicate, hideblock |
| `printObjectLine` | cartes de documents | Override complet du rendu des lignes spéciales |
| `printOriginObjectLine` / `printOriginObjectSubLine` | création depuis objet d'origine | Affichage des lignes spéciales dans les tables d'origine |
| `ODTSubstitutionLine` | `odtgeneration` | Substitution de variables dans les documents ODT |
| `pdfAddTitle` / `pdfAddTotal` | `pdfgeneration` | Rendu PDF spécifique des titres et sous-totaux |
| `beforePDFCreation` | `pdfgeneration` | Préparation des lignes (factures de situation, recap) |
| `afterPDFCreation` | `pdfgeneration` | Post-traitement (page récap si configuré) |
| `beforePercentCalculation` | `pdfgeneration` | Support des factures de situation |
| `changeRoundingMode` | `pdfgeneration` | Ajustement arrondis TVA sur blocs condensés |
| `defineColumnField` | `pdfgeneration` | Colonnes personnalisées dans les PDF |
| `isModInfrastructureLine` | génération PDF / autres modules | Test d'appartenance pour modules tiers (InfraSDiscount, marge) |
| `getlinetotalremise` | `pdfgeneration` | Remplacement du calcul de total de remise par ligne |
| `afterCreationOfRecurringInvoice` | `invoicereccard` | Préserve les structures à la création depuis modèle récurrent |
| `printCommonFooter` | tous contextes | Injection de scripts communs en pied de page |

### Flux des hooks (Hook workflow)

```
Utilisateur ouvre une fiche document
    ↓
formObjectOptions() : sommaire JS + formulaires modaux de saisie
    ↓
addMoreActionsButtons() : boutons « Ajouter titre / sous-total / texte libre / dupliquer »
    ↓
doActions() : traitement des soumissions
    - add_title       → qty=1..9, product_type=9, special_code=550090
    - add_infrastructure → qty=91..99
    - add_freetext    → qty=50
    - edit_* / duplicate / hideblock / remove_block
    ↓
printObjectLine() : router vers infrastructureline_row_document/shipment/shipping.tpl.php
    ↓
JS (infrastructure.lib.js + drag & drop core) : réorganisation AJAX via script/interface.php
    ↓
Génération PDF : pdfAddTitle / pdfAddTotal / beforePDFCreation / afterPDFCreation
```

### Modes d'affichage PDF

Trois modes contrôlés par ExtraFields portés par le titre :

| Mode | ExtraField | Comportement |
|------|-----------|-------------|
| **Standard** | — | Affichage complet en mode tableau |
| **Liste** | `print_as_list = 1` | Contenu rendu sous forme de liste à puces |
| **Condensé** | `print_condensed = 1` | Affichage compact (agrégation selon options) |

Option complémentaire : `hideblock = 1` masque le détail dans le PDF (seul le titre et le sous-total restent visibles).

## Trigger et comportement (Trigger behavior)

Classe `InterfaceInfrastructuretrigger` dans `core/triggers/interface_90_modInfrastructure_infrastructuretrigger.class.php` (priorité 90).

### Événements écoutés

| Événement | Action |
|-----------|--------|
| `LINEPROPAL_INSERT` | Ajout de ligne sous un titre en cours d'édition (`AddLineUnderTitle`) |
| `LINEORDER_INSERT` | Idem sur commande |
| `LINEBILL_INSERT` | Idem + logique d'insertion spécifique facture (`LineInvoiceInsert`) |
| `LINEBILL_SUPPLIER_CREATE` | Idem sur facture fournisseur |

### Méthodes principales

| Méthode | Rôle |
|---------|------|
| `AddLineUnderTitle(&$object, $action)` | Insère la nouvelle ligne juste après le titre courant |
| `LineInvoiceInsert($object, $user)` | Cas particuliers à l'insertion d'une ligne de facture |
| `ShippingOriginLine` / `ShippingCreate` | Préservation des titres/sous-totaux lors d'expéditions |
| `CreateFromClone` | Préservation des structures lors d'un clone |
| `OrdersToInvoiceBloc` | Regroupe les lignes par commande dans un bloc titre lors d'une facturation groupée |
| `RecurringInvoiceCreate` | Préserve les structures pour les factures récurrentes |
| `SituationPercentReset` / `SituationFinal` | Gestion des factures de situation (avancement de travaux) |
| `ComprisNonCompris` | Gestion de l'option NC (Non Compris) sur les lignes |
| `getShippingList` | Récupération des expéditions d'une commande pour inclusion dans les titres |
| `addToBegin` / `addToEnd` | Helpers publics statiques d'insertion |

Option introduite en 3.26.0 : la référence d'expédition peut être incluse dans le libellé des titres lors de la génération d'expéditions depuis une commande.

## Données / SQL (Data model)

Une table dictionnaire :

| Table | Description |
|-------|-------------|
| `llx_c_infrastructure_free_text` | Bibliothèque de textes libres réutilisables |

Schéma de `llx_c_infrastructure_free_text` (InnoDB) :

| Colonne | Type | Description |
|---------|------|-------------|
| `rowid` | INTEGER AUTO_INCREMENT PRIMARY KEY | Clé primaire |
| `label` | VARCHAR(255) NOT NULL | Libellé du texte libre |
| `content` | TEXT | Contenu HTML |
| `active` | TINYINT DEFAULT 1 NOT NULL | Actif ou non |
| `entity` | INTEGER DEFAULT 1 NOT NULL | Entité multi-société |

Métadonnées des blocs stockées via ExtraFields sur les lignes de documents :

| ExtraField | Tables cibles | Usage |
|------------|---------------|-------|
| `show_total_ht` | 6 tables de lignes | Sous-totaux : afficher le Total HT |
| `show_reduc` | idem | Sous-totaux : afficher la réduction totale |
| `infrastructure_show_qty` | idem | Sous-totaux : afficher la quantité totale |
| `hideblock` | propal/commande/facture + fournisseurs (sauf supplier_proposaldet) | Titres : masquer les lignes du bloc |
| `show_table_header_before` | 6 tables | Titres : répéter l'en-tête avant ce titre |
| `print_as_list` | 6 tables | Titres : impression en liste à puces |
| `print_condensed` | 6 tables | Titres : impression condensée |

## Constantes de configuration (Key settings)

Constantes actives usuelles (voir `sql/data.sql` et la page `admin/infrastructuresetup.php` pour la liste complète) :

- **Permissions globales** : `INFRASTRUCTURE_ALLOW_ADD_BLOCK` / `_EDIT_BLOCK` / `_REMOVE_BLOCK` / `_DUPLICATE_BLOCK` / `_DUPLICATE_LINE` / `_ADD_LINE_UNDER_TITLE`
- **Comportement d'insertion** : `INFRASTRUCTURE_ADD_LINE_UNDER_TITLE_AT_END_BLOCK`, `INFRASTRUCTURE_AUTO_ADD_INFRASTRUCTURE_ON_ADDING_NEW_TITLE`
- **ExtraFields sur titres** : `INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE`
- **Concaténation labels** : `INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL`
- **Styles écran** : `INFRASTRUCTURE_TITLE_STYLE` (défaut `BU`), `INFRASTRUCTURE_TOTAL_STYLE` (défaut `B`) — fallback pour PDF si versions PDF vides
- **Styles PDF** (18.3.0+) : `INFRASTRUCTURE_PDF_TITLE_STYLE`, `INFRASTRUCTURE_PDF_TOTAL_STYLE` (écrasent la version écran)
- **Styles spéciaux** : `INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES` (défaut `I`)
- **Couleurs** : `INFRASTRUCTURE_TITLE_BACKGROUND_COLOR` / `_TOTAL_BACKGROUND_COLOR` / `_TITLE_COLOR` / `_TOTAL_COLOR` / `_TITLE_COLOR_BLOC`
- **Affichage quantités sous-totaux** : `INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS` (CSV) + variante PDF `_PDF` (18.3.0+)
- **Pliage** : `INFRASTRUCTURE_BLOC_FOLD_MODE` (`default` / `keepTitle` / `hideAll`), `INFRASTRUCTURE_HIDE_FOLDERS_BY_DEFAULT` (3.28.0+)
- **TVA** : `INFRASTRUCTURE_LIMIT_TVA_ON_CONDENSED_BLOCS` (3.28.4+)
- **Récapitulatif PDF** : `INFRASTRUCTURE_PROPAL_ADD_RECAP` / `_COMMANDE_ADD_RECAP` / `_INVOICE_ADD_RECAP`, `INFRASTRUCTURE_KEEP_RECAP_FILE`
- **Marge sur sous-totaux** : `INFRASTRUCTURE_DISPLAY_MARGIN_ON_TOTAL`
- **UI** : `INFRASTRUCTURE_HIDE_OPTIONS_BUILD_DOC`, `INFRASTRUCTURE_DISABLE_SUMMARY`, `INFRASTRUCTURE_FORCE_EXPLODE_ACTION_BTN`, `INFRASTRUCTURE_DEFAULT_CHECK_SHIPPING_LIST_FOR_TITLE_DESC`
- **Expéditions** : `NO_TITLE_SHOW_ON_EXPED_GENERATION`
- **Offsets PDF** : `INFRASTRUCTURE_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET` / `_POS_Y_OFFSET`, `INFRASTRUCTURE_BACKGROUND_CELL_HEIGHT_OFFSET` / `_POS_Y_OFFSET`
- **Expérimental** : `INFRASTRUCTURE_DISABLE_FIX_TRANSACTION`, `INFRASTRUCTURE_ONE_LINE_IF_HIDE_INNERLINES`, `INFRASTRUCTURE_REPLACE_WITH_VAT_IF_HIDE_INNERLINES`

Point de vigilance : la liste complète des constantes par défaut (~30) est dans `sql/data.sql`. Toute modification manuelle est persistante tant que le module n'est pas désactivé.

## Compatibilité modules tiers (Third-party module compatibility)

Le module est explicitement interopérable avec :

- **Sous-Total** (ATM Consulting) — version originale ; **remplacement automatique** à l'activation
- **Milestone / Jalon** (iNodbox) — **CONFLIT BLOQUANT**
- **Ouvrage / Forfait** (Inovea), **Équipement** (Patas-Monkey), **Custom Link** (Patas-Monkey), **Note de Frais Plus** (Mikael Carlavan), **Ultimate** (ATM Consulting)
- **InfraSPackPlus** (InfraS) — support complet des structures dans les modèles PDF (InfraSPlus_Propal, InfraSPlus_Facture, etc.)
- **InfraSDiscount** (InfraS) — exclusion automatique des lignes spéciales infrastructure des calculs de remise via `infrasdiscount_isInfrastructureLine()`
- **Oblyon** (Inovea / InfraS) — CSS du sommaire flottant adapté ; gestion des barres sticky compensée en JS

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base Dolibarr : 7.1–8.4 ; module : 7.0–8.4 selon changelog),
- pas de framework lourd, pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity('c_infrastructure_free_text')`,
- hooks : retourner 0 (continuer), 1 (remplacer le code standard) ou <0 (erreur),
- respect du marquage `// InfraS change` / `// InfraS add` pour les modifications ciblées dans les fichiers existants.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / ExtraFields / hooks / trigger :

1. Désactiver puis réactiver le module
2. Vérifier les ExtraFields sur les 6 tables de lignes
3. Vérifier les constantes module (`INFRASTRUCTURE_*`)
4. Vérifier le dictionnaire `c_infrastructure_free_text`
5. Tester l'ajout de titre / sous-total / texte libre sur un devis
6. Tester le drag & drop (y compris déplacement de bloc complet)
7. Tester la conversion devis → commande → facture (préservation des structures)
8. Tester la génération PDF dans les trois modes (standard, liste, condensé)
9. Tester le sommaire rapide flottant sur un document avec plusieurs titres
10. Si thème `oblyon` actif : tester le scroll du sommaire avec `FIX_AREAREF_CARD` / `FIX_STICKY_TABS_CARD`

## Points d'attention (Watchpoints)

- `special_code = 550090` et `product_type = 9` identifient les lignes spéciales — le numéro `550090` est lu via `TInfrastructure::getModuleNumber()` (cache statique) et exposé dans `ActionsInfrastructure->module_number` (aucune valeur en dur dans les classes métier)
- Distinction titre / sous-total / texte libre via `qty` (titre : 1-9, sous-total : 91-99, texte libre : 50)
- Module **incompatible** avec `modMilestone` (iNodbox) — bloqué à l'activation
- La version locale est lue via `infrastructure_getLocalVersionMinDoli('infrastructure')` depuis `docs/changelog.xml`
- Le fork InfraS remplace l'original ATM Consulting ; éditeur affiché : `InfraS - Sylvain Legrand`
- Le drag & drop nécessite `$conf->use_javascript_ajax`
- `originproductline.tpl.php` override le rendu lors de la **copie depuis document d'origine** ; `infrastructureline_*.tpl.php` gèrent les rendus du document courant
- Sommaire rapide automatiquement désactivé si `oblyon` + `MAIN_MENU_INVERT`
- Factures de situation : méthodes de calcul dédiées pour éviter l'accumulation de TVA (DA027405, 3.29.2) ; injection de lignes TVA invisibles pour le calcul Dolibarr (DA027547, 3.29.3)
- Le descripteur référence `class/techatm.class.php` qui n'est plus présent — `dol_include_once` est tolérant et l'absence est silencieuse
- Compatibilité Easya : si `EASYA_VERSION` est définie, la famille de module bascule sur `easya`

## Dernières mises à jour (Recent updates)

- `3.25.0` (2024-07) : compatibilité Dolibarr 16 → 20 ; ajout hook `pdfgeneration`
- `3.25.1` à `3.25.7` (2024-07 → 2025-03) : corrections diverses (résumé titre, CKEditor, GETPOST, styles, SHIPPING_CREATE, null-coalesce)
- `3.26.0` (2024-09) : action massive de création de facture + injection des références d'expédition dans les titres
- `3.26.1` (2024-09) : compatibilité V20 (colonne « document » manquante dans les avoirs)
- `3.27.0` (2024-10) : passage aux dropdowns pour les boutons d'action (`INFRASTRUCTURE_FORCE_EXPLODE_ACTION_BTN` pour échappement)
- `3.28.0` (2025-01) : option `INFRASTRUCTURE_HIDE_FOLDERS_BY_DEFAULT`
- `3.28.1` à `3.28.7` (2025-02 → 2025-11) : corrections d'affichage et compatibilité fournisseur (DA026083, DA026204, DA026337, DA027316)
- `3.28.4` (2025-05) : constante `INFRASTRUCTURE_LIMIT_TVA_ON_CONDENSED_BLOCS`
- `3.29.0` (2025-07) : compatibilité Dolibarr 22
- `3.29.1` (2025-10) : ordre SQL correct à la création de facture depuis commande ; blocage facture de situation à 100 %
- `3.29.2` (2025-12) : correction méthode de calcul historique sur factures de situation (DA027405)
- `3.29.3` (2026-01) : correction TVA dans les PDF avec `hideInnerLines` ; injection de lignes TVA invisibles (DA027547)
- `3.29.4` (2025-12) : compatibilité Dolibarr 23
- `3.29.5` (2026-02) : correction DA027702
- `3.30.0` (2026-03) : nouvel onglet « Changelog » en administration ; ajout du descripteur CLAUDE.md ; amélioration CSS ; synchronisation en_US/es_ES/it_IT
- `3.30.1` (2026-04) : sommaire rapide en bouton flottant dépliable (au lieu d'un menu en sidebar) ; compensation des barres sticky oblyon (`FIX_AREAREF_CARD`, `FIX_STICKY_TABS_CARD`) ; nettoyage des images inutilisées
- `18.1.2` (2026-04) : bascule vers la numérotation alignée sur la version Dolibarr minimale (`18.x.y`) ; optimisations de performance PDF — pré-chauffage du cache parent/titre dans `beforePDFCreation` ; mémoïsation de `get_totalLineFromObject` et de l'`array_reverse` des lignes
- `18.1.3` (2026-04) : correction des requêtes SQL, constantes et ExtraFields pour éviter les doublons à l'activation ; suppression du BOM UTF-8 et conversion CRLF → LF sur 33 fichiers (le BOM dans `backport/v19/.../commonhookactions.class.php` provoquait une erreur fatale au chargement du hookmanager) ; amélioration de `infrastructure_addExtraField()`
- `18.2.0` (2026-04) : refactor majeur, regroupé par thématique : Renommages des constantes et des clés de traduction, extraction des 3 blocs vers des templates dédiés, Convergence du rendu de cellule libellé, Pliages des blocs, Marges sur sous-totaux, Suppression de `INFRASTRUCTURE_USE_NEW_FORMAT`, Application des classes CSS de niveau et Nouvelles options exposées en admin.
- `18.3.0` (2026-05) : Séparation des couleurs de fond, de texte des titres et sous-totaux, des styles de texte (B/U/I), du pourcentage de réduction de la luminosité par niveau et de la sélection « Afficher la quantité cumulée par défaut » entre l'affichage écran et le rendu PDF.
- `18.3.0` (2026-05) : Alignement de la quantité cumulée des sous-totaux sur la colonne Qté native de Dolibarr.


## Notes techniques (Technical notes)

### Classe `TInfrastructure` (Business logic)

`class/infrastructure.class.php` — toutes les méthodes sont **statiques**.

#### Identification des lignes

```php
TInfrastructure::isTitle(&$line, $level = -1)         // Détecte un titre (optionnellement d'un niveau donné)
TInfrastructure::isTotal(&$line, $level = -1)         // Détecte un sous-total
TInfrastructure::isFreeText(&$line)                   // Détecte un texte libre
TInfrastructure::isModInfrastructureLine(&$line)      // Toute ligne spéciale du module
TInfrastructure::getNiveau(&$line)                    // Niveau hiérarchique (1-9)
TInfrastructure::hasBreakPage($line)                  // Saut de page associé
TInfrastructure::hasNcTitle(&$line)                   // Titre NC (Non Compris)
```

#### Manipulation des lignes

```php
TInfrastructure::addTitle(&$object, $label, $level, $rang = -1, $desc = '')
TInfrastructure::addTotal(&$object, $label, $level, $rang = -1)
TInfrastructure::addInfrastructureMissing(&$object, $level_new_title)
TInfrastructure::updateRang(&$object, $rang_start, $move_to = 1)
TInfrastructure::duplicateLines(&$object, $lineid, $withBlockLine = false)
TInfrastructure::doUpdateLine(&$object, $rowid, $desc, $pu, $qty, $remise_percent, ...)
```

#### Recherche et parcours

```php
TInfrastructure::getAllTitleFromDocument(&$object, $get_block_total = false)
TInfrastructure::getAllTitleWithoutTotalFromDocument(&$object, $get_block_total = false)
TInfrastructure::getAllTitleFromLine(&$origin_line, $reverse = false)
TInfrastructure::getParentTitleOfLine(&$object, $rang, $lvl = 0)
TInfrastructure::getSubLineOfTitle(&$object, $rang, $lvl = 0)
TInfrastructure::getTotalBlockFromTitle(&$object, &$line, $breakOnTitle = false)
TInfrastructure::getLinesFromTitle(&$object, $key_trad, $level = 1, $under_title = '', $withBlockLine = false, $key_is_id = false)
TInfrastructure::getLinesFromTitleId(&$object, $lineid, $withBlockLine = false)
TInfrastructure::titleHasTotalLine(&$object, &$title_line, $strict_mode = false, $return_rang_on_false = false)
TInfrastructure::getOrderIdFromLineId(int $fk_commandedet, bool $supplier = false)
TInfrastructure::getLastLineOrderId(int $fk_commande, bool $supplier = false)
```

#### Rendu et récapitulatif

```php
TInfrastructure::generateDoc(&$object)                                     // Régénère document (PDF + update_price)
TInfrastructure::addRecapPage(&$parameters, &$origin_pdf, $fromInfraS = 0) // Page récap en fin de PDF
TInfrastructure::concat(&$outputlangs, $files, $fileoutput = '')           // Concaténation de PDF
TInfrastructure::getFreeTextHtml(&$line, $readonly = 0)                    // HTML d'un texte libre
TInfrastructure::getTitleLabel($line)                                      // Libellé d'un titre
TInfrastructure::getHtmlDictionnary(): string                              // HTML du sélecteur du dictionnaire
TInfrastructure::getCommonVATRate($object, $lineRef)                       // Taux TVA commun d'un ensemble
```

### Système de niveaux (Level system)

Gestion hiérarchique via la valeur `qty` :

| Type | Plage `qty` | Niveaux |
|------|-------------|---------|
| Titre | 1-9 | 1 = principal, 2..9 = sous-titres |
| Sous-total | 91-99 | 91 = principal, 92..99 = sous-sous-totaux |
| Texte libre | 50 | — |

Le niveau détermine l'indentation et le style d'affichage, la portée du calcul du sous-total (un sous-total de niveau N couvre jusqu'au titre parent de niveau ≤ N), et la hiérarchie visuelle dans les PDF.

### Calcul en cascade des sous-totaux (Cascade calculation)

```
POUR un sous-total à la position N :
  1. Remonter depuis la position N-1 vers 0
  2. S'arrêter au premier titre de niveau ≤ niveau(sous-total)
     OU au premier sous-total de niveau ≤ niveau(sous-total)
  3. Sommer les lignes standards entre ces bornes
  4. Exclure les lignes spéciales infrastructure (isModInfrastructureLine)
  5. Exclure les lignes masquées (hideblock sur le titre parent)
  6. Exclure les lignes de remise (modules tiers : infrasdiscount, etc.)
```

Exemple :

```
Ligne 1 : Titre « Matériel » (qty=1)
Ligne 2 : Produit A — 100,00 €
Ligne 3 : Produit B — 200,00 €
Ligne 4 : Sous-total « Matériel » (qty=91) → 300,00 €
Ligne 5 : Titre « Services » (qty=1)
Ligne 6 : Service X — 150,00 €
Ligne 7 : Sous-total « Services » (qty=91) → 150,00 €
```

### Gestion des ExtraFields dans les calculs

- **`show_total_ht = 1`** : affiche le montant HT sur la ligne sous-total
- **`show_reduc = 1`** : cumule et affiche le total des réductions du bloc
- **`infrastructure_show_qty = 1`** : cumule et affiche la quantité totale du bloc
- **`hideblock = 1`** (titre) : masque les lignes du bloc, change le style du titre (`INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES`), les lignes cachées restent comptabilisées dans les sous-totaux suivants (selon paramétrage)
- **`show_table_header_before = 1`** (titre) : répète l'en-tête du tableau juste avant ce titre dans le PDF
- **`print_as_list = 1`** (titre) : rendu en liste à puces
- **`print_condensed = 1`** (titre) : rendu condensé ; calcul TVA adapté si `INFRASTRUCTURE_LIMIT_TVA_ON_CONDENSED_BLOCS` activé

### Override de template (Template customization)

`core/tpl/` :

| Fichier | Rôle |
|---------|------|
| `originproductline.tpl.php` | Override du rendu lors de la création depuis un document d'origine |
| `infrastructureline_view.tpl.php` | Cellule libellé (mode consultation) — contextes `'document'` et `'shipment'` |
| `infrastructureline_edit.tpl.php` | Cellule libellé (mode édition) |
| `infrastructureline_infrastructure.tpl.php` | Rendu spécifique des lignes sous-total |
| `infrastructureline_row_document.tpl.php` | `<tr>` complet en contexte fiche document principale |
| `infrastructureline_row_shipment.tpl.php` | `<tr>` complet en contexte création d'expédition depuis commande |
| `infrastructureline_row_shipping.tpl.php` | `<tr>` complet en contexte fiche shipping/delivery |

Classes CSS / attributs de données utilisés côté rendu :

- `.infrastructure_label` — libellé principal (ciblé par le sommaire rapide)
- `tr[data-isinfrastructure="title"]`, `tr[data-isinfrastructure="total"]`, `tr[data-isinfrastructure="free-text"]` — distinction au niveau DOM
- `tr[data-level="..."]` — niveau hiérarchique exposé au JS

### Interaction avec les factures de situation (Progress invoices)

Traitement spécial réparti entre :

- **Trigger `SituationPercentReset()`** : remet à zéro les pourcentages d'avancement sur titres/sous-totaux
- **Trigger `SituationFinal()`** : gère la dernière facture d'une situation (passage à 100 %)
- **Hook `beforePercentCalculation()`** : intercepte le calcul des pourcentages
- **Hook `beforePDFCreation()`** : injecte les lignes TVA invisibles pour permettre à Dolibarr d'agréger (DA027547, 3.29.3)
- Version 3.29.1 : blocage de la création si la progression est déjà à 100 %
- Version 3.29.2 : correction d'une méthode historique qui accumulait la TVA (DA027405)

### API REST (API endpoints)

La classe `Infrastructure` (`class/api_infrastructure.class.php`) étend `DolibarrApi` et expose :

```
GET /infrastructure/{elementtype}/{idline}
  → getTotalLine() : total calculé d'un bloc sous-total pour une ligne donnée
  → elementtype ∈ { propal, commande, facture, supplier_proposal, supplier_order, supplier_invoice }
```

Helpers internes (`_getTotal`, `_getFkFieldName`) pour abstraire le type de document. Authentification : token API standard Dolibarr (`DOLAPIKEY`).

### Sommaire rapide flottant (Floating quick summary)

Injecté par `actions_infrastructure.class.php::formObjectOptions()` quand `INFRASTRUCTURE_DISABLE_SUMMARY` n'est pas actif. Trois fichiers impliqués :

- **`js/summary-menu.js`** : construit `#infrastructure-summary-floating` avec dropdown listant les titres (`<a class="infrastructure-summary-link">`). Au clic : scroll smooth vers `#row-<lineid>`
- **`css/summary-menu.css.php`** : CSS adapté automatiquement (variables `--bgnavtop*` pour `oblyon` / `--colorbackhmenu1` pour les autres thèmes)
- **Configuration JS** (`infrastructureSummaryJsConf`) injectée par PHP : `langs.InfrastructureSummaryTitle`, `useOldSplittedTrForLine` (compatibilité Dolibarr < 16), `isOblyon`, `fixArearefCard`, `fixStickyTabsCard`

**Compensation du scroll sous oblyon** : quand `FIX_AREAREF_CARD` ou `FIX_STICKY_TABS_CARD` sont actives, `div.arearef` et/ou `div.tabs:first-of-type` deviennent `position: sticky` et masqueraient la ligne cible. Le JS ajoute leur `outerHeight()` à l'offset de scroll quand ces éléments sont effectivement en `position: sticky`.

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="18.3.0" MonthVersion="2026-04">
        <change type='add'>Added feature description.</change>
        <change type='chg'>Changed feature description.</change>
        <change type='fix'>Fixed bug description.</change>
    </Version>
    <InfraS Downloaded="YYYYMMDD"/>
    <Dolibarr minVersion="18.0.0" maxVersion="23.x.x"/>
    <PHP minVersion="7.0" maxVersion="8.4"/>
</changelog>
```

- Types de changement : `add`, `chg`, `fix` — ordre recommandé dans une version : `fix → chg → add`
- L'attribut `Downloaded` est mis à jour automatiquement lors du téléchargement
- Parsé par `infrastructure_getLocalVersionMinDoli()` et `infrastructure_getChangeLog()` (dans `core/lib/infrastructureAdmin.lib.php`)

`infrastructure_getLocalVersionMinDoli()` retourne :

```php
[
    0 => "18.3.0",           // Version courante
    1 => "18.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (-1 = KO, 0 = OK)
    3 => <SimpleXMLElement>, // Liste des versions (ou message d'erreur)
    4 => "23.x.x",           // Version max Dolibarr
    5 => "7.0",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

### Compatibilité avec InfraSPackPlus (PDF rendering)

Les modèles PDF InfraSPlus (propal, facture, commande, etc.) intègrent nativement le support des structures :

- reconnaissance des lignes spéciales (`special_code = 550090`, `product_type = 9`),
- rendu personnalisé des titres selon `INFRASTRUCTURE_TITLE_STYLE` / `_PDF_TITLE_STYLE`,
- affichage des sous-totaux avec répartition TVA,
- support des modes `hideblock`, `print_as_list`, `print_condensed`,
- `show_table_header_before` pour répéter l'en-tête de tableau,
- page récap optionnelle (`INFRASTRUCTURE_*_ADD_RECAP`).

InfraSPackPlus 18.16.0 ajoute une exclusion explicite des lignes infrastructure du bloc `INFRASPLUS_PDF_SHOW_DISCOUNT_OPT` pour éviter le double affichage du template d'édition.

### Compatibilité avec InfraSDiscount (Discount exclusion)

Le module `infrasdiscount` exclut automatiquement les lignes infrastructure de ses calculs via :

```php
infrasdiscount_isInfrastructureLine($line)   // special_code == 550090 && product_type == 9
infrasdiscount_isInfrastructureTitle($line)  // qty ∈ [1..9]
infrasdiscount_isInfrastructureTotal($line)  // qty ∈ [91..99]
```

Les calculs de remise en cascade ignorent ces lignes, ce qui garantit la cohérence des sous-totaux en présence de lignes de remise.
