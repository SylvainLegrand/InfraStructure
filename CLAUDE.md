# CLAUDE.md — Contexte module subtotal

## Aperçu (Overview)

`subtotal` est un module externe Dolibarr de structuration et organisation des documents commerciaux :

- ajout de titres et sous-totaux pour structurer les documents,
- sous-sous-totaux pour des niveaux de regroupement supplémentaires,
- textes libres insérables entre les lignes,
- réorganisation par glisser-déposer (drag & drop),
- options d'affichage avancées (masquage, impression en liste/condensée),
- dictionnaire de textes libres réutilisables.

Informations module (issues du code et du changelog local) :

- Éditeur : ATM Consulting - InfraS (Sylvain Legrand)
- Numéro module : `104777`
- Licence : GPL v3+
- Compatibilité Dolibarr : `16.0.0` à `23.x.x`
- Compatibilité PHP : `7.0` à `8.2`
- Dernière version locale : `3.29.5` (2026-02)
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
│   ├── subtotal.class.php
│   └── techatm.class.php
├── config.php
├── core/
│   ├── modules/
│   │   └── modSubtotal.class.php
│   └── tpl/
│       └── originproductline.tpl.php
├── css/
│   └── subtotal.css
├── docs/
│   └── changelog.xml
├── img/
│   ├── object_subtotal.png
│   ├── object_modsubtotal.png
│   └── [divers icônes et images]
├── js/
│   └── subtotal.js.php
├── langs/
│   ├── en_US/
│   ├── es_ES/
│   ├── fr_FR/
│   └── [autres langues]
├── lib/
│   └── subtotal.lib.php
├── script/
│   └── create-maj-extrafield.php
└── sql/
    └── llx_c_subtotal_free_text.sql
