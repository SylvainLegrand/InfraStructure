# CLAUDE.md — Contexte module infrassupprice

## Aperçu (Overview)

`infrassupprice` est un module externe Dolibarr de gestion avancée des tarifs fournisseurs :

- mise à jour des prix fournisseurs depuis les documents commerciaux (demandes de prix, commandes, factures),
- affichage d'un tableau de comparaison prix document / prix fournisseur en base,
- support multi-devises complet,
- option de gestion de la quantité minimum.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS
- Numéro module : `500056`
- Licence : GPL v3+
- Compatibilité Dolibarr : `15.0.0` à `21.0.3`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `15.2.0` (2026-02)
- Dépendance obligatoire : extension PHP `xml`
- Emplacement : `htdocs/custom/infrassupprice/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrassupprice/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrassuppricesetup.php
├── class/
│   ├── actions_infrassupprice.class.php
│   └── infrassupprice.class.php
├── config.php
├── core/
│   ├── lib/
│   │   └── infrassuppriceAdmin.lib.php
│   └── modules/
│       └── modinfrassupprice.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrassupprice.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── img/
├── langs/
│   ├── en_US/infrassupprice.lang
│   ├── es_ES/infrassupprice.lang
│   └── fr_FR/infrassupprice.lang
├── script/
│   ├── interface.php
│   └── message.php
└── sql/
    └── data.sql
```

## Descripteur module (Module descriptor : `modinfrassupprice`)

Dans `core/modules/modinfrassupprice.class.php` :

- **Module parts** :
- hooks : `supplier_proposalcard`, `ordersuppliercard`, `invoicesuppliercard`
- CSS : `/infrassupprice/css/infrassupprice.css.php`
- **Dépendances** : aucune dépendance module ; extension PHP `xml` requise
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : 4 permissions
- `paramMenu`
- `paramInfraSSupPrice`
- `paramBkpRest`
- `update`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrassupprice/sql/')`)
2. Restauration des constantes module (`infrassupprice_restore_module`)
3. Initialisation de constantes clés :
 - `INFRASSUPPRICE_DOL_VERSION`
 - `INFRASSUPPRICE_MAIN_VERSION`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue sauvegarde module (`infrassupprice_bkup_module`), nettoyage des constantes `INFRASSUPPRICE_%` de l'entité courante.

## Fonctionnement principal (Core behavior)

Le module opère en trois étapes depuis les fiches documents fournisseur validés :

1. **Affichage** — Le hook injecte un tableau replié listant chaque ligne produit du document avec valeurs courantes et valeurs en base (tarif fournisseur existant, affiché en tooltip).

2. **Sélection** — L'utilisateur coche les produits à mettre à jour, avec possibilité de modifier la référence fournisseur et la quantité minimum.

3. **Mise à jour** — Appels AJAX séquentiels vers `script/interface.php` qui appelle `InfraSSupPrice->InfraS_update_buyprice()` pour créer ou mettre à jour le tarif dans `llx_product_fournisseur_price`.

Le module s'appuie sur :

- `actions_infrassupprice.class.php` pour l'injection du tableau via hook,
- `infrassupprice.class.php` (extends `Product`) pour la logique CRUD des prix fournisseurs,
- `infrassuppriceAdmin.lib.php` pour les fonctions d'administration (onglets, version, backup/restore, changelog XML).

## Hooks et comportement (Hook behavior)

La classe `Actionsinfrassupprice` intervient exclusivement sur le hook `addMoreActionsButtons` dans les contextes :

- `supplier_proposalcard` : fiches demandes de prix fournisseur (statut ≥ 1),
- `ordersuppliercard` : fiches commandes fournisseur (statut ≥ 1),
- `invoicesuppliercard` : fiches factures fournisseur (statut ≥ 1).

Le hook construit un tableau HTML avec JavaScript jQuery intégré pour :

- case à cocher globale (`#all_up_qp`),
- bouton de mise à jour (`#bt_add_sp`),
- appels AJAX séquentiels (`async: false`) vers `script/interface.php`,
- affichage des messages via `script/message.php` + rechargement de page.

## Données / SQL (Data model)

Aucune table propre — le module utilise les tables Dolibarr core :

- `llx_product_fournisseur_price` : stockage des tarifs fournisseur par produit/fournisseur/quantité
- `llx_product` : référence produit
- `llx_const` : constantes de configuration module

Le fichier `sql/data.sql` contient uniquement la valeur par défaut de `INFRASSUPPRICE_QTE_NOT_VALUE_MIN`.

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `INFRASSUPPRICE_QTE_NOT_VALUE_MIN` : force la quantité minimum à 1 au lieu de la quantité de la ligne document (défaut : `0`)
- `INFRASSUPPRICE_DOL_VERSION` : version Dolibarr lors de l'activation
- `INFRASSUPPRICE_MAIN_VERSION` : version module lors de l'activation
- `INFRASSUPPRICE_DISABLE_CHECK_VERSION_MIN` : désactive le contrôle de version minimum Dolibarr
- `INFRAS_PHP_EXT_XML` : statut de l'extension PHP XML (`1` = chargée, `-1` = manquante)

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / hooks / constantes :

1. Désactiver puis réactiver le module
2. Vérifier les constantes module (`INFRASSUPPRICE_*`)
3. Vérifier les permissions (4 permissions : `paramMenu`, `paramInfraSSupPrice`, `paramBkpRest`, `update`)
4. Tester la mise à jour de prix depuis une commande fournisseur validée
5. Tester le support multi-devises si le module multicurrency est actif

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrassupprice_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module auto-désactive si la version Dolibarr est inférieure au minimum requis
- Les scripts AJAX (`interface.php`, `message.php`) nécessitent `NOTOKENRENEWAL` et un contrôle d'accès
- Le hook n'affiche le tableau que sur documents validés (statut ≥ 1) et si l'utilisateur a le droit `update`

## Dernières mises à jour (Recent updates)

- `15.2.0` (2026-02) : corrections d'injections SQL dans la classe métier et le hook (cast int/float, `$db->escape()`, guillemets simples)
- `15.2.0` (2026-02) : ajout du contrôle d'accès et de `NOTOKENRENEWAL` sur les endpoints AJAX
- `15.2.0` (2026-02) : échappement des URLs de formulaires basées sur `PHP_SELF` (durcissement XSS)
- `15.2.0` (2026-02) : suppression de l'exposition des requêtes SQL dans les messages d'erreur
- `15.2.0` (2026-02) : remplacement de `now()` SQL par `$db->idate(dol_now())`
- `15.2.0` (2026-02) : validation GETPOST avec types appropriés sur tous les paramètres non typés
- `15.2.0` (2026-02) : normalisation `elseif` (PSR-12) et harmonisation de la librairie admin
