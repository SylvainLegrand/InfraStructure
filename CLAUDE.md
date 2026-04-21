# CLAUDE.md — Contexte module subtotal

## Aperçu (Overview)

`subtotal` est un module externe Dolibarr de structuration et organisation des documents commerciaux :

- ajout de titres, sous-titres et sous-totaux (niveaux hiérarchiques) sur propositions, commandes, factures et documents fournisseurs,
- insertion de lignes de texte libre entre les lignes standards,
- réorganisation des lignes par glisser-déposer (drag & drop),
- options d'affichage PDF : masquage, impression en liste ou en mode condensé, répétition d'en-tête,
- dictionnaire de textes libres réutilisables (`c_subtotal_free_text`),
- sommaire rapide flottant (depuis 3.30.1) pour naviguer entre titres dans les documents longs,
- support des factures de situation (avancement de travaux) avec préservation des structures.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand (fork maintenu, basé sur l'original ATM Consulting)
- Numéro module : `104777`
- Licence : GPL v3+
- Compatibilité Dolibarr : `16.0.0` à `23.x.x`
- Compatibilité PHP : `7.0` à `8.2`
- Dernière version locale : `3.30.1` (2026-04)
- Dépendance obligatoire : aucune
- Conflit : module **Milestone/Jalon** (iNodbox) — les deux modules ne peuvent pas être activés simultanément
- Emplacement : `htdocs/custom/subtotal/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/subtotal/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── subtotal_about.php
│   ├── subtotal_changelog.php
│   └── subtotal_setup.php
├── backport/
│   └── v19/
│       └── core/
│           └── class/
│               └── commonhookactions.class.php
├── class/
│   ├── actions_subtotal.class.php
│   ├── api_subtotal.class.php
│   ├── staticPdf.model.php
│   ├── subTotalJsonResponse.class.php
│   └── subtotal.class.php
├── config.php
├── core/
│   ├── lib/
│   │   └── subtotalAdmin.lib.php
│   ├── modules/
│   │   └── modSubtotal.class.php
│   ├── tpl/
│   │   ├── originproductline.tpl.php
│   │   ├── subtotalline_edit.tpl.php
│   │   ├── subtotalline_subtotal.tpl.php
│   │   └── subtotalline_view.tpl.php
│   └── triggers/
│       └── interface_90_modSubtotal_subtotaltrigger.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── puentebold.ttf
│   ├── subtotal.css.php
│   └── summary-menu.css.php
├── docs/
│   └── changelog.xml
├── img/                                    # Icônes, logos, captures
├── js/
│   ├── subtotal.lib.js                     # Helpers drag & drop et titres
│   └── summary-menu.js                     # Sommaire rapide flottant
├── langs/
│   ├── en_US/subtotal.lang
│   ├── es_ES/subtotal.lang
│   └── fr_FR/subtotal.lang
├── lib/
│   └── subtotal.lib.php
├── script/
│   ├── import-from-milestone-b3.8.php      # Import depuis module Milestone (branche 3.8)
│   ├── import-from-milestone.php           # Import depuis module Milestone
│   ├── interface.php                       # Endpoint AJAX générique (rank, NC, etc.)
│   ├── maj_subtotal_nc.php                 # Mise à jour des NC
│   └── migrate_titles_to_7.0.php           # Migration titres ancien format
└── sql/
    ├── data.sql                            # Constantes module (INSERT llx_const)
    └── llx_c_subtotal_free_text.sql        # Table dictionnaire
```

## Descripteur module (Module descriptor : `modSubtotal`)

Dans `core/modules/modSubtotal.class.php` :

- **Module parts** :
	- `triggers` : activés (1 trigger, voir section Trigger)
	- `hooks` (25 contextes) : `invoicecard`, `invoicesuppliercard`, `propalcard`, `supplier_proposalcard`, `ordercard`, `ordersuppliercard`, `odtgeneration`, `orderstoinvoice`, `orderstoinvoicesupplier`, `admin`, `invoicereccard`, `consumptionthirdparty`, `ordershipmentcard`, `expeditioncard`, `deliverycard`, `paiementcard`, `referencelettersinstacecard`, `shippableorderlist`, `propallist`, `orderlist`, `invoicelist`, `supplierorderlist`, `supplierinvoicelist`, `cron`, `pdfgeneration`, `checkmarginlist`
	- `tpl` : activés (override template `originproductline.tpl.php` et templates dédiés)
	- `css` : `/subtotal/css/subtotal.css.php` (le CSS `summary-menu.css.php` est chargé à la volée par `actions_subtotal`)
- **Dépendances** : aucune dépendance obligatoire
- **Conflit** : `modMilestone` (iNodbox)
- **Dictionnaires** : 1 dictionnaire
	- `c_subtotal_free_text` — bibliothèque de textes libres réutilisables (colonnes : `rowid`, `label`, `content`, `active`, `entity`)
- **Boxes** : aucune
- **Cron** : aucune tâche
- **ExtraFields** : création automatique sur les lignes de documents (`propaldet`, `commandedet`, `facturedet`, `supplier_proposaldet`, `commande_fournisseurdet`, `facture_fourn_det`)
- **Constantes prédéfinies** (dans `$this->const`) : 6 entrées
	- `SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES` (chaine, défaut `I`) — Style des sous-titres quand détail caché (B/I/U)
	- `SUBTOTAL_ALLOW_ADD_BLOCK` (chaine, défaut `1`) — Permet l'ajout de titres et sous-totaux
	- `SUBTOTAL_ALLOW_EDIT_BLOCK` (chaine, défaut `1`) — Permet de modifier titres et sous-totaux
	- `SUBTOTAL_ALLOW_REMOVE_BLOCK` (chaine, défaut `1`) — Permet de supprimer les titres et sous-totaux
	- `SUBTOTAL_TITLE_STYLE` (chaine, défaut `BU`) — Style des titres (B=Gras, U=Souligné)
	- `SUBTOTAL_SUBTOTAL_STYLE` (chaine, défaut `B`) — Style des sous-totaux (B=Gras)

	D'autres constantes par défaut sont chargées via `sql/data.sql` lors de l'activation (ex. `SUBTOTAL_ALLOW_DUPLICATE_BLOCK`, `SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE`, `SUBTOTAL_CONCAT_TITLE_LABEL_IN_SUBTOTAL_LABEL`, etc.).
- **Permissions** : aucune permission définie (accès réservé aux utilisateurs ayant les droits Dolibarr standards sur les documents concernés)
- **Famille** : `Modules InfraS` (ou `easya` si la constante `EASYA_VERSION` est présente)

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Vérification du conflit avec `modMilestone` (si activé : blocage avec erreur)
2. Chargement des tables SQL via `loadTables()` → `_load_tables('/subtotal/sql/')` : création de `llx_c_subtotal_free_text` et insertion des constantes depuis `data.sql`
3. Création automatique des ExtraFields sur les lignes de documents (`propaldet`, `commandedet`, `facturedet`, `supplier_proposaldet`, `commande_fournisseurdet`, `facture_fourn_det`) :
	- `show_total_ht` (int) — Afficher le Total HT sur le sous-total
	- `show_reduc` (int) — Afficher la réduction sur le sous-total
	- `subtotal_show_qty` (int) — Afficher la quantité du sous-total
	- `hideblock` (int) — Cacher les lignes contenues dans ce titre
	- `show_table_header_before` (int) — Afficher l'en-tête du tableau juste avant ce titre
	- `print_as_list` (int) — Imprimer le contenu sous forme de liste
	- `print_condensed` (int) — Imprimer le contenu de manière condensée
4. Désactivation automatique du sommaire rapide si le module `oblyon` est activé avec menu inversé (`MAIN_MENU_INVERT`)

### Désactivation (Lifecycle : `remove()`)

`remove()` délègue à `_remove()` sans action spécifique : les ExtraFields et les constantes sont conservés pour permettre une réactivation sans perte de données.

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `class/actions_subtotal.class.php` — classe `ActionsSubtotal`, hooks d'injection sur les documents,
- `class/subtotal.class.php` — classe métier `TSubtotal` (identification des lignes, calculs, manipulations),
- `class/api_subtotal.class.php` — classe `Subtotal` (API REST, exposition du total par ligne),
- `lib/subtotal.lib.php` — helpers génériques du module,
- `core/lib/subtotalAdmin.lib.php` — helpers des pages d'administration (onglets, backup/restore, changelog),
- `core/tpl/originproductline.tpl.php` — override complet du rendu des lignes spéciales lors de la création d'un document depuis un autre (copie de lignes d'origine),
- `core/tpl/subtotalline_*.tpl.php` — templates dédiés aux modes vue/édition/sous-total,
- `core/triggers/interface_90_modSubtotal_subtotaltrigger.class.php` — trigger pour la préservation des structures lors des événements documentaires,
- `js/subtotal.lib.js` — bibliothèque JS (détection des lignes filles d'un titre pour le drag & drop et le sommaire),
- `js/summary-menu.js` — sommaire rapide sous forme de bouton flottant (depuis 3.30.1),
- `css/subtotal.css.php` — CSS module principal,
- `css/summary-menu.css.php` — CSS du sommaire flottant (avec adaptation oblyon automatique).

### Types de lignes spéciales

Le module ajoute 3 types de lignes spéciales identifiées par `special_code = 104777` (numéro du module) et `product_type = 9`. Le type est distingué par la valeur de `qty` :

| Type | Valeur `qty` | Description | Utilisation |
|------|-------------|-------------|-------------|
| **Titre** | 1 à 9 | En-tête de section | Structuration hiérarchique (niveaux 1 à 9) |
| **Sous-total** | 91 à 99 | Ligne de totalisation intermédiaire | Totalisation du bloc (niveaux 1 à 9) |
| **Texte libre** | 50 | Bloc de texte explicatif | Annotations, explications |

Le niveau d'un titre/sous-total est accessible via `TSubtotal::getNiveau(&$line)`.

### Logique de calcul des sous-totaux

Pour un sous-total à la position N, on remonte dans les lignes précédentes jusqu'au titre parent (ou au sous-total de même niveau), puis on somme :

- **Total HT** — lignes standards du bloc,
- **Quantité totale** — si option `subtotal_show_qty` activée,
- **Réduction totale** — si option `show_reduc` activée,
- **TVA** — répartition par taux.

Le calcul exclut automatiquement :

- Les lignes spéciales du module (titres, sous-totaux, textes libres) : détectées par `TSubtotal::isModSubtotalLine()`,
- Les lignes masquées par `hideblock = 1` sur le titre parent,
- Les lignes de remise du module `infrasdiscount` (via leur `special_code`).

Implémentation principale : `TSubtotal::getTotalBlockFromTitle(&$object, &$line)` et méthodes associées dans `subtotal.class.php`.

### Gestion du drag & drop

Le drag & drop est pris en charge par le cœur Dolibarr (via `ajaxBlockOrderJs($object)`) avec le soutien de `js/subtotal.lib.js` pour la détection des lignes filles d'un titre (`getSubtotalTitleChilds`). Les comportements :

- déplacer un titre déplace automatiquement toutes les lignes jusqu'au prochain sous-total ou titre de niveau ≤,
- les sauvegardes de rang passent par `script/interface.php` (endpoint AJAX),
- les sous-totaux sont recalculés automatiquement après réorganisation.

### Sommaire rapide flottant (depuis 3.30.1)

Un bouton flottant (coin inférieur droit) permet de dérouler un menu listant tous les titres du document. Un clic scrolle doucement vers la ligne cible. Le comportement est désactivé via la constante `SUBTOTAL_DISABLE_SUMMARY` et adapté automatiquement au thème `oblyon` (détection des barres sticky `FIX_AREAREF_CARD` et `FIX_STICKY_TABS_CARD` pour compenser le scroll — voir section Notes techniques).

## Hooks et comportement (Hook behavior)

La classe `ActionsSubtotal` (dans `class/actions_subtotal.class.php`) expose les méthodes de hook suivantes :

| Méthode | Contextes utilisés | Rôle |
|---------|-------------------|------|
| `printFieldListSelect` | `consumptionthirdparty` | Injection dans la liste de consommation du tiers |
| `printFieldListWhere` | `propallist`, `orderlist`, `invoicelist`, `supplierorderlist`, `supplierinvoicelist`, `shippableorderlist`, `checkmarginlist` | Exclusion des lignes spéciales des listes / recherches |
| `editDictionaryFieldlist` / `createDictionaryFieldlist` | `admin` | Champs spécifiques du dictionnaire `c_subtotal_free_text` |
| `formObjectOptions` | cartes de documents | Injection de formulaires et du sommaire JS |
| `formBuilddocOptions` | cartes de documents | Ajout d'options dans la zone de génération PDF (récap, etc.) |
| `addMoreActionsButtons` | cartes de documents | Boutons d'action : ajouter titre, sous-total, texte libre, dupliquer bloc, etc. |
| `doActions` | cartes de documents | Traitement des actions : add/edit/remove de blocs, duplicate, hideblock, etc. |
| `printObjectLine` | cartes de documents | Override complet de l'affichage des lignes spéciales du module |
| `printOriginObjectLine` / `printOriginObjectSubLine` | création depuis objet d'origine | Affichage des lignes spéciales dans les tables d'origine |
| `ODTSubstitutionLine` | `odtgeneration` | Substitution de variables dans les documents ODT |
| `pdfAddTitle` / `pdfAddTotal` | `pdfgeneration` | Rendu PDF spécifique des titres et sous-totaux |
| `beforePDFCreation` | `pdfgeneration` | Préparation des lignes avant création PDF (factures de situation, recap, etc.) |
| `afterPDFCreation` | `pdfgeneration` | Post-traitement (injection page récap si configuré) |
| `beforePercentCalculation` | `pdfgeneration` | Support des factures de situation |
| `changeRoundingMode` | `pdfgeneration` | Ajustement arrondis TVA sur blocs condensés |
| `defineColumnField` | `pdfgeneration` | Colonnes personnalisées dans les PDF |
| `isModSubtotalLine` | génération PDF / autres modules | Fournit aux modules tiers (InfraSDiscount, marge) un test d'appartenance |
| `getlinetotalremise` | `pdfgeneration` | Remplacement du calcul de total de remise par ligne |
| `afterCreationOfRecurringInvoice` | `invoicereccard` | Préserve les structures à la création d'une facture depuis un modèle récurrent |
| `printCommonFooter` | tous contextes | Injection de scripts communs en pied de page |

### Flux des hooks (Hook workflow)

```
Utilisateur ouvre une fiche document (devis/commande/facture/documents fournisseurs)
    ↓
formObjectOptions() : injection du sommaire JS + formulaires modaux de saisie
    ↓
addMoreActionsButtons() : boutons « Ajouter titre / sous-total / texte libre / dupliquer »
    ↓
doActions() : traitement des soumissions
    - add_title       → création d'une ligne (qty=1..9, product_type=9, special_code=104777)
    - add_subtotal    → qty=91..99
    - add_freetext    → qty=50
    - edit_title / edit_subtotal / edit_freetext → mise à jour
    - duplicate / hideblock / remove_block      → actions sur le bloc
    ↓
printObjectLine() : rendu personnalisé des lignes spéciales (titres, sous-totaux, textes libres)
    ↓
JS (subtotal.lib.js + drag & drop core) : réorganisation avec sauvegarde AJAX via script/interface.php
    ↓
Génération PDF (pdfAddTitle, pdfAddTotal, beforePDFCreation, afterPDFCreation) : rendu spécifique
```

### Modes d'affichage PDF

Trois modes contrôlés par ExtraFields portés par le titre :

| Mode | ExtraField | Comportement |
|------|-----------|-------------|
| **Standard** | — | Affichage complet en mode tableau |
| **Liste** | `print_as_list = 1` | Contenu rendu sous forme de liste à puces |
| **Condensé** | `print_condensed = 1` | Affichage compact (agrégation selon options) |

Option complémentaire : `hideblock = 1` masque le détail du bloc dans le PDF (seul le titre et le sous-total restent visibles).

## Trigger et comportement (Trigger behavior)

Classe `InterfaceSubtotaltrigger` dans `core/triggers/interface_90_modSubtotal_subtotaltrigger.class.php` (priorité 90).

### Événements écoutés

| Événement | Action |
|-----------|--------|
| `LINEPROPAL_INSERT` | Ajout de ligne sous un titre en cours d'édition (`AddLineUnderTitle`) |
| `LINEORDER_INSERT` | Idem sur commande |
| `LINEBILL_INSERT` | Idem sur facture + logique d'insertion spécifique (`LineInvoiceInsert`) |
| `LINEBILL_SUPPLIER_CREATE` | Idem sur facture fournisseur |

### Méthodes privées principales

| Méthode | Rôle |
|---------|------|
| `AddLineUnderTitle(&$object, $action)` | Insère la nouvelle ligne juste après le titre courant (évite que les nouvelles lignes atterrissent en fin de document) |
| `LineInvoiceInsert($object, $user)` | Gère les cas particuliers à l'insertion d'une ligne de facture |
| `ShippingOriginLine($object, $user)` | Préserve les titres/sous-totaux issus d'une commande lors d'une expédition |
| `ShippingCreate($object, $user, $langs)` | Traitement à la création d'une expédition (avec option `NO_TITLE_SHOW_ON_EXPED_GENERATION` pour filtrer) |
| `CreateFromClone($object, $user, $action, $langs, $conf)` | Préserve les structures lors d'un clone de document |
| `OrdersToInvoiceBloc($object, $user, $action, $langs)` | Regroupe les lignes par commande avec un bloc titre lors de facturation groupée |
| `RecurringInvoiceCreate($object)` | Préserve les structures à la création d'une facture depuis un modèle récurrent |
| `SituationPercentReset($object, $user)` | Remise à zéro du pourcentage d'avancement sur les titres et sous-totaux lors de la création d'une facture de situation |
| `SituationFinal($object)` | Marquage spécifique pour la facture finale de situation |
| `ComprisNonCompris($object, $user, $action, $langs)` | Gestion de l'option NC (Non Compris) sur les lignes |
| `getShippingList($orderId)` | Récupération de la liste des expéditions d'une commande (pour inclusion dans les titres) |
| `addToBegin(&$parent, &$object, $rang)` / `addToEnd(&$parent, &$object, $rang)` | Helpers publics statiques d'insertion |

### Références d'expédition dans les titres

Option introduite en version 3.26.0 : lors de la génération d'expéditions depuis une commande, la référence d'expédition peut être automatiquement incluse dans le libellé des titres correspondants.

## Données / SQL (Data model)

Le module crée une table dictionnaire :

| Table | Description |
|-------|-------------|
| `llx_c_subtotal_free_text` | Bibliothèque de textes libres réutilisables |

Schéma de `llx_c_subtotal_free_text` (moteur InnoDB) :

| Colonne | Type | Description |
|---------|------|-------------|
| `rowid` | INTEGER AUTO_INCREMENT PRIMARY KEY | Clé primaire |
| `label` | VARCHAR(255) NOT NULL | Libellé du texte libre |
| `content` | TEXT | Contenu HTML |
| `active` | TINYINT DEFAULT 1 NOT NULL | Actif ou non |
| `entity` | INTEGER DEFAULT 1 NOT NULL | Entité multi-société |

Les métadonnées de structure des blocs sont stockées via ExtraFields sur les lignes de documents :

| ExtraField | Type | Tables cibles | Usage |
|------------|------|---------------|-------|
| `show_total_ht` | int (0/1) | `propaldet`, `commandedet`, `facturedet`, `supplier_proposaldet`, `commande_fournisseurdet`, `facture_fourn_det` | Sous-totaux : afficher le Total HT |
| `show_reduc` | int (0/1) | idem | Sous-totaux : afficher la réduction totale |
| `subtotal_show_qty` | int (0/1) | idem | Sous-totaux : afficher la quantité totale |
| `hideblock` | int (0/1) | `propaldet`, `commandedet`, `facturedet`, `commande_fournisseurdet`, `facture_fourn_det` | Titres : masquer les lignes du bloc |
| `show_table_header_before` | int (0/1) | tables propal/commande/facture + fournisseurs | Titres : répéter l'en-tête avant ce titre |
| `print_as_list` | int (0/1) | idem | Titres : impression en liste à puces |
| `print_condensed` | int (0/1) | idem | Titres : impression condensée |

## Constantes de configuration (Key settings)

Constantes actives usuelles (définies via `$this->const` ou `sql/data.sql`, ou ajoutées par la page `admin/subtotal_setup.php`) :

- `SUBTOTAL_ALLOW_ADD_BLOCK` / `SUBTOTAL_ALLOW_EDIT_BLOCK` / `SUBTOTAL_ALLOW_REMOVE_BLOCK` — permissions globales
- `SUBTOTAL_ALLOW_DUPLICATE_BLOCK` / `SUBTOTAL_ALLOW_DUPLICATE_LINE` — duplication
- `SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE` / `SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK` — comportement d'insertion
- `SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE` — ajoute automatiquement un sous-total en même temps qu'un titre
- `SUBTOTAL_ALLOW_EXTRAFIELDS_ON_TITLE` — autorise les ExtraFields sur les titres
- `SUBTOTAL_CONCAT_TITLE_LABEL_IN_SUBTOTAL_LABEL` — concatène le label du titre dans le libellé du sous-total
- `SUBTOTAL_TITLE_STYLE` — style des titres (`B`, `U`, `I`, combinaisons) — défaut `BU`
- `SUBTOTAL_SUBTOTAL_STYLE` — style des sous-totaux — défaut `B`
- `SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES` — style des titres quand détail caché — défaut `I`
- `SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS` — valeur par défaut de `subtotal_show_qty`
- `SUBTOTAL_HIDE_FOLDERS_BY_DEFAULT` — masquer les dossiers par défaut (3.28.0+)
- `SUBTOTAL_LIMIT_TVA_ON_CONDENSED_BLOCS` — TVA limitée aux blocs condensés/liste (3.28.4+)
- `SUBTOTAL_BLOC_FOLD_MODE` — mode de repli (plié/déplié) des blocs
- `SUBTOTAL_PROPAL_ADD_RECAP` / `SUBTOTAL_COMMANDE_ADD_RECAP` / `SUBTOTAL_INVOICE_ADD_RECAP` — ajout d'une page récap PDF
- `SUBTOTAL_HIDE_OPTIONS_BUILD_DOC` — masquer les options de génération PDF du module
- `SUBTOTAL_DISABLE_SUMMARY` — désactiver le sommaire rapide flottant
- `NO_TITLE_SHOW_ON_EXPED_GENERATION` — ne pas recopier les titres lors de la génération d'expéditions
- `SUBTOTAL_FORCE_EXPLODE_ACTION_BTN` — forcer les boutons d'action en mode « éclatés » (hors dropdown)

Point de vigilance : la liste complète des constantes par défaut (~30) est dans `sql/data.sql`. Toute modification manuelle d'une constante est persistante tant que le module n'est pas désactivé.

## Compatibilité modules tiers

Le module est explicitement interopérable avec :

- **Sous-Total** (ATM Consulting) — version originale, ce module en est un fork maintenu par InfraS
- **Milestone / Jalon** (iNodbox) — **CONFLIT BLOQUANT** (`conflictwith = array('modMilestone')`)
- **Ouvrage / Forfait** (Inovea)
- **Équipement** (Patas-Monkey)
- **Custom Link** (Patas-Monkey)
- **Note de Frais Plus** (Mikael Carlavan)
- **Ultimate** (ATM Consulting)
- **InfraSPackPlus** (InfraS) — support complet des structures dans les modèles PDF (InfraSPlus_Propal, InfraSPlus_Facture, etc.)
- **InfraSDiscount** (InfraS) — exclusion automatique des lignes spéciales subtotal des calculs de remise via `infrasdiscount_isSubtotalLine()`
- **Oblyon** (Inovea / InfraS) — CSS du sommaire flottant adapté automatiquement au thème ; gestion des barres sticky (`FIX_AREAREF_CARD`, `FIX_STICKY_TABS_CARD`) compensée en JS

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base Dolibarr : 7.1–8.4 ; module cible : 7.0–8.2 selon changelog),
- pas de framework lourd, pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity('c_subtotal_free_text')`,
- hooks : retourner 0 (continuer), 1 (remplacer le code standard) ou <0 (erreur),
- respect du marquage `// InfraS change` / `// InfraS add` pour les modifications ciblées dans les fichiers existants.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / ExtraFields / hooks / trigger :

