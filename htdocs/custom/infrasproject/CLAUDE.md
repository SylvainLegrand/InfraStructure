# CLAUDE.md — Contexte module infrasproject

## Aperçu (Overview)

`infrasproject` est un module externe Dolibarr orienté gestion avancée de projets :

- consommation de stock sur projets,
- calcul de marge provisionnelle (revenus, coûts, droits de dépense),
- répartition de lignes de factures fournisseur sur plusieurs projets,
- substitution de pages Dolibarr par version,
- comportements automatiques (création/validation de projet).

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS
- Numéro module : `500055`
- Licence : GPL v3+
- Compatibilité Dolibarr : `21.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `21.1.4` (2026-06)
- Dépendances obligatoires : `modProjet`, `modStock`
- Emplacement : `htdocs/custom/infrasproject/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrasproject/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   ├── generalproject.php
│   └── infrasprojectsetup.php
├── class/
│   ├── actions_infrasproject.class.php
│   ├── html.forminfrasproject.class.php
│   ├── infrasproject.class.php
│   └── infrasprojectsupplierinvoiceline.class.php
├── config.php
├── core/
│   ├── lib/
│   │   ├── infrasproject.lib.php
│   │   └── infrasprojectAdmin.lib.php
│   ├── modules/
│   │   └── modinfrasproject.class.php
│   ├── tpl/
│   │   ├── objectline_create.tpl.php   (dispatcher v21-v24, route vers linecreates/v*.tpl.php)
│   │   ├── lineviews/                  (mode view, chargé par hook printObjectLine)
│   │   │   ├── v21.tpl.php
│   │   │   ├── v22.tpl.php
│   │   │   ├── v22-DolInfraS.tpl.php
│   │   │   ├── v23.tpl.php
│   │   │   ├── v24.tpl.php
│   │   │   └── _columns/refproject.tpl.php
│   │   ├── linetitles/                 (mode title, chargé par hook printObjectLineTitle)
│   │   │   ├── v21.tpl.php ... v24.tpl.php
│   │   │   └── _columns/refproject.tpl.php
│   │   ├── lineedits/                  (mode edit, chargé par hook printObjectLine action=editline)
│   │   │   ├── v21.tpl.php ... v24.tpl.php
│   │   │   └── _columns/refproject.tpl.php
│   │   └── linecreates/                (mode create, chargé par hook formAddObjectLine via dispatcher objectline_create.tpl.php)
│   │       ├── v21.tpl.php ... v24.tpl.php
│   │       └── _columns/refproject_header.tpl.php + refproject_input.tpl.php
│   └── triggers/
│       └── interface_98_modinfrasproject_infrasprojecttrigger.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrasproject.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── infrasproject_tab.php
├── langs/
│   ├── en_US/infrasproject.lang
│   ├── es_ES/infrasproject.lang
│   └── fr_FR/infrasproject.lang
├── sql/
│   ├── data.sql
│   └── update.sql
└── substitutionpages/
    ├── dlb210x/
    ├── dlb220x/
    ├── dlb220x-DolInfraS/
    ├── dlb230x/
    └── dlb240x/
```

## Descripteur module (Module descriptor : `modinfrasproject`)

Dans `core/modules/modinfrasproject.class.php` :

- **Module parts** :
	- `triggers` (tous les modes de templates de ligne — view, title, edit, create — sont désormais routés via hooks ; aucune déclaration `tpl` dans `module_parts`)
	- hooks : `main`, `login`, `projectOverview`, `projectcard`, `invoicesuppliercard`, `globalcard`
	- CSS : `/infrasproject/css/infrasproject.css.php`
- **Dépendances** : `modProjet`, `modStock`
- **Onglet additionnel** : onglet « Consommation Stock » sur la fiche projet
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : 6 permissions
	- `paramMenu`, `paramDolibarr`, `paramSetup`, `paramBkpRest`
	- `readproject`, `writeproject`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrasproject/sql/')`)
2. Restauration des constantes module (`infrasproject_restore_module`)
3. Initialisation de constantes clés :
	 - `INFRASPROJECT_DOL_VERSION`
	 - `INFRASPROJECT_MAIN_VERSION`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue sauvegarde module (`infrasproject_bkup_module`), nettoyage des constantes `INFRASPROJECT_%` de l'entité courante.

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `actions_infrasproject.class.php` pour les hooks de substitution de pages et d'enrichissement de l'overview projet,
- `infrasproject.class.php` (étend `Project`) pour la consommation stock (`correct_stock()`) et le calcul de marge (`calcul_marge_fourn()`),
- `infrasprojectsupplierinvoiceline.class.php` pour la répartition multi-projets de lignes de factures fournisseur,
- `html.forminfrasproject.class.php` pour les formulaires de consommation stock (sélection entrepôt, lot/série),
- `infrasproject.lib.php` pour la logique transverse (substitution de pages, calcul de profit, intégration ContactTracking),
- `infrasprojectAdmin.lib.php` pour l'administration (onglets, changelog, backup/restore, vérification de mise à jour),
- le trigger `interface_98_modinfrasproject_infrasprojecttrigger.class.php` (création automatique de projet à la signature de devis),
- `infrasproject_tab.php` pour l'onglet de consommation stock sur la fiche projet,
- les templates `core/tpl/` versionnés par version Dolibarr (21, 22, 22-DolInfraS, 23, 24).

## Hooks et comportement (Hook behavior)

La classe `ActionsInfrasproject` (dans `class/actions_infrasproject.class.php`) gère principalement :

