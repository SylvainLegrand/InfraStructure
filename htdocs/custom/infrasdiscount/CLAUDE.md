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
- Compatibilité Dolibarr : `15.0.0` à `23.0.4`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `15.3.2` (2026-03)
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

- `15.3.0` (2026-02) : durcissements sécurité — sanitisation GETPOST, protection XSS sur `PHP_SELF` et `SERVER_SOFTWARE`, restriction regex des constantes, externalisation OAuth2, contrôles permissions, remplacement `addslashes()` par `$db->escape()`
- `15.3.0` (2026-02) : correction de l'appel `infraspackplus_print_input()` → `infrasdiscount_print_input()`, du modulepart backup et du slash manquant dans `dol_buildpath()`
- `15.3.0` (2026-02) : isolation du cookie JS (`infrasdiscount_tblPSexp`) et alignement sur les conventions des modules InfraS
- `15.3.0` (2026-02) : remplacement syntaxe dépréciée `$user->rights` par `$user->hasRight()`
- `15.3.0` (2026-02) : ajout du fichier CLAUDE.md
- `15.3.1` (2026-02) : correction du trigger `BILL_PAYED` quand l'authentification Sort&Group n'est pas activée
- `15.3.2` (2026-03) : Modification du trigger `BILL_PAYED` ajout d'une option pour l'utilisation du lien SortAndGroup
- `15.3.2` (2026-03) : ajout d'une option pour l'utilisation du lien SortAndGroup dans le trigger
- `15.3.2` (2026-03) : ajout d'un test de comparaison de la version majeure Dolibarr (avertissement si version non supportée)
- `15.3.2` (2026-03) : amélioration du descripteur CLAUDE.md : ajout des Notes techniques
- Entrées du changelog par version (types : `add`, `chg`, `fix`)
Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis. Un avertissement s'affiche à la connexion si Dolibarr dépasse la version max supportée.
## Notes techniques (Technical notes)
### Moteur de calcul des remises (Discount calculation engine)
Le fichier `infrasdiscount.lib.php` contient le moteur central de calcul, structuré en fonctions spécialisées :
**Fonctions de création** (appelées depuis `doActions` dans la classe hook) :
- `infrasdiscount_createDiscountLines()` — point d'entrée principal, dispatche vers le type approprié
- `infrasdiscount_createPercentDiscount()` — remise en pourcentage, calcul cascade sur les lignes au-dessus
- `infrasdiscount_createAmountDiscount()` — remise en montant fixe, simple ou prorata si produits+services
- `infrasdiscount_createProrataDiscount()` — répartition proportionnelle entre produits et services
- `infrasdiscount_createTotalTTCDiscount()` — calcul inverse depuis un TTC cible, avec correction d'arrondis
**Fonctions de recalcul** (appelées depuis le trigger et le hook `formObjectOptions`) :
- `infrasdiscount_recalculatePercentDiscounts()` — recalcule toutes les remises % en mode cascade
- `infrasdiscount_recalculateProrataDiscounts()` — recalcule toutes les paires prorata en mode cascade
**Fonctions utilitaires** :
- `infrasdiscount_addDiscountLine()` — wrapper unifié pour `addline()` (propal/commande/facture)
- `infrasdiscount_updateRemLine()` — wrapper unifié pour `updateline()` (propal/commande/facture)
- `infrasdiscount_calculateCascadeBase()` — calcule la base HT en cascade (uniquement les lignes AU-DESSUS de la position)
- `infrasdiscount_getDiscountProductRefs()` — récupère les références produit/service de remise depuis `llx_const`
- `infrasdiscount_getTypesToProcess()` — détermine les types (produit, service, les deux) selon le choix utilisateur
### Calcul en cascade (Cascade calculation)
Le mode cascade signifie que chaque ligne de remise calcule uniquement sur les lignes situées **au-dessus d'elle** dans le document :
```
Ligne 1 : Produit A — 100,00 €
Ligne 2 : Produit B — 200,00 €
Ligne 3 : Remise 10% → calcul sur lignes 1+2 = -30,00 €
Ligne 4 : Produit C — 150,00 €
Ligne 5 : Remise 5% → calcul sur lignes 1+2+3+4 = -21,00 € (base après remise précédente)
```
Ce comportement est implémenté dans `infrasdiscount_calculateCascadeBase()` via une boucle `for ($i = 0; $i < $position; $i++)`.
### Valeur cible TTC (Target TTC value)
Le calcul de la valeur cible TTC utilise un algorithme en deux étapes :
1. **Analyse** (`infrasdiscount_analyzeLinesForTotalTTC`) : regroupe les lignes par type (produit/service) et par taux de TVA, calcule le total TTC courant
2. **Calcul** (`infrasdiscount_calculateTTCDiscountLines`) :
   - Répartit la remise TTC nécessaire au prorata de chaque groupe
   - Convertit chaque part TTC en HT : `HT = TTC / (1 + taux_tva / 100)`
   - Corrige les arrondis en redistribuant les centimes restants sur les groupes les plus importants