1. Désactiver puis réactiver le module
2. Vérifier les ExtraFields sur `propaldet`, `commandedet`, `facturedet`, `supplier_proposaldet`, `commande_fournisseurdet`, `facture_fourn_det`
3. Vérifier les constantes module (`SUBTOTAL_*`)
4. Vérifier le dictionnaire `c_subtotal_free_text`
5. Tester l'ajout de titre/sous-total/texte libre sur un devis
6. Tester le drag & drop (y compris déplacement d'un bloc complet)
7. Tester la conversion devis → commande → facture (préservation des structures)
8. Tester la génération PDF dans les trois modes (standard, liste, condensé)
9. Tester le sommaire rapide flottant sur un document avec plusieurs titres
10. Si thème `oblyon` actif : tester le scroll du sommaire avec `FIX_AREAREF_CARD` / `FIX_STICKY_TABS_CARD`

## Points d'attention (Watchpoints)

- `special_code = 104777` et `product_type = 9` identifient les lignes spéciales du module
- La distinction titre / sous-total / texte libre se fait via `qty` (titre : 1-9, sous-total : 91-99, texte libre : 50)
- Le module est **incompatible** avec `modMilestone` (iNodbox) — bloqué à l'activation
- La version locale est lue via `subtotal_getLocalVersionMinDoli('subtotal')` depuis `docs/changelog.xml`
- Le fork InfraS remplace l'original ATM Consulting ; l'éditeur affiché est `InfraS - Sylvain Legrand`
- Le drag & drop nécessite `$conf->use_javascript_ajax`
- Le template `originproductline.tpl.php` override le rendu des lignes spéciales lors de la **copie depuis document d'origine** ; les templates `subtotalline_*.tpl.php` gèrent les rendus vue/édition dans le document courant
- Le sommaire rapide est automatiquement désactivé si `oblyon` est actif avec menu inversé (pour éviter un conflit de layout)
- Les factures de situation reposent sur des méthodes de calcul dédiées pour éviter l'accumulation de TVA (DA027405, 3.29.2) et injectent des lignes TVA invisibles pour le calcul Dolibarr (DA027547, 3.29.3)
- Le descripteur référence `class/techatm.class.php` qui n'est plus présent dans le module ; `dol_include_once` est tolérant et l'absence est silencieuse
- Compatibilité Easya : si `EASYA_VERSION` est définie, la famille de module bascule sur `easya`

## Dernières mises à jour (Recent updates)

- `3.25.0` (2024-07) : compatibilité Dolibarr 16 → 20 ; ajout hook `pdfgeneration`
- `3.25.1` à `3.25.7` (2024-07 → 2025-03) : corrections diverses (résumé titre, CKEditor, GETPOST, styles, SHIPPING_CREATE, null-coalesce)
- `3.26.0` (2024-09) : action massive de création de facture + injection des références d'expédition dans les titres
- `3.26.1` (2024-09) : compatibilité V20 (colonne « document » manquante dans les avoirs)
- `3.27.0` (2024-10) : passage aux dropdowns pour les boutons d'action (constante d'échappement `SUBTOTAL_FORCE_EXPLODE_ACTION_BTN`)
- `3.28.0` (2025-01) : option `SUBTOTAL_HIDE_FOLDERS_BY_DEFAULT`
- `3.28.1` (2025-02) : correction affichage détails hors bloc sous-total (DA026083)
- `3.28.2` (2025-03) : correction affichage prix PDF (DA026204)
- `3.28.3` (2025-04) : correction boutons fournisseur (DA026337)
- `3.28.4` (2025-05) : constante `SUBTOTAL_LIMIT_TVA_ON_CONDENSED_BLOCS`
- `3.28.5` (2025-05) : correction hook `printFieldListWhere` (impact module marge)
- `3.28.6` (2025-05) : correction template + colspan pour lignes texte libre
- `3.28.7` (2025-11) : correction `special_code` manquant sur facture issue d'expédition (DA027316)
- `3.29.0` (2025-07) : compatibilité Dolibarr 22
- `3.29.1` (2025-10) : ordre SQL correct à la création de facture depuis commande ; blocage facture de situation à 100%
- `3.29.2` (2025-12) : correction méthode de calcul historique sur factures de situation (DA027405)
- `3.29.3` (2026-01) : correction TVA dans les PDF avec `hideInnerLines` ; injection de lignes TVA invisibles (DA027547)
- `3.29.4` (2025-12) : compatibilité Dolibarr 23
- `3.29.5` (2026-02) : correction DA027702
- `3.30.0` (2026-03) : nouvel onglet « Changelog » en administration ; ajout du descripteur CLAUDE.md ; amélioration CSS ; synchronisation en_US/es_ES/it_IT
- `3.30.1` (2026-04) : sommaire rapide (`SubTotalQuickSummary`) affiché en bouton flottant dépliable au lieu d'un menu en sidebar ; compensation des barres sticky oblyon (`FIX_AREAREF_CARD`, `FIX_STICKY_TABS_CARD`) lors du scroll