```

## Descripteur module (Module descriptor : `modSubtotal`)

Dans `core/modules/modSubtotal.class.php` :

- **Module parts** :
	- triggers : activés
	- hooks : `invoicecard`, `invoicesuppliercard`, `propalcard`, `supplier_proposalcard`, `ordercard`, `ordersuppliercard`, `odtgeneration`, `orderstoinvoice`, `orderstoinvoicesupplier`, `admin`, `invoicereccard`, `consumptionthirdparty`, `ordershipmentcard`, `expeditioncard`, `deliverycard`, `paiementcard`, `referencelettersinstacecard`, `shippableorderlist`, `propallist`, `orderlist`, `invoicelist`, `supplierorderlist`, `supplierinvoicelist`, `cron`, `pdfgeneration`, `checkmarginlist`
	- tpl : activés (template customization)
- **Dépendances** : aucune dépendance obligatoire
- **Conflit** : module `Milestone` (iNodbox)
- **Dictionnaires** : 1 dictionnaire
	- `c_subtotal_free_text` — bibliothèque de textes libres réutilisables (champs : `label`, `content`, `entity`, `active`)
- **Boxes** : aucune
- **Cron** : aucune tâche
- **ExtraFields** : création automatique de 10 ExtraFields sur les lignes de documents
- **Constantes** : 6 constantes prédéfinies
	- `SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES` (type : `chaine`, défaut : `I`) — Style des sous-titres quand détail caché (B/I/U)
	- `SUBTOTAL_ALLOW_ADD_BLOCK` (défaut : `1`) — Permet l'ajout de titres et sous-totaux
	- `SUBTOTAL_ALLOW_EDIT_BLOCK` (défaut : `1`) — Permet de modifier titres et sous-totaux
	- `SUBTOTAL_ALLOW_REMOVE_BLOCK` (défaut : `1`) — Permet de supprimer les titres et sous-totaux
	- `SUBTOTAL_TITLE_STYLE` (défaut : `BU`) — Style des titres (B=Gras, U=Souligné)
	- `SUBTOTAL_SUBTOTAL_STYLE` (défaut : `B`) — Style des sous-totaux (B=Gras)
- **Permissions** : aucune permission définie (module accessible à tous les utilisateurs ayant accès aux documents)

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Vérification du conflit avec le module Milestone (si activé : erreur bloquante)
2. Chargement des tables SQL (`loadTables()` → `_load_tables('/subtotal/sql/')`)
3. Création des ExtraFields sur les lignes de documents (`propaldet`, `commandedet`, `facturedet`, `supplier_proposaldet`, `commande_fournisseurdet`, `facture_fourn_det`) :
	 - `show_total_ht` (int) — Afficher le Total HT sur le sous-total
	 - `show_reduc` (int) — Afficher la réduction sur le sous-total
	 - `subtotal_show_qty` (int) — Afficher la quantité du sous-total
	 - `hideblock` (int) — Cacher les lignes contenues dans ce titre
	 - `show_table_header_before` (int) — Afficher l'en-tête du tableau juste avant ce titre
	 - `print_as_list` (int) — Imprimer le contenu sous forme de liste
	 - `print_condensed` (int) — Imprimer le contenu de manière condensée
4. Désactivation automatique du sommaire rapide si module `oblyon` activé avec menu inversé

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue simplement un appel à `_remove()` sans action spécifique (les ExtraFields et les constantes sont conservés pour permettre une réactivation sans perte de données).

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `actions_subtotal.class.php` pour les hooks d'injection des fonctionnalités sur les documents,
- `subtotal.class.php` pour le modèle de données et la logique métier (classe `TSubtotal`),
- `subtotal.lib.php` pour les fonctions utilitaires et helpers,
- `subtotal.js.php` pour l'interface drag & drop et les interactions utilisateur,
- `originproductline.tpl.php` pour le template d'affichage des lignes (override complet des lignes spéciales),
- les triggers pour la gestion des conversions de documents et la préservation des structures.

### Types de lignes spéciales

Le module ajoute 3 types de lignes spéciales identifiées par `special_code = 104777` (numéro du module) et `product_type = 9` :

| Type | Valeur `qty` | Description | Utilisation |
|------|-------------|-------------|-------------|
| **Titre** | 0-9 | En-tête de section | Structuration de niveau 1 |
| **Sous-total** | 90-99 | Ligne de totalisation intermédiaire | Affichage des montants cumulés |
| **Texte libre** | 100+ | Bloc de texte explicatif | Annotations, explications |

Sous-niveaux supportés (via ExtraFields et styling) :

- Sous-titre (niveau 2)
- Sous-sous-total (niveau 3)

### Logique de calcul des sous-totaux

Les sous-totaux calculent automatiquement :

- **Total HT** — somme des lignes entre le titre et le sous-total (ou entre deux sous-totaux)
- **Quantité totale** — somme des quantités (si option `subtotal_show_qty` activée)
- **Réduction totale** — somme des réductions (si option `show_reduc` activée)
- **TVA** — répartition par taux de TVA

Le calcul exclut :

- Les lignes de remise du module `infrasdiscount` (via `special_code`)
- Les lignes masquées (via `hideblock`)
- Les autres lignes spéciales (titres, sous-totaux, textes libres)

### Gestion du drag & drop

JavaScript (`subtotal.js.php`) gère :

- Déplacement de lignes individuelles
- Déplacement de blocs entiers (titre + contenu jusqu'au sous-total)
- Zones de drop visuelles avec feedback
- Sauvegarde AJAX des nouvelles positions (`rank`)
- Recalcul automatique des sous-totaux après réorganisation

## Hooks et comportement (Hook behavior)

La classe `ActionsSubtotal` (dans `class/actions_subtotal.class.php`) intervient sur les contextes :

| Hook | Contexte | Rôle |
|------|----------|------|
| `printFieldListSelect` | `consumptionthirdparty` | Exclusion des lignes spéciales des statistiques de consommation |
| `printFieldListWhere` | listes (`propallist`, `orderlist`, etc.) | Exclusion des lignes spéciales des listes de documents |
| `doActions` | tous contextes de cartes | Gestion des actions utilisateur (ajout, édition, suppression de blocs) |
| `formObjectOptions` | cartes de documents | Injection des boutons d'action (ajouter titre/sous-total/texte libre) |
| `addMoreActionsButtons` | cartes de documents | Injection des boutons supplémentaires en bas de fiche |
| `printObjectLine` | cartes de documents | Override complet de l'affichage des lignes spéciales |
| `pdf_getlineX_Y` | génération PDF | Personnalisation du rendu PDF des lignes spéciales |
| `pdfgeneration` | génération PDF | Injection de logique avant/après génération PDF |
| `afterPDFCreation` | génération PDF | Post-traitement après génération PDF |

### Flux des hooks (Hook workflow)

```
Utilisateur accède à une fiche document (devis/commande/facture)
    ↓
