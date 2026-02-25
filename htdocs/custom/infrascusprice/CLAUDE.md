# CLAUDE.md — Contexte module infrascusprice

## Aperçu (Overview)

`infrascusprice` est un module externe Dolibarr de gestion des prix clients par groupe de sociétés :

- déploiement automatique des tarifs clients d'une société mère vers ses filiales,
- suppression en masse des tarifs clients d'un tiers,
- boutons d'action intégrés sur l'onglet prix clients,
- mécanisme de substitution de pages selon version Dolibarr.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS
- Numéro module : `500077`
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `22.0.2`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `18.1.0` (2026-02)
- Emplacement : `htdocs/custom/infrascusprice/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrascusprice/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrascuspricesetup.php
├── class/
│   └── actions_infrascusprice.class.php
├── config.php
├── core/
│   ├── lib/
│   │   ├── infrascusprice.lib.php
│   │   └── infrascuspriceAdmin.lib.php
│   └── modules/
│       └── modinfrascusprice.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrascusprice.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── langs/
│   ├── en_US/infrascusprice.lang
│   ├── es_ES/infrascusprice.lang
│   └── fr_FR/infrascusprice.lang
└── substitutionpages/
    ├── dlb180x/societe/price.php
    ├── dlb190x/societe/price.php
    ├── dlb200x/societe/price.php
    ├── dlb210x/societe/price.php
    └── dlb220x/societe/price.php
```

## Descripteur module (Module descriptor : `modinfrascusprice`)

Dans `core/modules/modinfrascusprice.class.php` :

- **Module parts** :
	- hooks : `thirdpartycustomerprice`, `all`
	- CSS : `/infrascusprice/css/infrascusprice.css.php`
- **Dépendances** : aucune dépendance obligatoire
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : 3 permissions
	- `paramMenu` (par défaut : activée)
	- `paramInfraSCusPrice`
	- `paramUse`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Activation de la substitution de page : `INFRASCUSP_PS_ACTIVE_SOCIETE_PRICE = 1`
2. Activation de la fonctionnalité prix clients Dolibarr : `PRODUIT_CUSTOMER_PRICES = 1`
3. Chargement SQL via `_init()`

### Désactivation (Lifecycle : `remove()`)

`remove()` supprime les constantes `INFRASCUSP_%` de l'entité courante.

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `actions_infrascusprice.class.php` pour les hooks de redirection et d'actions prix,
- `infrascusprice.lib.php` pour la détection de substitution, la génération d'URL et les opérations prix,
- `infrascuspriceAdmin.lib.php` pour l'administration (onglets, versions, changelog, helpers UI),
- les pages de substitution `substitutionpages/dlb<VER>x/societe/price.php` pour le remplacement de la page prix clients selon la version Dolibarr.

### Mécanisme de substitution de pages

1. Les hooks `updateSession` et `afterLogin` détectent l'accès à `/societe/price.php`
2. Le module redirige vers la page substituée correspondant à la version Dolibarr majeure (ex. `dlb220x` pour v22.x)
3. Chaque chemin de substitution possède une constante d'activation (ex. `INFRASCUSP_PS_ACTIVE_SOCIETE_PRICE`)
4. Lors d'une montée de version Dolibarr, le module utilise automatiquement le dossier approprié

### Fonctionnalités clés

**Déploiement des prix parent vers filiales** (`updateCustPrices`) :
- Récupère tous les `Productcustomerprice` de la société mère
- Appelle `update($user, 0, 1)` sur chaque enregistrement (paramètre `1` = cascade aux filiales)

**Suppression des prix** (`deleteCustPrices`) :
- Supprime tous les `Productcustomerprice` de la filiale via `delete($user)`

## Hooks et comportement (Hook behavior)

La classe `Actionsinfrascusprice` intervient sur :

- `updateSession` : redirige vers la page de substitution avant chargement si la substitution est active,
- `afterLogin` : affiche un avertissement de compatibilité si Dolibarr > version max supportée, gère les redirections post-login,
- `addMoreActionsButtons` (contexte `thirdpartycustomerprice`) : ajoute les boutons « Supprimer les prix » et « Déployer les prix parent »,
- `doActions` (contexte `thirdpartycustomerprice`) : traite les actions `deleteCustPrices` et `updateCustPrices`.

## Données / SQL (Data model)

Le module ne crée aucune table propre. Il opère sur les tables Dolibarr existantes :

- `llx_product_customer_price` (via la classe `Productcustomerprice`)
- `llx_const` (constantes de configuration)

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `INFRASCUSP_DOL_VERSION` — version Dolibarr au moment de l'activation
- `INFRASCUSP_PS_ACTIVE_SOCIETE_PRICE` — active la substitution de `societe/price.php`
- `PRODUIT_CUSTOMER_PRICES` — active la fonctionnalité prix clients Dolibarr
- `INFRASCUSPRICE_DISABLE_CHECK_VERSION_MIN` — désactive le contrôle de version minimum
- `INFRASCUSPRICE_DISABLE_CHECK_VERSION_MAX` — désactive l'avertissement de version max

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification du descripteur / permissions / hooks / constantes :