## Notes techniques (Technical notes)

### Classe `TSubtotal` (Business logic)

Le fichier `class/subtotal.class.php` contient la classe métier `TSubtotal`. Toutes les méthodes sont **statiques** ; les identifiants sont basés sur `special_code = 104777` et `product_type = 9`.

#### Identification des lignes

```php
TSubtotal::isTitle(&$line, $level = -1)         // Détecte si la ligne est un titre (optionnellement d'un niveau donné)
TSubtotal::isSubtotal(&$line, $level = -1)      // Détecte si la ligne est un sous-total
TSubtotal::isFreeText(&$line)                   // Détecte une ligne de texte libre
TSubtotal::isModSubtotalLine(&$line)            // Toute ligne spéciale du module (titre, sous-total ou texte libre)
TSubtotal::getNiveau(&$line)                    // Retourne le niveau hiérarchique (1-9) pour un titre ou sous-total
TSubtotal::hasBreakPage($line)                  // Détection d'un saut de page associé à la ligne
TSubtotal::hasNcTitle(&$line)                   // Détection d'un titre NC (Non Compris)
```

#### Manipulation des lignes

```php
TSubtotal::addTitle(&$object, $label, $level, $rang = -1, $desc = '')       // Ajoute une ligne titre
TSubtotal::addTotal(&$object, $label, $level, $rang = -1)                   // Ajoute une ligne sous-total
TSubtotal::addSubtotalMissing(&$object, $level_new_title)                   // Ajoute le sous-total manquant avant un nouveau titre
TSubtotal::updateRang(&$object, $rang_start, $move_to = 1)                  // Décale le rang des lignes
TSubtotal::duplicateLines(&$object, $lineid, $withBlockLine = false)        // Duplication de ligne ou de bloc complet
TSubtotal::doUpdateLine(&$object, $rowid, $desc, $pu, $qty, $remise_percent, ...)   // Wrapper d'update pour lignes spéciales
```