formObjectOptions() : injection des boutons "Ajouter titre/sous-total/texte libre"
    ↓
doActions() : traitement des soumissions de formulaire
    - Action 'add_title' : création d'une ligne titre (special_code=104777, product_type=9, qty=0-9)
    - Action 'add_subtotal' : création d'une ligne sous-total (qty=90-99)
    - Action 'add_freetext' : création d'une ligne texte libre (qty=100+)
    - Action 'edit_block' : modification d'une ligne spéciale
    - Action 'delete_block' : suppression d'une ligne spéciale
    ↓
printObjectLine() : affichage personnalisé des lignes spéciales
    - Titres : affichage avec style (B/I/U selon configuration)
    - Sous-totaux : calcul et affichage des montants cumulés
    - Textes libres : affichage du contenu riche (HTML possible)
    ↓
JavaScript (subtotal.js.php) : activation du drag & drop
    - Listener sur événement drag & drop
    - Appel AJAX pour mise à jour du `rank` des lignes
    - Rechargement de la page pour affichage actualisé
```

### Modes d'affichage PDF

Trois modes contrôlés par ExtraFields sur les titres :

| Mode | ExtraField | Comportement |
|------|-----------|-------------|
| **Standard** | — | Affichage de toutes les lignes en mode tableau |  
| **Liste** | `print_as_list = 1` | Affichage du contenu sous forme de liste à puces |
| **Condensé** | `print_condensed = 1` | Affichage compact (1 ligne par produit avec quantités agrégées) |

Option de masquage (`hideblock = 1`) : cache les lignes du détail dans le PDF (seul le titre et le sous-total sont visibles).

## Triggers et interactions (Trigger behavior)

Le module n'a pas de trigger dédié via fichier `interface_*.class.php`, mais utilise le système de hooks pour :

- **Conversion de documents** : préservation des structures lors des transformations (devis → commande → facture, commande → expédition, etc.)
- **Factures de situation** : gestion spéciale pour la conservation des structures lors des avancements de travaux
- **Actions massives** : support de la création de factures en masse depuis les commandes (avec préservation des structures)

### Références d'expédition dans les titres

Option spéciale (version 3.26.0+) : possibilité d'inclure automatiquement la référence d'expédition dans le libellé des titres lors de la génération d'expéditions depuis une commande.

## Données / SQL (Data model)

Le module crée une table dictionnaire :

| Table | Description |
|-------|-------------|
| `llx_c_subtotal_free_text` | Bibliothèque de textes libres réutilisables |

Schéma de `llx_c_subtotal_free_text` :

| Colonne | Type | Description |
|---------|------|-------------|
| `rowid` | INTEGER AUTO_INCREMENT PRIMARY KEY | Clé primaire |
| `label` | VARCHAR(255) | Libellé du texte libre |
| `content` | TEXT | Contenu du texte libre (HTML possible) |
| `entity` | INTEGER DEFAULT 1 NOT NULL | Entité multi-société |
| `active` | TINYINT DEFAULT 1 | Actif ou non |

Les métadonnées de structure sont stockées via ExtraFields sur les lignes de documents (`propaldet`, `commandedet`, `facturedet`, `supplier_proposaldet`, `commande_fournisseurdet`, `facture_fourn_det`) :

| ExtraField | Type | Valeurs | Cibles |
|------------|------|---------|--------|
| `show_total_ht` | int | 0/1 | Sous-totaux : afficher le Total HT |
| `show_reduc` | int | 0/1 | Sous-totaux : afficher la réduction |
| `subtotal_show_qty` | int | 0/1 | Sous-totaux : afficher la quantité totale |
| `hideblock` | int | 0/1 | Titres : cacher les lignes contenues dans le bloc |
| `show_table_header_before` | int | 0/1 | Titres : afficher l'en-tête du tableau avant ce titre |
| `print_as_list` | int | 0/1 | Titres : imprimer le contenu sous forme de liste |
| `print_condensed` | int | 0/1 | Titres : imprimer le contenu de manière condensée |

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `SUBTOTAL_ALLOW_ADD_BLOCK` — Autoriser l'ajout de titres et sous-totaux (défaut : `1`)
- `SUBTOTAL_ALLOW_EDIT_BLOCK` — Autoriser la modification des titres et sous-totaux (défaut : `1`)
- `SUBTOTAL_ALLOW_REMOVE_BLOCK` — Autoriser la suppression des titres et sous-totaux (défaut : `1`)
- `SUBTOTAL_TITLE_STYLE` — Style des titres : B (gras), I (italique), U (souligné), combinaisons possibles (défaut : `BU`)
- `SUBTOTAL_SUBTOTAL_STYLE` — Style des sous-totaux : B/I/U (défaut : `B`)
- `SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES` — Style des titres quand les lignes sont cachées (défaut : `I`)
- `SUBTOTAL_DISABLE_SUMMARY` — Désactiver le sommaire rapide (défaut : non défini)
- `SUBTOTAL_HIDE_FOLDERS_BY_DEFAULT` — Masquer les blocs par défaut (défaut : non défini)
- `SUBTOTAL_LIMIT_TVA_ON_CONDENSED_BLOCS` — Limiter la TVA sur les blocs condensés (défaut : non défini)
- `NO_TITLE_SHOW_ON_EXPED_GENERATION` — Ne pas afficher les titres lors de la génération d'expéditions (défaut : non défini)

## Compatibilité modules tiers

Le module est compatible avec :

- **Sous-Total** (ATM Consulting - version améliorée par InfraS)
- **Milestone/Jalon** (iNodbox) — **CONFLIT** : les deux modules ne peuvent pas être activés simultanément
- **Ouvrage/Forfait** (Inovea)
- **Équipement** (Patas-Monkey)
- **Custom Link** (Patas-Monkey)
- **Note de Frais Plus** (Mikael Carlavan)
- **Ultimate** (ATM Consulting)
- **InfraSPackPlus** (InfraS) — support complet des structures dans les modèles PDF
- **InfraSDiscount** (InfraS) — exclusion automatique des lignes spéciales des calculs de remise

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.0–8.2 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / ExtraFields / hooks :

1. Désactiver puis réactiver le module
2. Vérifier les ExtraFields sur `propaldet`, `commandedet`, `facturedet`, `supplier_proposaldet`, `commande_fournisseurdet`, `facture_fourn_det`
3. Vérifier les constantes module (`SUBTOTAL_*`)
4. Vérifier le dictionnaire `c_subtotal_free_text`
5. Tester l'ajout de titre/sous-total/texte libre sur un devis
6. Tester le drag & drop de lignes
7. Tester la conversion devis → commande → facture (préservation des structures)
8. Tester la génération PDF avec les différents modes d'affichage

## Points d'attention (Watchpoints)

- Le module utilise `special_code = 104777` et `product_type = 9` pour identifier les lignes spéciales
- Les valeurs de `qty` identifient le type de ligne : 0-9 (titre), 90-99 (sous-total), 100+ (texte libre)
- Le module est **incompatible** avec le module Milestone/Jalon (iNodbox)
- L'initialisation vérifie l'activation du module Milestone et bloque l'activation si détecté
- Les calculs de sous-totaux excluent automatiquement les lignes de remise et les autres lignes spéciales
- Le drag & drop nécessite JavaScript activé (`$conf->use_javascript_ajax`)
- Le template `originproductline.tpl.php` override complètement l'affichage des lignes spéciales
- Pour les factures de situation, une méthode de calcul spéciale préserve les structures lors des avancements

## Dernières mises à jour (Recent updates)

- `3.25.0` (2024-07) : compatibilité Dolibarr 16 → 20 ; ajout hook `pdfgeneration`
- `3.25.1` (2024-07) : correction résumé titre
- `3.25.2` (2024-08) : correction CKEditor version check
- `3.25.3` (2024-08) : correction GETPOST type integer incorrect
- `3.25.4` (2024-12) : option pour désactiver le style des titres
- `3.25.5` (2024-12) : correction `NO_TITLE_SHOW_ON_EXPED_GENERATION` non fonctionnel
- `3.25.6` (2025-03) : refactor trigger `SHIPPING_CREATE`
- `3.25.7` (2025-03) : correction null-coalesce
- `3.26.0` (2024-09) : ajout action massive création facture + référence expédition dans titre
- `3.26.1` (2024-09) : correction colonne manquante dans documents
- `3.27.0` (2024-10) : utilisation dropdown pour boutons d'action
- `3.28.0` (2025-01) : ajout option `SUBTOTAL_HIDE_FOLDERS_BY_DEFAULT`
- `3.28.1` (2025-02) : correction affichage détails hors bloc sous-total (DA026083)
- `3.28.2` (2025-03) : correction affichage prix PDF (DA026204)
- `3.28.3` (2025-04) : correction boutons fournisseur (DA026337)
- `3.28.4` (2025-05) : suppression warning ; ajout configuration `SUBTOTAL_LIMIT_TVA_ON_CONDENSED_BLOCS`
- `3.28.5` (2025-05) : correction hook `printfieldlistWhere`
- `3.28.6` (2025-05) : correction problème template avec lignes texte libre et colspan
- `3.28.7` (2025-11) : correction du `special_code` manquant lors de la création de facture depuis expédition (DA027316)
- `3.29.0` (2025-07) : compatibilité Dolibarr 22
- `3.29.1` (2025-10) : correction de l'ordre SQL lors de la création de facture depuis commande ; empêche la création de facture de situation si progression à 100%
- `3.29.2` (2025-12) : correction de l'ancienne méthode de calcul utilisée pour les factures de situation (DA027405)
- `3.29.3` (2026-01) : correction de l'affichage de la TVA dans les PDF lorsque l'option `hideInnerLines` est activée ; correction de l'accumulation de TVA dans les modèles PDF subtotal ; injection de lignes TVA invisibles pour permettre le calcul correct dans Dolibarr (DA027547)
- `3.29.4` (2025-12) : compatibilité Dolibarr 23
- `3.29.5` (2026-02) : correction DA027702

## Notes techniques (Technical notes)

### Classe TSubtotal (Business logic)

Le fichier `class/subtotal.class.php` contient la classe métier `TSubtotal` qui gère toute la logique de traitement des lignes spéciales :

#### Propriétés statiques

- `TSubtotal::$module_number = 104777` — identifiant unique du module utilisé comme `special_code`
- `TSubtotal::$TYPE_PRODUCT = 9` — valeur de `product_type` pour toutes les lignes spéciales
- `TSubtotal::$QTY_TITLE = 0-9` — plage de valeurs `qty` pour les titres
- `TSubtotal::$QTY_SUBTOTAL = 90-99` — plage de valeurs `qty` pour les sous-totaux
- `TSubtotal::$QTY_FREETEXT = 100+` — valeur `qty` pour les textes libres

#### Méthodes principales

**Identification des lignes** :

```php
TSubtotal::isTitle($line)          // Détecte si ligne est un titre
TSubtotal::isSubTotal($line)       // Détecte si ligne est un sous-total
TSubtotal::isFreeText($line)       // Détecte si ligne est un texte libre
TSubtotal::isModLine($line)        // Détecte si ligne appartient au module
TSubtotal::getLevel($line)         // Retourne le niveau (0-9 pour titre, 90-99 pour sous-total)
```

**Calcul des totaux** :

```php
TSubtotal::getSubTotalAmount($object, $line_index)
// Calcule le montant total HT des lignes entre le titre et le sous-total
// Paramètres :
//   - $object : objet document (Propal, Commande, Facture)
//   - $line_index : index de la ligne sous-total
// Retour : array('total_ht' => float, 'total_qty' => float, 'total_reduc' => float)