- **Substitution de pages** (`afterLogin`, `updateSession`) : redirection automatique vers les pages de substitution selon version Dolibarr et constantes de configuration,
- **Enrichissement overview projet** (`projectOverview`) : injection du tableau de marge provisionnelle et des informations ContactTracking via les hooks `printFieldListOption1/2` et `printFieldListValue1/2`,
- **Onglet consommation stock** (`projectcard`) : ajout de l'onglet « Consommation Stock » sur la fiche projet, personnalisation de l'affichage,
- **Répartition multi-projets** (`invoicesuppliercard`) : ventilation des lignes de factures fournisseur par projet.

## Trigger (Trigger behavior)

Le trigger `interface_98_modinfrasproject_infrasprojecttrigger` (dans `core/triggers/`) écoute les événements suivants :

| Événement | Condition | Action |
|-----------|-----------|--------|
| `PROPAL_CLOSE_SIGNED` | `INFRASPROJECT_PROJECT_ON_SIGN_PROPAL` activé | Création automatique d'un projet au titre du devis avec validation optionnelle (`INFRASPROJECT_VALIDATE_PROJECT_ON_CREATE`) |
| `LINEBILL_SUPPLIER_CREATE` | Toujours | Ventile la ligne de facture fournisseur par projet via `supplierInvoiceLineProject()` |
| `LINEBILL_SUPPLIER_MODIFY` | Toujours | Met à jour la ventilation projet de la ligne de facture fournisseur |

**Comportement création projet** (`PROPAL_CLOSE_SIGNED`) :
1. Vérifie l'activation de `INFRASPROJECT_PROJECT_ON_SIGN_PROPAL`
2. Crée un projet avec le titre du devis (`Propal::title`)
3. Associe le tiers (`fk_soc`) et la date (`datep`)
4. Copie les extrafields du devis vers le projet
5. Valide automatiquement le projet si `INFRASPROJECT_VALIDATE_PROJECT_ON_CREATE` actif
6. Lie le devis au projet créé

**Comportement ventilation facture fournisseur** (`LINEBILL_SUPPLIER_*`) :
1. Charge la facture fournisseur parent
2. Lit le projet sélectionné au niveau de la ligne
3. Met à jour `facture_fourn_det` avec `entity`, `fk_soc`, `fk_projet` de la ligne
4. Désassocie le projet global de la facture pour éviter les doublons dans les calculs

## Données / SQL (Data model)

Pas de tables personnalisées — utilise les mouvements de stock Dolibarr (`llx_stock_mouvement`) avec des codes inventaire personnalisés.

Les mouvements sont identifiés par :

- préfixe de code inventaire (`INFRASPROJECT_CODE_MVT_STOCK`),
- mode de recherche (`INFRASPROJECT_SEARCH_MODE_STOCK` : 0=réf projet, 1=préfixe, 2=mixte),
- libellé contenant la référence du projet.

Fichiers SQL :

- `data.sql` (constantes module),
- `update.sql` (évolutions).

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- **Consommation stock** : `INFRASPROJECT_CODE_MVT_STOCK`, `INFRASPROJECT_SEARCH_MODE_STOCK`, `INFRASPROJECT_ADD_STOCK_CONSUMPTION`, `INFRASPROJECT_DEFAULT_WAREHOUSE`, `INFRASPROJECT_LINK_TO_USER`, `INFRASPROJECT_CATEG_PRODUCT_CONSUMPTION`, `INFRASPROJECT_DATE_MVT_STOCK`
- **Marge provisionnelle** : `INFRASPROJECT_SHOW_PROVISIONAL_PROFIT`, `INFRASPROJECT_PROVISIONAL_PROFIT_FIRST`, `INFRASPROJECT_ELEMENTS_PROFIT_ADDITION`, `INFRASPROJECT_ELEMENTS_PROFIT_DEDUCTION`, `INFRASPROJECT_UNLINKED_SUPPLIER_INVOICE`, `INFRASPROJECT_EXCLUDED_TYPES_NDFP`
- **Taux de marque** : `INFRASPROJECT_TAUX_MARQUE_PRODUCT`, `INFRASPROJECT_TAUX_MARQUE_SERVICE`, `INFRASPROJECT_TAUX_MARQUE_MIXTE`
- **Masquage de listes** : `INFRASPROJECT_HIDE_EMPTY_LISTS`, `INFRASPROJECT_HIDE_ORDER_LIST`, `INFRASPROJECT_HIDE_INVOICE_TEMPLATE_LIST`, `INFRASPROJECT_HIDE_SUPPLIER_PROPOSAL_LIST`, `INFRASPROJECT_HIDE_SHIPMENT_LIST`, `INFRASPROJECT_HIDE_TIME_SPENT_LIST`
- **Comportements automatiques** : `INFRASPROJECT_PROJECT_ON_SIGN_PROPAL`, `INFRASPROJECT_VALIDATE_PROJECT_ON_CREATE`, `INFRASPROJECT_PROJECT_OPEN_TAB`
- **ContactTracking** : `INFRASPROJECT_CT_LAST_EXCHANGE`, `INFRASPROJECT_CT_NEXT_ACTION`
- **Substitution de pages** : `INFRASPROJECT_SUBSTITUTE_PROJECT_OVERVIEW`, `INFRASPROJECT_SUBSTITUTE_PROJECT_TASKS`, etc.
- **Constantes Dolibarr masquées** (onglet Général) : `PROJECT_*` (commentaires, opportunités, tâches, etc.)
- **Constantes de versions/migrations** : `INFRASPROJECT_DOL_VERSION`, `INFRASPROJECT_MAIN_VERSION`

Point de vigilance : les constantes `INFRASPROJECT_*` sont nombreuses ; éviter les changements massifs sans test fonctionnel.

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / hooks / templates / trigger :