#### Recherche et parcours

```php
TSubtotal::getAllTitleFromDocument(&$object, $get_block_total = false)
TSubtotal::getAllTitleWithoutTotalFromDocument(&$object, $get_block_total = false)
TSubtotal::getAllTitleFromLine(&$origin_line, $reverse = false)
TSubtotal::getParentTitleOfLine(&$object, $rang, $lvl = 0)
TSubtotal::getSubLineOfTitle(&$object, $rang, $lvl = 0)
TSubtotal::getTotalBlockFromTitle(&$object, &$line, $breakOnTitle = false)
TSubtotal::getLinesFromTitle(&$object, $key_trad, $level = 1, $under_title = '', $withBlockLine = false, $key_is_id = false)
TSubtotal::getLinesFromTitleId(&$object, $lineid, $withBlockLine = false)
TSubtotal::titleHasTotalLine(&$object, &$title_line, $strict_mode = false, $return_rang_on_false = false)
TSubtotal::getOrderIdFromLineId(int $fk_commandedet, bool $supplier = false)
TSubtotal::getLastLineOrderId(int $fk_commande, bool $supplier = false)
```

#### Rendu et récapitulatif

```php
TSubtotal::generateDoc(&$object)                                     // Régénère le document (PDF + update_price)
TSubtotal::addRecapPage(&$parameters, &$origin_pdf, $fromInfraS = 0) // Ajoute une page récap en fin de PDF
TSubtotal::concat(&$outputlangs, $files, $fileoutput = '')           // Concaténation de PDF
TSubtotal::getFreeTextHtml(&$line, $readonly = 0)                    // HTML d'une ligne texte libre
TSubtotal::getTitleLabel($line)                                      // Libellé d'un titre
TSubtotal::getHtmlDictionnary(): string                              // HTML du sélecteur de textes libres du dictionnaire
TSubtotal::getCommonVATRate($object, $lineRef)                       // Taux TVA commun d'un ensemble
```