TSubtotal::getSubTotalVAT($object, $line_index)
// Calcule la répartition TVA des lignes du bloc
// Retour : array(taux_tva => montant_tva, ...)
```

**Manipulation des lignes** :

```php
TSubtotal::addTitleLine($object, $label, $level = 0, $options = [])
// Ajoute une ligne titre au document
// Paramètres :
//   - $object : objet document
//   - $label : libellé du titre
//   - $level : niveau du titre (0-9)
//   - $options : array d'options (hideblock, show_table_header_before, etc.)

TSubtotal::addSubTotalLine($object, $label, $level = 90, $options = [])
// Ajoute une ligne sous-total au document

TSubtotal::addFreeTextLine($object, $content, $options = [])
// Ajoute une ligne texte libre au document
```

### Système de niveaux (Level system)

Gestion des niveaux hiérarchiques via la valeur `qty` :

#### Titres (qty = 0-9)

```
qty = 0 → Titre niveau 1 (principal)
qty = 1 → Sous-titre niveau 2
qty = 2 → Sous-sous-titre niveau 3
...
qty = 9 → Niveau 10 (maximum)
```

#### Sous-totaux (qty = 90-99)

```
qty = 90 → Sous-total niveau 1 (principal)
qty = 91 → Sous-total niveau 2
qty = 92 → Sous-total niveau 3
...
qty = 99 → Niveau 10 (maximum)
```

Le niveau détermine :
- Le style d'affichage (indentation, taille de police)
- La portée du calcul de sous-total
- La hiérarchie visuelle dans le document

### Calcul en cascade des sous-totaux (Cascade calculation)

Le calcul d'un sous-total suit cette logique :

```
POUR CHAQUE ligne sous-total à la position N :
  1. Remonter depuis la position N-1 vers 0
  2. S'arrêter au premier titre rencontré OU au premier sous-total de même niveau
  3. Sommer toutes les lignes standards entre ces bornes
  4. Exclure les lignes spéciales (titres, sous-totaux, textes libres)
  5. Exclure les lignes de remise (module infrasdiscount)
  6. Exclure les lignes masquées (hideblock sur le titre parent)
