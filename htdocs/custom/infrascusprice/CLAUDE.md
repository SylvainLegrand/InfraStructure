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
- Compatibilité Dolibarr : `18.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `18.1.5` (2026-06)
- Dépendance obligatoire : aucune (extension PHP `xml` requise)
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

1. Chargement SQL (`_load_tables('/infrascusprice/sql/')`)
2. Restauration des constantes module (`infrascusp_restore_module`)
3. Activation de la substitution de page : `INFRASCUSP_PS_ACTIVE_SOCIETE_PRICE = 1`
4. Activation de la fonctionnalité prix clients Dolibarr : `PRODUIT_CUSTOMER_PRICES = 1`
5. Initialisation de constantes clés :
	 - `INFRASCUSPRICE_DOL_VERSION`
	 - `INFRASCUSPRICE_MAIN_VERSION`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue :

- sauvegarde module (`infrascusp_bkup_module`),
- suppression des constantes `INFRASCUSP_%` de l'entité courante.

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

La classe `Actionsinfrascusprice` (dans `class/actions_infrascusprice.class.php`) intervient principalement sur :

- **Substitution de pages** (`updateSession`, `afterLogin`) : redirection automatique vers les pages de substitution selon version Dolibarr et constantes de configuration,
- **Enrichissement onglet prix clients** (`thirdpartycustomerprice`) :
  - `addMoreActionsButtons` : injection des boutons « Supprimer les prix » et « Déployer les prix parent »,
  - `doActions` : traitement des actions `deleteCustPrices` et `updateCustPrices`,
- **Vérification de version** (`afterLogin`) : affiche un avertissement de compatibilité si la version majeure de Dolibarr > version max supportée (comparaison sur le numéro de branche majeur uniquement via `explode()`).

## Trigger (Trigger behavior)

Le module ne possède pas de trigger. Aucun événement n'est écouté (pas de répertoire `core/triggers/` avec trigger actif).

## Données / SQL (Data model)

Le module ne crée aucune table propre. Il opère sur les tables Dolibarr existantes :

- `llx_product_customer_price` (via la classe `Productcustomerprice`)
- `llx_const` (constantes de configuration)

## Constantes de configuration (Key settings)

Constantes système utilisées par le module :

| Constante | Type | Description |
|-----------|------|-------------|
| `INFRASCUSP_DOL_VERSION` | string | Version Dolibarr au moment de l'activation du module |
| `INFRASCUSP_PS_ACTIVE_SOCIETE_PRICE` | int | Active la substitution de `societe/price.php` (1=actif) |
| `PRODUIT_CUSTOMER_PRICES` | int | Active la fonctionnalité prix clients Dolibarr (1=actif, activée automatiquement par le module) |
| `INFRASCUSPRICE_DOL_VERSION` | string | Version Dolibarr enregistrée lors de l'initialisation |
| `INFRASCUSPRICE_MAIN_VERSION` | string | Version du module enregistrée lors de l'initialisation |
| `INFRASCUSPRICE_DISABLE_CHECK_VERSION_MIN` | bool | Désactive le contrôle de version minimum Dolibarr |
| `INFRASCUSPRICE_DISABLE_CHECK_VERSION_MAX` | bool | Désactive l'avertissement de version max Dolibarr |
| `INFRAS_PHP_EXT_XML` | int | État de l'extension PHP XML (1=ok, -1=absente) |
| `DOLINFRAS_VERSION` | string | Version de Dolibarr lue depuis le fichier `VERSION` (branding dynamique) |

Point de vigilance : la constante `PRODUIT_CUSTOMER_PRICES` est une constante core Dolibarr qui est automatiquement activée par le module (le module ne peut pas fonctionner sans cette option).

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
- Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis
- La constante `PRODUIT_CUSTOMER_PRICES` est essentielle et activée automatiquement par le module

## Dernières mises à jour (Recent updates)

Voir `docs/changelog.xml` pour l'historique complet des versions.

## Notes techniques (Technical notes)

### Substitution vs Hooks

Contrairement à la plupart des modules qui utilisent les hooks, InfraSCusPrice repose sur la **substitution de pages** :
- **Page substituée** : `societe/price.php` (onglet prix clients)
- **Constante d'activation** : `INFRASCUSP_PS_ACTIVE_SOCIETE_PRICE`
- **Branches maintenues** : `dlb180x`, `dlb190x`, `dlb200x`, `dlb210x`, `dlb220x` (5 variantes)
- **Avantages** : contrôle total du comportement de la page, possibilité de modifier n'importe quel aspect de la page d'origine
- **Inconvénients** : nécessite de maintenir des fichiers séparés pour chaque version majeure de Dolibarr ; toute évolution significative de la page core impose une mise à jour
- **Stratégie** : le module maintient des copies spécifiques uniquement par version majeure (18.x, 19.x, 20.x, 21.x, 22.x)

### Mécanisme de mise à jour des prix (Price update mechanism)

Le module exploite la fonctionnalité de cascade intégrée à `Productcustomerprice::update()` :
```php
// Dans infrascusp_actions() lorsque action='update'
$prodcustpriceline->update($user, 0, 1);
// Paramètres :
// - $user : utilisateur courant (pour le log)
// - 0 : pas de mise à jour par niveaux de prix
// - 1 : mettre à jour les filiales (cascade vers les sociétés enfants)
```

Le troisième paramètre déclenche la logique interne de Dolibarr pour copier le prix vers toutes les filiales liées via le champ `parent`.

### Flux de redirection (Redirect flow)

**Depuis la version 18.1.3**, le flux de redirection utilise `infrascusp_getSubstitutionRedirectUrl()` pour centraliser la logique :

```
L'utilisateur accède à /societe/price.php
    ↓