### Système de niveaux (Level system)

Gestion hiérarchique via la valeur `qty` :

| Type | Plage `qty` | Niveaux |
|------|-------------|---------|
| Titre | 1-9 | 1 = principal, 2..9 = sous-titres |
| Sous-total | 91-99 | 91 = principal, 92..99 = sous-sous-totaux |
| Texte libre | 50 | — |

Le niveau détermine :

- l'indentation et le style d'affichage,
- la portée du calcul du sous-total (un sous-total de niveau N couvre jusqu'au titre parent de niveau ≤ N),
- la hiérarchie visuelle dans les PDF.

### Calcul en cascade des sous-totaux (Cascade calculation)

```
POUR un sous-total à la position N :
  1. Remonter depuis la position N-1 vers 0
  2. S'arrêter au premier titre de niveau ≤ niveau(sous-total)
     OU au premier sous-total de niveau ≤ niveau(sous-total)
  3. Sommer les lignes standards entre ces bornes
  4. Exclure les lignes spéciales subtotal (isModSubtotalLine)
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

**`show_total_ht = 1`** : affiche le montant HT sur la ligne sous-total.

**`show_reduc = 1`** : cumule et affiche le total des réductions du bloc.

**`subtotal_show_qty = 1`** : cumule et affiche la quantité totale du bloc.

**`hideblock = 1`** (sur titre) :
- masque les lignes du bloc dans l'affichage (seuls titre + sous-total visibles),
- change le style du titre (`SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES`),
- les lignes cachées restent comptabilisées dans les sous-totaux suivants (dépend du paramétrage).

**`show_table_header_before = 1`** (sur titre) : répète l'en-tête du tableau juste avant ce titre dans le PDF.

**`print_as_list = 1`** (sur titre) : rendu en liste à puces, calcul standard.

**`print_condensed = 1`** (sur titre) : rendu condensé (agrégation) ; calcul TVA adapté si `SUBTOTAL_LIMIT_TVA_ON_CONDENSED_BLOCS` activé.

### Override de template (Template customization)

Le module fournit quatre fichiers dans `core/tpl/` :

| Fichier | Rôle |
|---------|------|
| `originproductline.tpl.php` | Override du rendu des lignes spéciales lors de la création d'un document depuis un autre (propal → commande, commande → facture, etc.) |
| `subtotalline_view.tpl.php` | Rendu en mode consultation d'une ligne spéciale |
| `subtotalline_edit.tpl.php` | Rendu en mode édition |
| `subtotalline_subtotal.tpl.php` | Rendu spécifique des lignes sous-total |

Classes CSS / attributs de données utilisés côté rendu :

- `.subtotal_label` — libellé principal (ciblé par le sommaire rapide)
- `tr[data-issubtotal="title"]`, `tr[data-issubtotal="total"]`, `tr[data-issubtotal="free-text"]` — distinction au niveau DOM
- `tr[data-level="..."]` — niveau hiérarchique exposé au JS

### Interaction avec les factures de situation (Progress invoices)

Les factures de situation (avancement de travaux) nécessitent un traitement spécial, réparti entre :

- **Trigger** `SituationPercentReset()` : remet à zéro les pourcentages d'avancement sur les titres/sous-totaux lors de la création d'une facture de situation,
- **Trigger** `SituationFinal()` : gère la dernière facture d'une situation (passage à 100%),
- **Hook** `beforePercentCalculation()` : intercepte le calcul des pourcentages,
- **Hook** `beforePDFCreation()` : injecte les lignes TVA invisibles pour permettre à Dolibarr d'agréger correctement (DA027547, 3.29.3),
- **Version 3.29.1** : blocage de la création d'une facture de situation si la progression est déjà à 100%,
- **Version 3.29.2** : correction d'une ancienne méthode de calcul qui accumulait la TVA (DA027405).

### API REST (API endpoints)

La classe `Subtotal` (dans `class/api_subtotal.class.php`) étend `DolibarrApi` et expose :

```
GET /subtotal/{elementtype}/{idline}
  → getTotalLine() : retourne le total calculé d'un bloc sous-total pour une ligne donnée
  → elementtype ∈ { propal, commande, facture, supplier_proposal, supplier_order, supplier_invoice }
```

La classe utilise des helpers internes (`_getTotal`, `_getFkFieldName`) pour abstraire le type de document.

Authentification : token API standard Dolibarr (`DOLAPIKEY`).

### Sommaire rapide flottant (Floating quick summary, 3.30.1)

Injecté par `actions_subtotal.class.php::formObjectOptions()` quand `SUBTOTAL_DISABLE_SUMMARY` n'est pas actif. Trois fichiers impliqués :

- **`js/summary-menu.js`** : construit un bouton flottant fixe (`#subtotal-summary-floating`) avec un dropdown listant les titres du document (`<a class="subtotal-summary-link">`). Au clic : scroll smooth vers `#row-<lineid>`.
- **`css/summary-menu.css.php`** : CSS du bouton + dropdown, adapté automatiquement au thème `oblyon` (variables `--bgnavtop*`) ou autres thèmes (`--colorbackhmenu1`).
- **Configuration JS** (`subtotalSummaryJsConf`) : injectée via `<script>` par PHP :
	- `langs.SubtotalSummaryTitle` — libellé du titre du dropdown,
	- `useOldSplittedTrForLine` — compatibilité Dolibarr < 16,
	- `isOblyon` — flag thème oblyon actif,
	- `fixArearefCard` — constante `FIX_AREAREF_CARD` active,
	- `fixStickyTabsCard` — constante `FIX_STICKY_TABS_CARD` active.

**Compensation du scroll sous oblyon** : quand `FIX_AREAREF_CARD` ou `FIX_STICKY_TABS_CARD` sont actives, `div.arearef` et/ou `div.tabs:first-of-type` deviennent `position: sticky` et masqueraient la ligne cible. Le JS ajoute leur `outerHeight()` à l'offset de scroll quand ces éléments sont effectivement en `position: sticky`.

### Drag & drop côté client (Client-side reordering)

Le drag & drop natif Dolibarr (`ajaxBlockOrderJs($object)`) est renforcé par `js/subtotal.lib.js` :

- `getSubtotalTitleChilds($item, removeLastSubtotal = false)` — retourne les lignes filles d'un titre donné (parcours via `$item.nextAll('[id^="row-"]')` jusqu'au prochain titre de niveau ≤ ou sous-total).