```

Exemple de structure :

```
Ligne 1 : Titre "Matériel informatique" (qty=0)
Ligne 2 : Produit A — 100,00 €
Ligne 3 : Produit B — 200,00 €
Ligne 4 : Sous-total "Matériel" (qty=90) → calcule lignes 2+3 = 300,00 €
Ligne 5 : Titre "Services" (qty=0)
Ligne 6 : Service X — 150,00 €
Ligne 7 : Sous-total "Services" (qty=90) → calcule ligne 6 seulement = 150,00 €
```

### Gestion des ExtraFields dans les calculs (ExtraFields in calculations)

Les ExtraFields influencent le calcul et l'affichage :

**`show_total_ht = 1`** : affiche explicitement le montant HT sur la ligne sous-total

**`show_reduc = 1`** : calcule et affiche le total des réductions accordées dans le bloc

**`subtotal_show_qty = 1`** : cumule et affiche la quantité totale d'articles dans le bloc

**`hideblock = 1`** (sur titre) : 
- Cache les lignes du bloc dans l'affichage (seul titre + sous-total visibles)
- Change le style du titre (utilise `SUBTOTAL_STYLE_TITRES_SI_LIGNES_CACHEES`)
- Les lignes cachées sont ignorées dans les calculs de sous-total suivants

**`print_as_list = 1`** (sur titre) :
- Affiche les lignes du bloc sous forme de liste à puces dans le PDF
- Conserve le calcul normal du sous-total

**`print_condensed = 1`** (sur titre) :
- Affiche les lignes du bloc de manière compacte (agrégation par référence)
- Calcul spécial de TVA si `SUBTOTAL_LIMIT_TVA_ON_CONDENSED_BLOCS` activé

### Template override (Template customization)

Le fichier `core/tpl/originproductline.tpl.php` override complètement le rendu des lignes spéciales :

```php
// Point d'entrée du template
if ($this->tpl['subtotal'] ?? '' == $this->tpl['id'] 
    && in_array($this->tpl['sub-type'] ?? '', array('title', 'total', 'freetext'))) {
    // Affichage personnalisé complet
    // Pas de rendu du template standard
    return;
}
```

Structure du rendu personnalisé :

```
<tr class="subtotal-line subtotal-TYPE">
  <td colspan="X" class="subtotal-content">
    [Contenu selon le type : titre / sous-total / texte libre]
  </td>