### Gestion multi-devises (Multi-currency management)
Toutes les fonctions de remise supportent le multi-devises lorsque le module Dolibarr `multicurrency` est activé :
```php
// Vérification : le module multicurrency est activé ET le taux de change est != 1
infrsdiscount_multicurrency_enabled($object)
// Conversions sécurisées (protection division par zéro)
infrsdiscount_to_foreign($amount_base, $object)  // devise de base → devise étrangère
infrsdiscount_to_base($amount_foreign, $object)   // devise étrangère → devise de base
// Préparation unifiée des prix (gère les cas base seule, devise seule, ou les deux)
infrsdiscount_prepare_prices($pu_ht, $pu_ht_devise, $object)
```
Règle métier : si l'utilisateur saisit un montant en devise étrangère (`$pu_ht_devise`), le montant de base est recalculé à partir de la devise ; sinon, la devise est calculée depuis la base.
### Flux des hooks (Hook workflow)
La classe `ActionsInfraSDiscount` intervient sur les contextes `propalcard`, `ordercard`, `invoicecard` selon ce flux :
```
L'utilisateur accède à une fiche document (devis/commande/facture)
    ↓
afterLogin() : vérifie la version max Dolibarr supportée
    via explode('.', DOL_VERSION)[0] vs explode('.', maxVersion)[0]
    ↓
addMoreActionsButtons() : injecte les boutons « Remise » et « Modifier remise »
    (conditionné par : statut brouillon, type document activé, permission 'use')
    ↓
formConfirm() : affiche le popup de création ou modification de remise
    → création : radio percent/amount/total_ttc, select produit/service/les deux,
                 champ TVA, libellé personnalisé, champ devise si multicurrency
    → modification : liste les remises existantes avec champs de saisie préremplis
    ↓
doActions() : traite la soumission du formulaire
    → handleCreateDiscount() → infrasdiscount_createDiscountLines()
    → handleModifyDiscount() → met à jour chaque ligne via infrasdiscount_updateRemLine()
    ↓
formObjectOptions() : après chaque modification de ligne,
    recalcule automatiquement les remises % et prorata via
    infrasdiscount_recalculatePercentDiscounts() et
    infrasdiscount_recalculateProrataDiscounts()
    (protégé par un flag static $isRecalculating anti-récursion)
```
### Trigger et prévention de récursion (Trigger and recursion prevention)
Le trigger `InterfaceInfrasdiscounttrigger` écoute les actions sur les lignes de documents et utilise un mécanisme de double protection contre la récursion :
**Flag statique dans `updateRemise()`** :
```php
static $isUpdating = false;
if ($isUpdating) return 0;
try {
    $isUpdating = true;
    // Recalcul des remises %, prorata, suppression des lignes à 0, régénération PDF
} finally {
    $isUpdating = false;
}
```
Ce pattern est nécessaire car les appels à `addline()`, `updateline()` et `deleteline()` sur les lignes de remise déclenchent à nouveau les triggers `LINEPROPAL_INSERT/UPDATE/DELETE`, ce qui provoquerait une boucle infinie sans ce flag.
**Événements écoutés par le trigger** :
| Événement | Action |
|-----------|--------|
| `LINEPROPAL_INSERT/UPDATE/MODIFY/DELETE` | Recalcul de toutes les remises du document |
| `LINEORDER_INSERT/UPDATE/MODIFY/DELETE` | Recalcul de toutes les remises du document |
| `LINEBILL_INSERT/UPDATE/MODIFY/DELETE` | Recalcul de toutes les remises du document |
| `ORDER_VALIDATE` | Déclenche les remises automatiques (`validateRemiseAutomatique`) |
| `BILL_PAYED` | Appel API Sort&Group si `INFRASDISCOUNT_SORTANDGROUP` activé |
**Flux du recalcul (`updateRemise`)** :
1. Recharge l'objet complet avec ses lignes
2. Recalcule les remises en pourcentage (cascade)
3. Recalcule les remises prorata (cascade)
4. Supprime les lignes de remise à montant nul (arrondi à 0)
5. Régénère le PDF si `MAIN_DISABLE_PDF_AUTOUPDATE` n'est pas activé
6. Met à jour le prix total via `$element->update_price()`
### Remise automatique sur validation de commande (Automatic discount on order validation)
`validateRemiseAutomatique()` applique des remises à la validation de commande selon les constantes :
- `INFRASDISCOUNT_PRODUCT_AFFILIATE` : liste de produits éligibles (multiselect)
- `INFRASDISCOUNT_FREE_LINE` : nombre d'unités offertes
- `INFRASDISCOUNT_NUMBER_DISCOUNT_ALLOW` : nombre max de commandes validées du client pour bénéficier de la remise
- `INFRASDISCOUNT_PONDERATION` : produit prioritaire pour la remise (si défini, les unités gratuites s'appliquent d'abord sur ce produit)
- `INFRASDISCOUNT_DESC_FREETEXT` : texte libre ajouté à la description de la remise auto
Les lignes de remise automatique utilisent `special_code = 9` et `specialtype = 4`.
### API Sort&Group (`payFacture`)
Lorsque `INFRASDISCOUNT_SORTANDGROUP` est activé et qu'une facture est payée (`BILL_PAYED`) :
1. Récupère un token OAuth2 via `INFRASDISCOUNT_OAUTH_URL` avec les identifiants `INFRASDISCOUNT_OAUTH_CLIENT_ID` / `INFRASDISCOUNT_OAUTH_CLIENT_SECRET`
2. Appelle l'API `https://icr-api.sortandgroup.fr/api/batches/{batch_id}/_payment` avec le token
3. Le `batch_id` est lu depuis l'ExtraField `options_uid` de la facture
### ExtraField `specialtype` (Discount line identification)
Les lignes de remise sont identifiées par l'ExtraField `specialtype` (int) créé automatiquement sur `propaldet`, `commandedet`, `facturedet` :
| Valeur | Type de remise | Description |
|--------|---------------|-------------|
| 1 | Pourcentage | Remise en % sur produits ou services (cascade) |
| 2 | Montant fixe | Remise en montant sur un seul type (produit ou service) |
| 3 | Prorata | Remise en montant répartie au prorata entre produits et services (paire) |
| 4 | Total TTC / Auto | Remise calculée pour atteindre un TTC cible, ou remise automatique |
Les lignes prorata fonctionnent **par paires** : une ligne `product_type=0` (produit) et une ligne `product_type=1` (service), toutes deux avec `specialtype=3`.
### Compatibilité avec les modules externes (External module compatibility)
Le module détecte et exclut les lignes des modules externes des calculs de remise :
- **Subtotal ATM** (`special_code = 104777`, `product_type = 9`) : titres, sous-totaux et textes libres détectés via `infrasdiscount_isSubtotalLine()`, `infrasdiscount_isSubtotalTitle()`, `infrasdiscount_isSubtotalTotal()`
- **Autres modules** (MileSton, Ouvrage) : détectés via `infrasdiscount_isLineFromExternalModule()` qui compare le `special_code` de la ligne avec le numéro de module (`$objMod->numero`)
### Structure du changelog (Changelog structure)
```xml
<changelog>
    <Version Number="15.3.2" MonthVersion="2026-03">
        <change type='chg'>Trigger : ajout d'une option pour l'utilisation du lien SortAndGroup</change>
        <change type='add'>Ajout d'un test de comparaison de la version majeur de Dolibarr</change>
    </Version>
    <InfraS Downloaded="20260301"/>
    <Dolibarr minVersion="15.0.0" maxVersion="23.0.4"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```