Le reclassement AJAX passe par `script/interface.php` (endpoint générique : JSON POST avec `set`, `element`, `elementid`, `lineid`, valeurs à mettre à jour).

### Cycle de vie du module (Module lifecycle)

**`init()`** effectue dans l'ordre :

1. Vérification du conflit avec `modMilestone` (blocage si détecté)
2. `loadTables()` → `_load_tables('/subtotal/sql/')` : création de `llx_c_subtotal_free_text` et insertion des constantes `data.sql`
3. Création des ExtraFields (`show_total_ht`, `show_reduc`, `subtotal_show_qty`, `hideblock`, `show_table_header_before`, `print_as_list`, `print_condensed`) sur toutes les tables de lignes
4. Désactivation conditionnelle du sommaire rapide si `oblyon` + `MAIN_MENU_INVERT`
5. Appel de `$this->_init()` standard

**`remove()`** : `_remove()` sans nettoyage (ExtraFields + constantes conservés pour réactivation).

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="3.30.1" MonthVersion="2026-04">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
    </Version>
    <InfraS Downloaded="YYYYMMDD"/>
    <Dolibarr minVersion="16.0.0" maxVersion="23.x.x"/>
    <PHP minVersion="7.0" maxVersion="8.2"/>
</changelog>
```

- Types de changement : `add` (ajout), `chg` (modification), `fix` (correction) — ordre recommandé dans une version : `fix → chg → add`
- L'attribut `Downloaded` est mis à jour automatiquement lors du téléchargement
- Parsé par `subtotal_getLocalVersionMinDoli()` et `subtotal_getChangeLog()` (dans `core/lib/subtotalAdmin.lib.php`)

La fonction `subtotal_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "3.30.1",          // Version courante
    1 => "16.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (-1 = KO, 0 = OK)
    3 => <SimpleXMLElement>, // Liste des versions (ou message d'erreur)
    4 => "23.x.x",           // Version max Dolibarr
    5 => "7.0",              // Version min PHP
    6 => "8.2"               // Version max PHP
]
```

### Compatibilité avec InfraSPackPlus (PDF rendering)

Les modèles PDF InfraSPlus (propal, facture, commande, etc.) intègrent nativement le support des structures subtotal :

- reconnaissance des lignes spéciales (`special_code = 104777`, `product_type = 9`),
- rendu personnalisé des titres selon `SUBTOTAL_TITLE_STYLE`,
- affichage des sous-totaux avec répartition TVA,
- support des modes `hideblock`, `print_as_list`, `print_condensed`,
- injection de `show_table_header_before` pour répéter l'en-tête de tableau,
- page récap optionnelle en fin de document (`SUBTOTAL_*_ADD_RECAP`).

### Compatibilité avec InfraSDiscount (Discount exclusion)

Le module `infrasdiscount` exclut automatiquement les lignes subtotal de ses calculs via :

```php
infrasdiscount_isSubtotalLine($line)   // special_code == 104777 && product_type == 9
infrasdiscount_isSubtotalTitle($line)  // qty ∈ [1..9]
infrasdiscount_isSubtotalTotal($line)  // qty ∈ [91..99]
```

Les calculs de remise en cascade ignorent ces lignes, ce qui garantit la cohérence des sous-totaux en présence de lignes de remise.