Le hook updateSession() ou afterLogin() s'exécute
    ↓
infrascusp_getSubstitutionRedirectUrl() :
    → infrascusp_is_substitution_page() vérifie via strpos() si on est
      déjà sur une page substituée (prévention de boucle)
    → infrascusp_get_substitution_url() génère l'URL substituée :
      • Vérifie la constante INFRASCUSP_PS_ACTIVE_SOCIETE_PRICE
      • Construit le chemin : /infrascusprice/substitutionpages/dlb{major}0x/societe/price.php
      • Vérifie l'existence physique du fichier via dol_buildpath()
    → Filtre les paramètres GET : exclusion du token CSRF (page-specific)
    → Retourne l'URL complète avec query string filtrée
    ↓
Redirection header('Location: ...') → exit
```

**Amélioration de sécurité** : la fonction exclut automatiquement les paramètres POST (pouvant contenir des credentials) et le token CSRF qui est spécifique à la page d'origine.

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="18.1.5" MonthVersion="2026-06">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
    </Version>
    <InfraS Downloaded="20260619"/>
    <Dolibarr minVersion="18.0.0" maxVersion="24.x.x"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

La fonction `infrascusp_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "18.1.5",           // Version courante du module
    1 => "18.0.0",           // Version min Dolibarr
    2 => "20260301",         // Date de téléchargement InfraS
    3 => "",                 // Erreur (vide si ok)
    4 => "24.x.x",           // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

### Ajout du support d'une nouvelle version Dolibarr (Adding support for new Dolibarr versions)

Pour supporter une nouvelle version majeure de Dolibarr (ex. 25.x) :

1. **Créer le répertoire** : `substitutionpages/dlb250x/societe/`
2. **Copier la page de la version précédente** : `cp substitutionpages/dlb240x/societe/price.php substitutionpages/dlb250x/societe/`
3. **Comparer avec le core Dolibarr** : `diff htdocs/societe/price.php substitutionpages/dlb250x/societe/price.php`
4. **Adapter les évolutions** : fusionner les changements du core Dolibarr v25 (nouveaux champs, méthodes, logique métier)
5. **Mettre à jour le changelog** :
   ```xml
   <Version Number="X.Y.Z" MonthVersion="YYYY-MM">
       <change type='add'>Compatibilité avec Dolibarr v25</change>
   </Version>
   <Dolibarr minVersion="18.0.0" maxVersion="25.x.x"/>
   ```
6. **Tester** :
   - Vérifier la redirection automatique vers la page substituée
   - Tester le bouton « Déployer les prix parent » sur une filiale
   - Tester le bouton « Supprimer les prix » sur une filiale
   - Vérifier que les recherches de prix fonctionnent (champs `search_price`, `search_price_ttc`)

## Cas d'usage courants (Common use cases)

### Cas 1 : Mise en place initiale pour un groupe de sociétés

1. Créer la société mère (définie comme « N'est pas une filiale »)
2. Configurer les prix clients spécifiques pour la société mère
3. Créer les filiales (renseigner le champ `parent` vers la société mère)
4. Accéder à la filiale → onglet Prix clients
5. Cliquer sur le bouton « Déployer les prix parent »
6. Tous les prix de la société mère sont copiés vers la filiale

### Cas 2 : Détachement d'une filiale du groupe

1. Accéder à la filiale → onglet Prix clients
2. Cliquer sur le bouton « Supprimer les prix » (supprime tous les prix clients)
3. Modifier la fiche Tiers de la filiale
4. Vider le champ `parent` (détacher du groupe)
5. Configurer une tarification indépendante

### Cas 3 : Ajout d'un nouveau produit au groupe

1. Ajouter le produit aux prix clients de la société mère
2. Accéder à chaque filiale → onglet Prix clients
3. Cliquer sur « Déployer les prix parent » (met à jour les existants + ajoute les nouveaux)
4. Ou configurer `update_child_soc` dans le core pour cascader automatiquement à la création d'un prix parent