1. Désactiver puis réactiver le module
2. Vérifier les constantes `INFRASCUSP_*` et `PRODUIT_CUSTOMER_PRICES`
3. Vérifier la redirection de substitution sur `/societe/price.php`
4. Tester les boutons d'actions sur une filiale (déploiement + suppression prix)
5. Vérifier la compatibilité de la page substituée avec la version Dolibarr courante

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrascusp_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module déclenche un avertissement si la version Dolibarr dépasse la version max supportée
- Les pages de substitution sont des copies adaptées du core Dolibarr ; toute montée de version Dolibarr peut nécessiter une mise à jour de ces pages
- Le menu « Paramètres spécifique InfraS » pointe vers `infrassearchsetup.php` (ligne 171 du descripteur) — lien potentiellement incorrect

## Dernières mises à jour (Recent updates)

- `18.1.0` (2026-02) : durcissements sécurité — échappement `$_SERVER['PHP_SELF']` sur pages admin et boutons du hook
- `18.1.0` (2026-02) : correction injection SQL dans le descripteur module (cast entity en entier)
- `18.1.0` (2026-02) : suppression d'un `error_log` de debug laissé en production
- `18.1.0` (2026-02) : typage `GETPOST(..., 'alpha')` sur les champs de recherche prix des pages de substitution
- `18.1.0` (2026-02) : échappement XSS des valeurs de recherche et encodage URL dans les pages de substitution
- `18.1.0` (2026-02) : nouveau lien Wiki InfraSDiscount, amélioration CSS, ajout documentation CLAUDE.md
- Per-version changelog entries (type: `add`, `chg`, `fix`)

The module auto-disables if Dolibarr version is below the minimum required. A warning is shown on login if Dolibarr exceeds the maximum supported version.

## Technical Notes

### Substitution vs Hooks

Unlike most modules that use hooks, InfraSCusPrice uses **page substitution**:
- **Advantages**: Full control over page behavior, can modify any aspect of the original page
- **Disadvantages**: Must maintain separate files for each Dolibarr version, requires updates when core page changes significantly
- **Strategy**: Module maintains version-specific copies only for major Dolibarr releases (18.x, 19.x, 20.x, 21.x, 22.x)

### Price Update Mechanism

The module leverages Dolibarr's built-in `Productcustomerprice::update()` cascade feature:
```php
// In infrascusp_actions() when action='update'
$prodcustpriceline->update($user, 0, 1);
// Parameters:
// - $user: Current user (for log)
// - 0: Not a price level update
// - 1: Update child companies (cascades to subsidiaries)
```

The third parameter triggers Dolibarr's internal logic to copy the price to all subsidiaries linked via the `parent` field.

### Redirect Flow

```
User accesses /societe/price.php
    ↓
Hook updateSession() or afterLogin() executes
    ↓
infrascusp_is_substitution_page() checks if already on substitute (prevent loop)
    ↓
infrascusp_get_substitution_url() generates versioned substitute URL
    ↓
Check if constant INFRASCUSP_PS_ACTIVE_SOCIETE_PRICE = 1
    ↓
Get Dolibarr major version (e.g., 22) → 'dlb220x'
    ↓
Build path: /infrascusprice/substitutionpages/dlb220x/societe/price.php
    ↓
Verify file exists with dol_buildpath()
    ↓
header('Location: ...') redirect with query params preserved
```

### Changelog Structure

```xml
<changelog>
    <Version Number="18.0.8" MonthVersion="2025-08">
        <change type='fix'>Correction du lien vers le Dolistore</change>
    </Version>
    <InfraS Downloaded="20250801"/>
    <Dolibarr minVersion="18.0.0" maxVersion="22.0.2"/>
    <PHP minVersion="7.4" maxVersion="8.2"/>
</changelog>
```

The `infrascusp_getLocalVersionMinDoli()` function parses this XML and returns an array:
```php
[
    0 => "18.0.8",           // Current version
    1 => "18.0.0",           // Min Dolibarr
    2 => "20250801",         // Downloaded date
    3 => "",                 // (unused)
    4 => "22.0.2",           // Max Dolibarr
    5 => "7.4",              // Min PHP
    6 => "8.2"               // Max PHP
]
```

### Adding Support for New Dolibarr Versions

To support a new Dolibarr major version (e.g., 23.x):

1. Create new directory: `substitutionpages/dlb230x/`
2. Copy latest version folder contents: `cp -r dlb220x/* dlb230x/`
3. Review and update core page changes from upstream Dolibarr
4. Update `docs/changelog.xml`:
   ```xml
   <Version Number="X.Y.Z" MonthVersion="YYYY-MM">
       <change type='add'>Compatibilité avec Dolibarr v23</change>
   </Version>
   <Dolibarr minVersion="18.0.0" maxVersion="23.0.x"/>
   ```
5. Test substitution page redirection and button functionality

## Common Use Cases

### Use Case 1: Initial Setup for Corporate Group

1. Create parent company (set as "Not a subsidiary")
2. Configure customer-specific prices for parent
3. Create subsidiary companies (set `parent` field to parent company)
4. Navigate to subsidiary → Customer prices tab
5. Click "Deploy parent prices" button
6. All parent prices are copied to subsidiary

### Use Case 2: Detaching Subsidiary from Group

1. Navigate to subsidiary → Customer prices tab
2. Click "Delete prices" button (removes all customer prices)
3. Edit subsidiary Third Party card
4. Clear `parent` field (detach from group)
5. Configure new independent pricing

### Use Case 3: Adding New Product to Group

1. Add product to parent company's customer prices
2. Visit each subsidiary → Customer prices tab
3. Click "Deploy parent prices" (updates existing + adds new)
4. Or configure `update_child_soc` in core to auto-cascade on parent price creation