La fonction `infrasdiscount_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "15.3.2",           // Version courante
    1 => "15.0.0",           // Version min Dolibarr
    2 => "20260301",         // Date de téléchargement
    3 => "",                 // (non utilisé)
    4 => "23.0.4",           // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```
### Cycle de vie du module (Module lifecycle)
**`init()`** effectue dans l'ordre :
1. Chargement des tables SQL (`_load_tables('/infrasdiscount/sql/')`) — exécute `data.sql`
2. Restauration des paramètres sauvegardés (`infrasdiscount_restore_module`)
3. Initialisation des constantes `INFRASDISCOUNT_DOL_VERSION` et `INFRASDISCOUNT_MAIN_VERSION`
4. Création des ExtraFields `specialtype` (int) sur `propaldet`, `commandedet`, `facturedet`
5. Activation de `INVOICE_KEEP_DISCOUNT_LINES_AS_IN_ORIGIN = 1`
**`remove()`** effectue :
1. Sauvegarde des paramètres (`infrasdiscount_bkup_module`)
2. Suppression des constantes `INFRASDISCOUNT_%` de l'entité courante
### Cas d'usage courants (Common use cases)
#### Cas 1 : Remise en pourcentage sur un devis
1. Créer un devis avec des lignes produits et/ou services
2. Cliquer sur le bouton « Remise » (ajouté par `addMoreActionsButtons`)
3. Sélectionner « Pourcentage », saisir 10%, choisir « Produits et Services »
4. La remise est ajoutée en bas du document (10% du total)
5. Ajouter une ligne → le trigger recalcule automatiquement la remise
#### Cas 2 : Remise valeur cible TTC sur une commande
1. Créer une commande avec plusieurs lignes à différents taux de TVA
2. Cliquer sur « Remise » → sélectionner « Valeur cible TTC »
3. Saisir le montant TTC souhaité (ex. 5 000,00 €)
4. Le module calcule automatiquement les remises HT par groupe type/TVA
5. Correction intelligente des arrondis pour garantir le TTC exact
#### Cas 3 : Modifier une remise existante
1. Cliquer sur « Modifier remise » (bouton visible uniquement si des remises existent)
2. Le popup affiche toutes les remises avec leurs valeurs actuelles
3. Saisir les nouvelles valeurs (%, montant, ou montant en devise étrangère)
4. Les lignes prorata sont combinées en une seule entrée de modification