1. Désactiver puis réactiver le module
2. Vérifier constantes (`INFRASPROJECT_*`)
3. Vérifier l'onglet consommation stock sur une fiche projet
4. Vérifier les hooks de substitution (`afterLogin`, `projectOverview`, `projectcard`, `invoicesuppliercard`)
5. Vérifier un cas d'affichage réel de l'overview projet avec marge provisionnelle activée
6. Vérifier les templates TPL pour la version Dolibarr en cours

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrasproject_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module applique des substitutions de pages selon version Dolibarr (répertoire `substitutionpages/`)
- Chaque version Dolibarr a son propre répertoire de substitution et ses propres TPL — maintenir toutes les versions indépendamment
- Le module auto-désactive si la version Dolibarr est inférieure au minimum requis
- L'intégration ContactTracking est optionnelle et dégradée gracieusement si le module est désactivé
- **Co-activation avec InfraSPackPlus** : le hook `printObjectLine` d'infrasproject teste `isModEnabled('infraspackplus')` en mode view et retourne 0 (laisse IPP gérer). Si la stratégie est inversée par erreur (les deux modules rendent la ligne), l'affichage est dupliqué. Garder cet ordre de priorité IPP > infrasproject en mode view.
- **Co-activation avec infrastructure et autres modules à lignes spéciales** : `printObjectLine` filtre `$line->special_code > 3` et retourne 0 — ne pas tenter de rendre les lignes spéciales infrastructure (titres / sous-totaux / textes libres, special_code = 550090), infrasdiscount, etc. Sans ce filtre, ces lignes seraient rendues deux fois dans le DOM (notre tpl `lineviews/v*.tpl.php` + le tpl propriétaire du module). Cf. section *Sous-hooks d'enrichissement* pour le mécanisme propre permettant à ces modules d'ajouter des colonnes.
- **Ne pas implémenter `printObjectLineTitle` / `printObjectLine` dans un autre module** quand infrasproject est actif : préférer les sous-hooks `infrasprojectEnrichObjectLineTitle` / `infrasprojectEnrichObjectLine` qui s'agrègent proprement via `resprints`. Implémenter `printObjectLineTitle` côté tiers émet un second `<thead>` (le HookManager n'arrête pas l'exécution sur retour positif).

## Dernières mises à jour (Recent updates)

Voir `docs/changelog.xml` pour l'historique complet des versions.

## Notes techniques (Technical notes)

### Substitution de pages vs hooks

Comme InfraSPackPlus et InfraSCusPrice, InfraSProject utilise la **substitution de pages** pour remplacer certaines pages standard de Dolibarr par des versions personnalisées :

- **Pages substituables** : `projet/overview.php`, `projet/tasks.php`, `projet/tasks/list.php`, etc.
- **Constantes d'activation** : générées dynamiquement depuis le chemin (ex. `/projet/overview.php` → `INFRASPROJECT_SUBSTITUTE_PROJECT_OVERVIEW`)
- **Branches maintenues** : `dlb210x`, `dlb220x`, `dlb220x-DolInfraS`, `dlb230x`, `dlb240x` (5 variantes, Dolibarr 21–24)
- **Avantages** : contrôle total de la page, adaptation par version Dolibarr et par distribution (Dolibarr standard vs Easya)
- **Inconvénients** : maintenance d'un fichier par page et par version majeure

### Flux de redirection (Redirect flow)

```
L'utilisateur accède à une page substituable (ex. /projet/overview.php)
    ↓
Le hook afterLogin() ou updateSession() s'exécute
    ↓
infrasproject_is_substitution_page() vérifie via strpos() si on est déjà
    sur une page substituée (prévention de boucle infinie)
    ↓
Pour chaque constante INFRASPROJECT_SUBSTITUTE_* activée :
    → infrasproject_getSubstitutionRedirectUrl() génère l'URL substituée
    → Vérifie l'existence physique du fichier via dol_buildpath()
    → Construit le chemin : /infrasproject/substitutionpages/dlb{major}0x{-Easya}/
    → Filtre les paramètres GET (exclusion du token CSRF pour éviter les conflits)
    ↓
Redirection header('Location: ...') avec conservation des paramètres GET filtrés → exit
```

**Détection de version** : La redirection choisit automatiquement le répertoire de substitution selon la version Dolibarr détectée via `DOL_VERSION` (ex. `21.0.3` → `dlb210x`).

**Protection Easya** : Si la distribution Easya est détectée via présence de fichiers spécifiques, le suffixe `-Easya` est ajouté au chemin.

### Templates versionnés (Versioned TPL) — v21.0.0+

Depuis la version `21.0.0` du module, les templates de lignes sont restructurés en 4 dossiers (un par mode), avec un fichier par version Dolibarr et des partials communs sous `_columns/` :

| Dossier | Mode | Chargé par | Versions disponibles |
|---------|------|-----------|----------------------|
| `core/tpl/lineviews/` | view (ligne existante en lecture) | hook `printObjectLine` (Actionsinfrasproject) | `v21`, `v22`, `v22-DolInfraS`, `v23`, `v24` |
| `core/tpl/linetitles/` | title (en-tête du tableau) | hook `printObjectLineTitle` | idem |
| `core/tpl/lineedits/` | edit (ligne existante en édition) | hook `printObjectLine` quand `$action == 'editline'` | idem |
| `core/tpl/linecreates/` | create (formulaire ajout nouvelle ligne) | hook `formAddObjectLine` (trampoline `CommonObject::formAddObjectLine` avec `$defaulttpldir='/infrasproject/core/tpl'` → dispatcher `objectline_create.tpl.php`) | idem |