</tr>
```

Classes CSS utilisées :

- `.subtotal-line` — ligne spéciale générique
- `.subtotal-title` — ligne titre
- `.subtotal-total` — ligne sous-total
- `.subtotal-freetext` — ligne texte libre
- `.subtotal-level-N` — modification selon le niveau (0-9, 90-99)
- `.subtotal-hidden` — bloc caché (`hideblock = 1`)

### Interaction avec les factures de situation (Progress invoices)

Les factures de situation (avancement de travaux) nécessitent un traitement spécial :

1. **Préservation des structures** : lors de la création d'une facture de situation depuis une commande, les titres/sous-totaux sont copiés avec leur configuration
2. **Calcul des montants** : les sous-totaux sont recalculés en fonction du pourcentage d'avancement
3. **Méthode de calcul spécifique** : utilisation d'une méthode de calcul alternative pour éviter l'accumulation de TVA (DA027405, version 3.29.2)
4. **Injection de lignes TVA invisibles** : pour permettre le calcul correct dans Dolibarr tout en masquant le détail (DA027547, version 3.29.3)
5. **Blocage à 100%** : empêche la création d'une nouvelle facture de situation si la progression est déjà à 100% (version 3.29.1)

### API REST (API endpoints)

Le module expose une API REST via `class/api_subtotal.class.php` :

```
GET /subtotal/freetexts
  → Liste les textes libres du dictionnaire