**Partials communs** : chaque dossier contient un sous-dossier `_columns/` avec les fragments réutilisables :

- `lineviews/_columns/refproject.tpl.php` — cellule `<td>` affichant le projet lié à une ligne de facture fournisseur (lecture)
- `linetitles/_columns/refproject.tpl.php` — cellule `<th>` du header
- `lineedits/_columns/refproject.tpl.php` — sélecteur projet (édition)
- `linecreates/_columns/refproject_header.tpl.php` — cellule header du mini titre du create form
- `linecreates/_columns/refproject_input.tpl.php` — cellule sélecteur projet du create form

**Routage** (`actions_infrasproject.class.php::infrasproject_pickLineTpl()`) :

```php
$major = (int) DOL_VERSION;
$dolinfras = getDolGlobalString('EASYA_VERSION', '') || getDolGlobalString('DOLINFRAS_VERSION', '');
if ($major >= 24) $tplname = 'v24.tpl.php';
elseif ($major == 23) $tplname = 'v23.tpl.php';
elseif ($major == 22) $tplname = $dolinfras ? 'v22-DolInfraS.tpl.php' : 'v22.tpl.php';
elseif ($major == 21) $tplname = 'v21.tpl.php';
else return ''; // Dolibarr < 21 plus supporté
```

**Compatibilité InfraSPackPlus** : si `infraspackplus` est activé en même temps qu'infrasproject, le hook `printObjectLine` d'infrasproject **laisse la main à infraspackplus** en mode view (return 0). InfraSPackPlus inclut déjà la colonne « Projet » via son propre partial `core/tpl/lineviews/_columns/refproject.tpl.php` qui appelle `infrasproject_printprj()`. Les modes title/edit/create restent gérés par infrasproject puisque InfraSPackPlus ne les surcharge pas (IPP n'implémente ni `printObjectLineTitle`, ni `formAddObjectLine`, ni la branche `editline` de `printObjectLine`).

### Hooks — récapitulatif des comportements

| Hook | Contexte | Retour | Rôle |
|------|----------|--------|------|
| `afterLogin` | `login` | 0 | Redirection initiale vers pages de substitution |
| `updateSession` | `main` | 0 | Redirection continue vers pages de substitution |
| `completeTabsHead` | `projectcard` | 0 | Ajout de l'onglet « Consommation Stock » avec badge de compteur |
| `printFieldListOption1/2` | `projectOverview` | 0 | Injection des en-têtes du tableau de marge provisionnelle |
| `printFieldListValue1/2` | `projectOverview` | 0 | Injection des valeurs (CA, marge HT/TTC, taux) dans l'overview |
| `completeListOfReferent` | `projectOverview` | 0 | Ajout de `invoice_supplier_det` (lignes factures fournisseur) dans les éléments liés |
| `formObjectOptions` | `invoicesuppliercard` | 0 | Affichage du sélecteur de projet par ligne de facture fournisseur |
| `printObjectLine` | `globalcard` | 0 ou 1 | Surcharge du rendu d'une ligne (mode view ou edit). Retourne 1 et inclut `lineviews/v{XX}.tpl.php` ou `lineedits/v{XX}.tpl.php`. Retourne 0 quand infraspackplus est actif en mode view (laisse IPP gérer), ou quand la ligne a `special_code > 3` (ligne d'un autre module — infrastructure, infrasdiscount, etc.). Depuis 21.1.0 : capture du rendu via `ob_start` puis exécution du sous-hook `infrasprojectEnrichObjectLine` pour permettre à d'autres modules d'enrichir la ligne (cf. *Sous-hooks d'enrichissement* dans les notes techniques). |
| `printObjectLineTitle` | `globalcard` | 1 | Surcharge du rendu du header de tableau de lignes. Inclut `linetitles/v{XX}.tpl.php`. Depuis 21.1.0 : capture du rendu via `ob_start` puis exécution du sous-hook `infrasprojectEnrichObjectLineTitle`. |
| `formAddObjectLine` | `globalcard` | 0 ou 1 | Surcharge du formulaire d'ajout de ligne (mode create). Reconstruit `seller`/`buyer` selon le type d'objet (achat vs vente) puis trampoline vers `CommonObject::formAddObjectLine()` avec `$defaulttpldir='/infrasproject/core/tpl'` qui charge le dispatcher → `linecreates/v{XX}.tpl.php`. Retourne 0 si la version Dolibarr n'a pas de tpl versionné (fallback natif). |

### Sous-hooks d'enrichissement — colonnes additionnelles dans le tableau de lignes (depuis 21.1.0)

Pour permettre à d'autres modules d'ajouter des colonnes au tableau de lignes sans dupliquer le `<thead>` / `<tr>` (le HookManager Dolibarr ne s'arrête pas au 1er retour positif d'un hook `printObjectLineTitle` / `printObjectLine` — chaque module qui implémente ces hooks émet son propre rendu, ce qui produit des doublons quand plusieurs modules surchargent le même hook), InfraSProject expose deux sous-hooks d'enrichissement appelés depuis ses propres méthodes après capture du rendu via `ob_start`.

**InfraSPackPlus partage la convention** : depuis InfraSPackPlus `21.1.0`, son `printObjectLine` applique le même pattern et appelle le sous-hook `infrasprojectEnrichObjectLine` (nom historique conservé pour interopérabilité). Cela résout le cas critique : InfraSProject cède la main à InfraSPackPlus en mode view via `if ($isView && isModEnabled('infraspackplus')) return 0;` — sans le pattern partagé, les modules tiers (ex. infrastructure pour la colonne « Opt ») perdent leur enrichissement dès qu'InfraSPackPlus est actif.

**Hooks exposés** :

| Sous-hook | Appelé par | Rôle |
|-----------|------------|------|
| `infrasprojectEnrichObjectLineTitle` | `printObjectLineTitle` après include `linetitles/v{XX}.tpl.php` | Les modules tiers retournent une cellule `<th>...</th>` via `$this->resprints`. Les contributions sont agrégées par le HookManager dans `$hookmanager->resPrint`, puis InfraSProject les injecte avant `<th class="linecolmove">` via `preg_replace`. |
| `infrasprojectEnrichObjectLine` | `printObjectLine` après include `lineviews/v{XX}.tpl.php` ou `lineedits/v{XX}.tpl.php` | Idem pour les cellules `<td>...</td>`, injectées avant `<td class="linecolmove">`. |

**Pattern d'implémentation côté module tiers** :

```php
public function infrasprojectEnrichObjectLineTitle($parameters, &$object, &$action, HookManager $hookmanager)
{
    // 1. Vérifier que l'enrichissement s'applique (contextes, statut, constantes, etc.)
    if (! $this->shouldEnrich($object, $action, $parameters)) {
        return 0;
    }
    // 2. Produire le HTML de la cellule
    ob_start();
    include dol_buildpath('/monmodule/core/tpl/maboitepartial.tpl.php', 0);
    $this->resprints = ob_get_clean();
    // 3. Retour 0 : non bloquant, l'enrichissement n'empêche pas d'autres modules de contribuer
    return 0;
}
```

**Premier consommateur** : le module `infrastructure` (depuis sa version 21.0.1) implémente les deux sous-hooks pour injecter la colonne « Opt » (`<th class="infrastructure_ol">Opt</th>` + `<td class="infrastructure_ol"><input checkbox></td>`) quand `INFRASTRUCTURE_MANAGE_OL` est actif sur un document en brouillon. La cellule sur les lignes spéciales `infrastructure` (titres / sous-totaux / textes libres) est rendue directement par le tpl `infrastructureline_row_document.tpl.php` du module — le sous-hook `infrasprojectEnrichObjectLine` retourne donc une chaîne vide pour ces lignes (et InfraSProject lui-même les ignore via le filtre `special_code > 3`).

**Convention de nommage** : le préfixe `infrasproject` dans le nom du sous-hook reflète son origine historique (introduit par ce module en 21.1.0). InfraSPackPlus 21.1.0 a adopté le même nom pour qu'un module qui implémente le sous-hook fonctionne indifféremment derrière les deux modules hôtes. Renommer le hook impliquerait une coordination cross-modules — à éviter sauf nécessité.

**Compatibilité ascendante** : si aucun module tiers n'implémente les sous-hooks, `$hookmanager->resPrint` reste vide, aucun `preg_replace` n'est effectué et le rendu est strictement identique aux versions antérieures.

**Réinitialisation du buffer** : avant l'appel à `executeHooks`, InfraSProject sauvegarde la valeur courante de `$hookmanager->resPrint` puis la vide, et la restaure après la récupération du HTML enrichi. Évite de polluer le `resPrint` global avec le HTML enrichi (qui pourrait être lu par d'autres hooks plus loin dans le cycle).

**Filtrage des lignes spéciales d'autres modules** : `printObjectLine` retourne 0 (laisse au module propriétaire le soin de rendre) dès que `$line->special_code > 3` (convention Dolibarr : les codes 0–3 sont les types natifs, > 3 indique une ligne d'un module externe). Sans ce filtre, InfraSProject émettrait son propre `<tr>` standard pour des lignes spéciales infrastructure / infrasdiscount / etc., produisant une doublure dans le DOM.

### Consommation de stock (`correct_stock()`)

`InfraSProject::correct_stock()` dans `class/infrasproject.class.php` gère la décrémentation/incrémentation de stock sur un projet :

1. Récupère le produit et son PMP (ou `cost_price` si PMP = 0)
2. Calcule le sens du mouvement via `$op[0]` / `$op[1]` selon le signe de `$nbpiece`
3. Crée un objet `MouvementStock` avec l'origine `project` et `fk_project = origin_id`
4. Génère le code inventaire : `INFRASPROJECT_INVCODEPREFIX` + `ref projet` + `dol_print_date($datem, '%y%m%d%H%M%S')`
5. Appelle `MouvementStock::_create()` dans une transaction `begin()` / `commit()` ou `rollback()`
6. Supporte les paramètres de lot (`batch`, `eatby`, `sellby`)