POST /subtotal/freetext
  → Crée un nouveau texte libre dans le dictionnaire

PUT /subtotal/freetext/{id}
  → Met à jour un texte libre existant

DELETE /subtotal/freetext/{id}
  → Supprime un texte libre du dictionnaire
```

Authentification : via token API standard Dolibarr (`DOLAPIKEY` header).

### Drag & Drop JavaScript (Client-side logic)

Le fichier `js/subtotal.js.php` implémente le système de réorganisation :

```javascript
// Initialisation du système drag & drop
$(document).ready(function() {
    initSubtotalDragDrop();
});

// Fonction principale
function initSubtotalDragDrop() {
    // 1. Rendre toutes les lignes draggables
    $('.subtotal-draggable').draggable({
        helper: 'clone',
        revert: 'invalid',
        cursor: 'move'
    });
    
    // 2. Définir les zones de drop
    $('.subtotal-dropzone').droppable({
        accept: '.subtotal-draggable',
        drop: function(event, ui) {
            // 3. Appel AJAX pour mise à jour du rank
            updateLineRank(lineId, newPosition);
        }
    });
}

// Mise à jour du rank en base
function updateLineRank(lineId, newPosition) {
    $.ajax({
        url: 'ajax/update_rank.php',
        data: { line_id: lineId, new_position: newPosition },
        success: function() {
            location.reload(); // Rechargement pour affichage actualisé
        }
    });
}
```

Comportement spécial :

- **Bloc complet** : déplacer un titre déplace automatiquement toutes les lignes jusqu'au sous-total suivant
- **Ligne isolée** : déplacer une ligne standard la sort de son bloc actuel et l'insère à la nouvelle position
- **Zones interdites** : impossibilité de placer un sous-total avant son titre parent

### Compatibilité avec InfraSPackPlus (PDF rendering)

Le module InfraSPackPlus (modèles PDF InfraS) intègre nativement le support des structures subtotal :

- Reconnaissance automatique des lignes spéciales (`special_code = 104777`)
- Rendu personnalisé des titres avec styles configurables
- Calcul et affichage des sous-totaux avec répartition TVA
- Support du mode `hideblock` (masquage du détail)
- Support du mode `print_as_list` (affichage en liste)
- Support du mode `print_condensed` (affichage condensé)
- Préservation des structures lors de la génération de tous types de documents

### Compatibilité avec InfraSDiscount (Discount exclusion)

Le module InfraSDiscount exclut automatiquement les lignes spéciales subtotal de ses calculs :

```php
// Dans infrasdiscount.lib.php
function infrasdiscount_isSubtotalLine($line) {
    return ($line->special_code == 104777 && $line->product_type == 9);
}

// Utilisation dans les fonctions de calcul
if (infrasdiscount_isSubtotalLine($line)) {
    continue; // Ignore la ligne dans le calcul de remise
}
```

Cette exclusion garantit que :
- Les remises ne s'appliquent pas aux lignes de structure
- Les calculs de sous-totaux restent cohérents
- Les deux modules peuvent coexister sans conflit