Le formulaire de consommation est rendu par `FormInfrasproject::showformwrite()` qui :
- Filtre les produits par entrepôt sélectionné (`list_product_warehouse()`)
- Filtre optionnellement par catégorie produit (`INFRASPROJECT_STOCK_PROD_CAT`, CSV d'IDs, `-2` = sans catégorie)
- Recharge la page en JavaScript au changement d'entrepôt (`$("#id_entrepot").change()`)
- Prend en charge l'association lot/série ou utilisateur selon `INFRASPROJECT_LINK_TO_USER` et l'activation de `productbatch`

### Comptage et recherche des mouvements (`countconso()`)

`InfraSProject::countconso()` compte les mouvements de stock associés à un projet selon le mode de recherche `INFRASPROJECT_SEARCHMODE` :

| Mode | Critère SQL |
|------|-------------|
| `1` | `m.label LIKE "%{ref}%"` |
| `2` | `m.inventorycode LIKE "{prefix}{ref}%"` |
| `3` | Combinaison des deux modes (OR) |

Ce comptage détermine le badge affiché sur l'onglet « Consommation Stock » via le hook `completeTabsHead`.

### Affichage des mouvements (`showformview()`)

`FormInfrasproject::showformview()` dans `class/html.forminfrasproject.class.php` affiche la liste paginée et filtrable des mouvements de stock associés au projet :

- Requête SQL joignant `stock_mouvement`, `product`, `entrepot`, `user`, `product_lot`
- Filtrage dynamique identique à `countconso()` (modes 1, 2, 3)
- Support des extrafields de `stock_mouvement`
- Champ batch renommé « Prénom / Nom » si `productbatch` désactivé et `INFRASPROJECT_LINK_TO_USER` actif
- Colonne « Montant HT » calculée en temps réel : `PMP × |qty| × signe(qty)`
- Ligne de total en bas de liste via `list_print_total.tpl.php`

### Calcul de marge provisionnelle (`calculateProvMargin()`)

`InfraSProject::calculateProvMargin()` dans `class/infrasproject.class.php` repose sur la liste des éléments référents fournie par `infrasproject_getListOfReferent()` :

1. Charge `$listofreferent` (21 types d'éléments : devis, commandes, factures, NdF, prêts, etc.)
2. Applique les constantes de configuration `provmargin` :
   - `INFRASPROJECT_ADD_SUPPLIER_INVOICE_IN_MARGIN_PROV` → ajoute `provmargin = 'minus'` pour `invoice_supplier`
   - `INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV` → remplace les éléments `add`
   - `INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV` → remplace les éléments `minus`
3. Pour chaque type qualifié, récupère les éléments via `get_element_list()` (SQL adapté par type : `agenda`, `expensereport`, `project_task`, `element_time`, `stock_mouvement`, `loan`, etc.)
4. Filtres appliqués avant sommation :
   - Devis : seuls `STATUS_SIGNED` / `STATUS_BILLED`
   - Factures clients : exclut `close_code = 'replaced'` et dépôts si `FACTURE_DEPOSITS_ARE_JUST_PAYMENTS`
   - Factures fournisseur : ignore celles liées à une commande fournisseur si `INFRASPROJECT_ADD_SUPPLIER_INVOICE_IN_MARGIN_PROV`
   - NdF : exclut certains types selon `INFRASPROJECT_TYPE_FEES_NOT_INCLUDED_IN_MARGIN`
   - Commandes fournisseur : calcule le reste à payer (HT/TTC) = total − somme des factures liées
5. Agrégation : les éléments `add` sont additionnés, les autres soustraits
6. Retourne : `ca_ht`, `margin_ht`, `margin_ttc`, `margin_rate`, `propal_ht`, `supplier_order_ht`

### Liste des éléments référents (`infrasproject_getListOfReferent()`)

Fonction clé dans `core/lib/infrasproject.lib.php` retournant un tableau de 21 types d'éléments liés aux projets :

- `entrepot`, `propal` (provmargin=add), `order`, `invoice` (margin=add), `invoice_predefined`, `proposal_supplier`, `order_supplier` (provmargin=minus), `invoice_supplier` (margin=minus), `contract`, `intervention` (margin=minus), `shipping`, `mrp`, `trip` (margin=minus), `expensereport` (provmargin=minus, margin=minus), `donation` (margin=add), `loan` (margin=add), `chargesociales` (margin=minus), `project_task` (margin=minus), `stock_mouvement` (margin=minus), `salaries` (margin=minus), `variouspayment` (margin=minus)

Chaque entrée définit : `name`, `title`, `class`, `table`, `datefieldname`, `margin`/`provmargin`, `urlnew`, `test` (permissions), `project_field`.

Le hook `completeListOfReferent` (`projectOverview`) ajoute dynamiquement l'entrée `invoice_supplier_det` (lignes de factures fournisseur par projet) via la classe `Infrasprojectsupplierinvoiceline`, et reconfigure les éléments `margin` via `PROJECT_ELEMENTS_FOR_PLUS_MARGIN` / `PROJECT_ELEMENTS_FOR_MINUS_MARGIN`.

### Répartition multi-projets factures fournisseur

La classe `Infrasprojectsupplierinvoiceline` dans `class/infrasprojectsupplierinvoiceline.class.php` (étend `SupplierInvoiceLine`) permet d'afficher les lignes de factures fournisseur ventilées par projet dans l'overview projet.

Le trigger `LINEBILL_SUPPLIER_CREATE` / `LINEBILL_SUPPLIER_MODIFY` exécute `supplierInvoiceLineProject()` :

1. Récupère la facture fournisseur parent (`FactureFournisseur::fetch()`)
2. Lit le projet actuel de la ligne via `infrasproject_printprj($line->id, 1)`
3. Met à jour `facture_fourn_det` avec `entity`, `fk_soc` et `fk_projet` de la ligne
4. Si un projet est assigné, supprime le projet de la facture parent (`setProject(0)`) car la ventilation est faite au niveau des lignes

Le hook `doActions` sur `invoicesuppliercard` :
- Bloque le changement de projet global (`classin`) si des lignes ont déjà un projet assigné (avertissement)
- Autorise `addlink` / `dellink` même avec seulement les droits de lecture si `INFRASPROJECT_SHOW_MARGIN_PROV` est activé

### Trigger (`interface_98_modinfrasproject_infrasprojecttrigger`)

Le trigger filtre d'abord par élément (`propal`, `facture_fourn_det`) et par action :

| Action | Comportement |
|--------|-------------|
| `PROPAL_CLOSE_SIGNED` | Si `INFRASPROJECT_CREATE_PROJECT_FROM_SIGNED_PROPAL` est actif : crée un projet via `createProject()`, le valide via `setValid()`. Le projet hérite du titre (`ref_client` ou `thirdparty.name - ref`), du tiers, des extrafields du devis. Offre un hook `createProject` / `afterCreateProject` pour personnalisation |
| `LINEBILL_SUPPLIER_CREATE` | Assigne un projet à la ligne de facture fournisseur créée |
| `LINEBILL_SUPPLIER_MODIFY` | Met à jour le projet de la ligne de facture fournisseur modifiée |

La génération de la référence projet utilise le modèle de numérotation configuré dans `PROJECT_ADDON`.

### Structure du changelog (Changelog structure)

```xml
<changelog>
  <Version Number="21.1.4" MonthVersion="2026-06">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
  </Version>
  <InfraS Downloaded="20260619"/>
  <Dolibarr minVersion="18.0.0" maxVersion="24.x.x"/>
  <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

- Types de changement : `add` (ajout, vert), `chg` (modification, bleu), `fix` (correction, rouge/caution)
- L'attribut `Downloaded` est mis à jour automatiquement lors du téléchargement de la version distante
- Versions ordonnées chronologiquement (la dernière est la plus récente)
- Parsé par `infrasproject_getChangelogFile()` / `infrasproject_getLocalVersionMinDoli()`

La fonction `infrasproject_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "21.1.4",          // Version courante
    1 => "21.0.0",          // Version min Dolibarr
    2 => 0,                 // Flag erreur (-1 = KO, 0 = OK)
    3 => "24.x.x",          // Version max Dolibarr
    4 => "NOT_RETRIEVED",   // Réservé
    5 => "7.4",             // Version min PHP
    6 => "8.4"              // Version max PHP
]
```

### Cycle de vie du module (Module lifecycle)

**Activation (`init()`)** :

1. `_load_tables('/infrasproject/sql/')` — exécute `data.sql` et `update.sql`
2. `infrasproject_restore_module('infrasproject')` — restaure les constantes sauvegardées
3. Initialise `INFRASPROJECT_DOL_VERSION` et `INFRASPROJECT_MAIN_VERSION`

**Désactivation (`remove()`)** :

1. `infrasproject_bkup_module('infrasproject')` — sauvegarde les constantes `INFRASPROJECT_%`
2. Supprime toutes les constantes `INFRASPROJECT_%` de l'entité courante
3. Copie le fichier de sauvegarde dans `admin/` avec horodatage

### Intégration ContactTracking

Le hook `formObjectOptions` sur `projectcard` affiche deux informations optionnelles (via `$this->resprints`) :

- **Dernier échange** (si `INFRASPROJECT_SHOW_LAST_EXCHANGE` + module `contacttracking` actif) : requête SQL sur `llx_contacttracking` → `element_type = 'projet'` + `fk_element_id`, tri par `date_creation DESC LIMIT 1`, affiche `$contactEx->comment`
- **Prochaine action** (si `INFRASPROJECT_SHOW_NEXT_ACTION`) : requête SQL sur `llx_actioncomm` → `fk_project`, tri par `datep DESC LIMIT 1`, affiche `note_private` + date formatée

Dégradation gracieuse : l'include de `contacttracking.class.php` est conditionné par `isModEnabled('contacttracking')` et le hook vérifie `class_exists('Contacttracking')`.

### Classe InfraSProject (héritage)

`InfraSProject extends Project` dans `class/infrasproject.class.php` :

- Le constructeur adapte les labels de statut selon la version Dolibarr (≥19 : `labelStatusShort` / `labelStatus` ; <19 : `statuts_short` / `statuts_long`)
- Désactive dynamiquement les champs selon les options : `PROJECT_USE_OPPORTUNITIES`, `PROJECT_HIDE_TASKS`, module `eventorganization`
- Surcharge `get_element_list()` pour supporter des types spéciaux : `agenda` (fk_project), `expensereport` (via table de détail), `element_time` (avec fk_user), `stock_mouvement` (origintype=project), `loan` (filtrage par dates début/fin inversées)
- Filtre sur les dates via `infrasproject_isDolTms()` (validation robuste des timestamps)

### Classe Infrasprojectsupplierinvoiceline (héritage)

`Infrasprojectsupplierinvoiceline extends SupplierInvoiceLine` dans `class/infrasprojectsupplierinvoiceline.class.php` :

- `getNomUrl()` : construit un lien cliquable vers la facture fournisseur parent (`FactureFournisseur::fetch()`) avec tooltip complet (type, statut, ref, ref fournisseur, label, date, montants HT/TVA/TTC)
- `getLibStatut()` : délègue à la facture fournisseur parent pour afficher le statut avec icône
- `fetch_thirdparty()` : lit `fk_soc` depuis `facture_fourn_det` (pas depuis l'objet line standard), récupère aussi la date et le statut de la facture parent

### Formulaire de consommation (`FormInfrasproject`)

`class/html.forminfrasproject.class.php` expose deux méthodes principales :

- `showformwrite($user, $module, $object)` : formulaire d'ajout de consommation avec entrepôt (`selectWarehouses`), produit (filtré par entrepôt et catégorie), quantité, date, lot/série ou utilisateur
- `showformview($user, $object)` : vue paginée et triable des mouvements de stock (SQL multi-tables avec extrafields, filtrage par ref, date, produit, lot, entrepôt, auteur, code inventaire, label, quantité)

### Fonctions utilitaires (`infrasproject.lib.php`)

| Fonction | Rôle |
|----------|------|
| `infrasproject_is_substitution_page($path)` | Détecte si le chemin est déjà une page de substitution |
| `infrasproject_get_substitution_url($path)` | Retourne l'URL de substitution si disponible |
| `infrasproject_get_const_name_from_substitution_path($path)` | Construit le nom de constante (`INFRASPROJECT_PS_ACTIVE_...`) |
| `infrasproject_printprj($lineid, $idOnly)` | Retourne le projet lié à une ligne de facture fournisseur (HTML ou ID) |
| `infrasproject_finduser($search)` | Trouve un utilisateur par prénom + nom (recherche bidirectionnelle) |
| `infrasproject_getListOfReferent($id, $socid)` | Tableau de 21 types d'éléments référents pour les calculs de marge |
| `infrasproject_isDolTms($timestamp)` | Valide qu'un timestamp est numérique et non-null, avec log de dépréciation pour chaînes vides |
| `infrasproject_getCallerInfoString()` | Retourne la trace d'appel (fichier + ligne) pour les messages de log |
| `infrasproject_replaceKeyArray($array, $fieldKey, $fieldValue)` | Transforme un tableau associatif à partir de champs key/value |

### Bibliothèque admin (`infrasprojectAdmin.lib.php`)

| Fonction | Rôle |
|----------|------|
| `infrasproject_admin_prepare_head()` | Onglets admin : Général, Paramètres, À propos, Changelog (conditionné par permissions) |
| `infrasproject_no_topmenu()` | Vérifie l'existence du menu InfraS dans `llx_menu` |
| `infrasproject_test_php_ext()` | Teste l'extension XML, écrit `INFRAS_PHP_EXT_XML` (1 ou -1) |
| `infrasproject_getLocalVersionMinDoli($appliname)` | Parse `docs/changelog.xml` → retourne `[version, dolMin, flag, versions[], dolMax, phpMin, phpMax]` |
| `infrasproject_getChangelogFile($appliname, $from)` | Charge et parse le changelog XML (local ou téléchargé) via `simplexml_load_string()` |
| `infrasproject_dwnChangelog($appliname)` | Télécharge le changelog distant depuis `infras.fr`, ajoute la date de téléchargement |
| `infrasproject_bkup_module($appliname)` | Sauvegarde les constantes `INFRASPROJECT_%` dans un fichier SQL avec `INSERT ... ON DUPLICATE KEY UPDATE` + placeholder `__ENTITY__` |
| `infrasproject_bkup_table($table, $sql, $listeCols, $duplicate, $truncate, $add)` | Génère les requêtes SQL d'insertion pour la sauvegarde (compatible MySQL/PostgreSQL) |
| `infrasproject_restore_module($appliname)` | Restaure les constantes depuis le fichier SQL via `run_sql()` |
| `infrasproject_print_backup_restore()` | Interface HTML sauvegarde/restauration |
| `infrasproject_getChangeLog($appliname, ...)` | Affiche le changelog HTML avec comparaison versions locale/distante (couleurs : orange=nouveau, vert=en avance) |
| `infrasproject_getSupportInformation($currentversion)` | Tableau HTML d'informations support (Dolibarr, module, PHP, DB, serveur web) |
| `infrasproject_load_title(...)` | Titre avec picto pour les pages admin |
| `infrasproject_print_colgroup($metas)` | Génère les colonnes HTML pour les tableaux admin |
| `infrasproject_print_liste_titre($metas)` | En-tête de liste pour tableaux admin |
| `infrasproject_print_btn_action(...)` | Bouton d'action dans les formulaires admin |
| `infrasproject_print_hr($cs)` / `infrasproject_print_final($cs)` | Séparateurs HTML |
| `infrasproject_print_input(...)` | Champ de formulaire universel : `on_off`, `on_off2`, `input`, `input2`, `radio`, `textarea`, `color`, `select`, `select_produits`, `select_types_paiements`, `selectTypeContact`, `select_type_actions`, `select_warehouse`, `multiselect_type_fees`, `editor` |

### Page consommation stock (`infrasproject_tab.php`)

Point d'entrée pour l'onglet consommation stock sur la fiche projet :

1. Charge la configuration via `config.php` (→ `main.inc.php`)
2. Récupère le projet par `id` ou `ref`
3. Action `conso` : construit les paramètres de consommation (quantité, date, lot/utilisateur) et appelle `InfraSProject::correct_stock()`
4. Affichage : en-tête projet avec `project_prepare_head()` / `dol_get_fiche_head()`, puis délègue à `FormInfrasproject::showformwrite()` + `showformview()`

### Cas d'usage courants

**1. Consommer du stock sur un projet**
   - L'utilisateur ouvre l'onglet « Consommation Stock » d'un projet ouvert
   - Sélectionne un entrepôt → liste produits rechargée dynamiquement (JS)
   - Saisit quantité, date, lot/série
   - `infrasproject_tab.php` appelle `InfraSProject::correct_stock()` → mouvement de stock type sortie

**2. Ventiler une facture fournisseur par projets**
   - Lors de la création/modification d'une ligne de facture fournisseur, le trigger `LINEBILL_SUPPLIER_CREATE` / `MODIFY` est déclenché
   - Il affecte le projet sélectionné au niveau de chaque ligne (`facture_fourn_det.fk_projet`)
   - Le projet global de la facture est désassocié pour éviter les doublons dans les calculs

**3. Créer automatiquement un projet à la signature d'un devis**
   - Le commercial signe un devis (action `PROPAL_CLOSE_SIGNED`)
   - Le trigger crée un projet avec le titre du devis, le tiers, les extrafields
   - Le projet est automatiquement validé et lié au devis

**4. Afficher la marge provisionnelle sur la vue d'ensemble projet**
   - Le hook `completeListOfReferent` injecte `invoice_supplier_det` et reconfigure les éléments de marge
   - `calculateProvMargin()` agrège les éléments add/minus avec filtrage par statut et type
   - L'overview projet affiche le CA, la marge HT/TTC, le taux de marge, et les sous-totaux devis/commandes fournisseur