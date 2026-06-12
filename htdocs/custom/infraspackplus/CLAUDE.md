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
- Compatibilité Dolibarr : `21.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `21.2.0` (2026-06)
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
│   └── v21/                              # Backport getDolGlobalFloat/Bool pour Dolibarr < 21 (v20 supprimé — natif depuis Dolibarr 20.0.0)
├── class/
│   ├── actions_infraspackplus.class.php
│   ├── address.class.php
│   └── tcpdf_infrasplus.class.php
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
│   │   ├── lineviews/                    # Templates de lignes actifs (v21, v22, v22-DolInfraS, v23, v24)
│   │   │   └── _columns/                 # Partials partagés entre versions (refproject, discount, total_ht)
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
	- `models`, `triggers` (le `tpl` a été supprimé en v21.0.0 — rendu migré vers le hook `printObjectLine`)
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
- `tcpdf_infrasplus.class.php` pour les surcharges TCPDF/TCPDI (correction `ColorFlag` et z-order),
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
- appels TCPDF avec couleurs : toujours caster en `(int)` les arguments de `SetTextColor()`, `SetDrawColor()`, `SetFillColor()` quand ce sont des variables (requis PHP 8.x),
- déclarations de propriétés : toutes les classes PDF doivent déclarer explicitement leurs propriétés (pas de propriétés dynamiques, interdit depuis PHP 8.2),
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

Voir `docs/changelog.xml` pour l'historique complet des versions.

## Notes techniques (Technical notes)

### Substitution de pages vs hooks

Comme InfraSCusPrice, InfraSPackPlus utilise la **substitution de pages** pour certaines pages Dolibarr :
- **Pages substituées** : `societe/contact.php` (contacts société) et `admin/dict.php` (dictionnaires admin)
- **Constante d'activation** : générée dynamiquement depuis le chemin (ex. `/societe/contact.php` → `INFRASPACKPLUS_PS_ACTIVE_SOCIETE_CONTACT`)
- **Branches maintenues** : `dlb210x`, `dlb220x`, `dlb220x-DolInfraS`, `dlb230x`, `dlb240x` (5 branches dont 1 variante DolInfraS)
- **Avantages** : contrôle total de la page, adaptation par version Dolibarr et par distribution (Dolibarr standard vs DolInfraS/LTS by InfraS)
- **Inconvénients** : maintenance d'un fichier par page et par version majeure

### Flux de redirection (Redirect flow)

```
L'utilisateur accède à une page substituée (ex. /societe/contact.php)
    ↓
Le hook updateSession() ou afterLogin() s'exécute
    ↓
infraspackplus_is_substitution_page() vérifie via strpos() sur le chemin
    si on est déjà sur une page substituée (prévention de boucle)
    ↓
infraspackplus_getSubstitutionRedirectUrl() génère l'URL de redirection :
    → Filtre les paramètres GET (exclusion du token CSRF)
    ↓
infraspackplus_get_substitution_url() génère l'URL substituée :
    → Vérifie la constante INFRASPACKPLUS_PS_ACTIVE_<PATH_UPPER>
    → Construit le chemin : /infraspackplus/substitutionpages/dlb{major}0x{-DolInfraS}/
    → Vérifie l'existence physique du fichier via dol_buildpath()
    ↓
Redirection header('Location: ...') avec conservation des paramètres GET/POST → exit
```

### Mécanisme de génération PDF (PDF generation mechanism)

Le module intervient via trois hooks principaux sur le contexte `pdfgeneration` :

**`formBuilddocOptions()`** (hook `formfile`) :
- Affiche les options de génération avancées sous le formulaire standard de génération des documents
- Couvre tous les types d'objets : `propal`, `commande`, `facture`, `contrat`, `fichinter`, `shipping`, `reception`, `delivery`, `supplier_proposal`, `order_supplier`, `product`, `mo`, `bom`, `project`, `expensereport`
- Options : logo émetteur, adresses (expéditeur/destinataire/livraison/facturation), mentions (dictionnaire `c_infraspackplus_mention`), notes publiques (dictionnaire `c_infraspackplus_note`), CGV/CGI/CGA, fichiers joints, zone de signature client (canvas JS), infos douanières, images produits, etc.
- Contrôle des droits via la permission `paramLastOpt`

**`beforePDFCreation()`** (hook `pdfgeneration`) :
- Enregistre `$_SESSION['InfraSPackPlus_model'] = true` pour signaler l'utilisation du template InfraSPlus (utilisé par actions_infrastructure pour déléguer la génération du récap à InfraSPackPlus)
- Récupère les paramètres par défaut via `infraspackplus_defaultParam($object)`
- Collecte et sauvegarde les choix dans **4 niveaux de constantes** :
  - `INFRASPLUS_PDF_PARAMS_{element}_USER_{user_id}` — par utilisateur
  - `INFRASPLUS_PDF_PARAMS_{element}_DOC_{object_id}` — par document
  - `INFRASPLUS_PDF_PARAMS_{element}_TYPE` — par type de document
  - `INFRASPLUS_PDF_PARAMS_{element}_CUST_{thirdparty_id}` — par client/tiers

**`afterPDFCreation()`** (hook `pdfgeneration`) :
- Nettoie la variable de session `$_SESSION['InfraSPackPlus_model']`

### Autres hooks notables

| Hook | Contexte | Rôle |
|------|----------|------|
| `formObjectOptions()` | `thirdpartycard` | Gestion du logo émetteur par tiers sur la fiche société |
| `doActions()` | `globalcard` | Génération semi-automatique des PDF quand `INFRASPLUS_PDF_SEMIAUTOUPDATE=1` (à la validation, changement de notes, d'extrafields, etc.). Voir *Hook doActions — génération semi-automatique PDF* ci-dessous. |
| `printObjectLine()` | `thirdpartycard`, `globalcard`, `*card` | Rendu des lignes de document en mode view (depuis v21.0.0 : migration module_parts['tpl'] → hook). Dispatcher versionné : charge `core/tpl/lineviews/v{21,22,22-DolInfraS,23,24}.tpl.php` + partials `_columns/` (refproject, discount, total_ht). Depuis 21.1.0 : buffer ob_start + sous-hook `infrasprojectEnrichObjectLine` pour injection de colonnes tierces. Cf. *Sous-hook d'enrichissement des lignes*. **Exclusions** (depuis 21.1.2) : `evaluation` (HRM, template natif), `bom` / `mo` (structure manufacturing — colonnes qty_frozen / disable_stock_change / efficiency / cost incompatibles avec les colonnes commerciales vat / uht / discount / ht des templates lineviews), contextes `expeditioncard` / `ordershipmentcard` (depuis 21.1.1, layout colonnes propre à expedition). Retour anticipé `return 0` pour laisser les templates natifs reprendre la main. |

### Hook doActions — génération semi-automatique PDF (depuis v21.0.0, fix v21.1.3)

#### Constantes liées

| Constante | Rôle |
|-----------|------|
| `INFRASPLUS_PDF_SEMIAUTOUPDATE` | Active l'interception de la validation par infraspackplus (`1` = actif) |
| `MAIN_DISABLE_PDF_AUTOUPDATE` | Désactive la génération PDF automatique standard de Dolibarr après action |
| `INFRASPLUS_PDF_UPDATE_ON_NOTES_CHANGE` | Regénère le PDF quand les notes publiques changent (nécessite SEMIAUTOUPDATE) |
| `INFRASPLUS_PDF_UPDATE_ON_EXF_CHANGE` | Regénère le PDF quand un extrafield change (nécessite SEMIAUTOUPDATE) |
| `INFRASPLUS_PDF_UPDATE_ON_FIELDS_CHANGE` | Regénère le PDF quand certains champs changent (nécessite SEMIAUTOUPDATE) |

**Règle d'usage** : ces deux constantes doivent être cohérentes. L'admin infraspackplus (`infrasplussetup.php`) maintient cette cohérence automatiquement :
- Activer `INFRASPLUS_PDF_SEMIAUTOUPDATE` → positionne `MAIN_DISABLE_PDF_AUTOUPDATE = 1`
- Désactiver `MAIN_DISABLE_PDF_AUTOUPDATE` → positionne `INFRASPLUS_PDF_SEMIAUTOUPDATE = 0`

Ne jamais modifier `MAIN_DISABLE_PDF_AUTOUPDATE` directement via l'admin Dolibarr standard ; passer toujours par la page de configuration d'infraspackplus.

#### Fonctionnement quand INFRASPLUS_PDF_SEMIAUTOUPDATE = 1

Le hook `doActions` intercepte les actions de validation (`confirm_validate`, `confirm_valid`) et les actions de modification déclenchant une regénération (`setnote_public`, `update_extras`, `setecheance`, `setconditions`, `setmode`, `setbankaccount`, `setdate_livraison`, `setavailability`).

Pour chaque type d'objet (`Propal`, `Commande`, `Facture`, `Contrat`, `Fichinter`, `SupplierProposal`, `CommandeFournisseur`), le hook :
1. Exécute l'action métier (`$object->valid()`, `$object->update_note()`, etc.)
2. Recharge l'objet via `$object->fetch()` pour synchroniser tous les champs
3. Génère le PDF via `$object->generateDocument()` ou équivalent
4. **Retourne 1** → le code standard de `card.php` est sauté

#### Bug statut brouillon après validation (corrigé en v21.1.3)

**Symptôme** : avec `INFRASPLUS_PDF_SEMIAUTOUPDATE=0` et `MAIN_DISABLE_PDF_AUTOUPDATE=1`, valider un devis changeait la référence (PROV→PR) mais le statut restait affiché en brouillon ; une deuxième validation était nécessaire.

**Cause** : `Propal::valid()` met à jour `$this->statut` (champ déprécié) mais **pas** `$this->status`. Dans `card.php`, le `$object->fetch()` qui synchronise les deux est dans le bloc conditionnel `if (!MAIN_DISABLE_PDF_AUTOUPDATE)` — ce bloc était sauté. `$object->status` restait donc à `0` en affichage → bouton « Valider » affiché de nouveau.

**Correctif** (v21.1.3, `doActions()`) : quand `INFRASPLUS_PDF_SEMIAUTOUPDATE=0` mais `MAIN_DISABLE_PDF_AUTOUPDATE=1`, le hook intercepte `confirm_validate` pour les objets `Propal`, exécute `valid()` + `fetch()` + `fetch_thirdparty()` sans génération de PDF, et retourne 1 pour sauter le code standard (qui aurait fait le `valid()` sans le `fetch()`).

**Invariant à maintenir** : si d'autres types d'objets présentent le même symptôme dans cette configuration, appliquer le même pattern dans le bloc `if (!SEMIAUTOUPDATE && MAIN_DISABLE_PDF_AUTOUPDATE)` de `doActions()`.

### Sous-hook d'enrichissement des lignes (depuis 21.1.0)

Pour permettre à d'autres modules d'ajouter des colonnes au tableau de lignes sans entrer en concurrence sur `printObjectLine` (le HookManager Dolibarr ne s'arrête pas au 1er retour positif et appelle séquentiellement tous les modules qui implémentent un même hook, produisant des `<tr>` en doublon), InfraSPackPlus capture le rendu de son template versionné via `ob_start`, exécute le sous-hook `infrasprojectEnrichObjectLine` après l'include, collecte le HTML retourné par les modules tiers via `$hookmanager->resPrint` et l'injecte juste avant la cellule `.linecolmove` via `preg_replace`.

Le nom du sous-hook (`infrasprojectEnrichObjectLine`) a été introduit par InfraSProject 21.1.0 puis adopté tel quel par InfraSPackPlus 21.1.0 — la convention est partagée entre les deux modules pour que les modules tiers (ex. infrastructure pour la colonne « Opt ») fonctionnent indifféremment derrière InfraSProject ou InfraSPackPlus :

- En **mode view** sur tous les documents : InfraSProject cède la main à InfraSPackPlus via `if ($isView && isModEnabled('infraspackplus')) return 0;` → c'est donc InfraSPackPlus qui rend la ligne et qui appelle le sous-hook.
- En **mode edit** : InfraSPackPlus ne surcharge pas (return 0 par défaut), InfraSProject reprend la main et c'est lui qui appelle le sous-hook depuis ses propres lineedits.

**Filtrage des lignes spéciales d'autres modules** : InfraSPackPlus filtre déjà les lignes Infrastructure, Subtotal ATM et Ouvrage via `infraspackplus_isInfrastructureLine($line) || $isATMLine || $isOuvrageLine → return 0`. Les modules propriétaires de ces lignes (notamment infrastructure pour ses titres/sous-totaux/textes libres) sont responsables du rendu et de leur propre cellule Opt — le sous-hook d'enrichissement n'est pas appelé pour ces lignes côté InfraSPackPlus.

**Pattern d'implémentation côté module tiers** : voir CLAUDE.md d'InfraSProject section *Sous-hooks d'enrichissement* pour le code de référence.

**Cas particulier** : InfraSPackPlus ne surcharge pas `printObjectLineTitle` (rendu de l'en-tête `<thead>`). Pour la colonne « Opt », deux scénarios :
- **InfraSProject actif** : c'est InfraSProject qui rend le `<thead>` via son hook `printObjectLineTitle` + sous-hook `infrasprojectEnrichObjectLineTitle`. Le rendu est entièrement serveur.
- **InfraSProject inactif** : depuis infrastructure `21.0.2`, un fallback JavaScript côté infrastructure injecte le `<th>Opt</th>` manquant au DOMReady (les `<td>` standards restent rendus côté serveur via le sous-hook `infrasprojectEnrichObjectLine` qu'IPP appelle déjà). InfraSPackPlus n'a donc plus besoin d'être co-activé avec InfraSProject pour afficher la colonne Opt.

### Détection des lignes de modules externes (External module line detection)

Helpers définis dans `core/lib/infraspackplus.pdf.lib.php` et utilisés dans les modèles PDF + le hook `printObjectLine` :

| Helper | Délègue à | Détection |
|--------|-----------|-----------|
| `infraspackplus_isInfrastructureLine($line)` | `TInfrastructure::isModInfrastructureLine($line)` | Titre, sous-total ou texte libre du module Infrastructure |
| `infraspackplus_isInfrastructureTotal($line)` | `TInfrastructure::isTotal($line)` | Sous-total Infrastructure (qty 91..99) |
| `infraspackplus_isInfrastructureFreeText($line)` | `TInfrastructure::isFreeText($line)` | Texte libre Infrastructure (qty 50) |

Les deux helpers vérifient `isModEnabled('infrastructure')` puis chargent la classe en lazy via `dol_include_once('/infrastructure/class/infrastructure.class.php')` et un garde-fou `class_exists('TInfrastructure')`. Ils retournent `false` si le module n'est pas actif ou si la classe n'est pas disponible, ce qui rend leur appel sûr quel que soit l'état du module externe.

**Pourquoi un wrapper plutôt qu'un appel direct ?** La classe `TInfrastructure` n'est pas auto-chargée par Dolibarr ; sans inclusion explicite, un appel direct provoquerait une fatal error si le module n'est pas actif. Le wrapper centralise l'inclusion lazy et le check `class_exists`.

**Pourquoi pas `infraspackplus_isLineFromExternalModule()` ?** Cette fonction interne instancie la classe descripteur du module externe pour récupérer son numéro (`infraspackplus_get_mod_number`). Quand la classe descripteur n'existe pas, elle retourne `0`, ce qui matche le `special_code = 0` des lignes ordinaires (faux positifs). Les natifs `TInfrastructure::is*()` lisent directement `getModuleNumber()` (cache statique) et sont la source de vérité du module Infrastructure.

Pour les modules **Ouvrage** (Inovea) et **Subtotal** (ATM Consulting), les appels à `infraspackplus_isLineFromExternalModule()` sont toujours utilisés mais systématiquement préfixés par un `isModEnabled()` correspondant pour éviter le même faux positif (voir changelog 18.15.5).

### Classe `Address` (Gestion multi-adresses)

Fichier : `class/address.class.php` — opère sur la table `llx_infraspackplus_societe_address`

| Méthode | Description |
|---------|-------------|
| `create($user)` | Création d'une adresse avec vérification (`verify()`), transaction, puis `update()` pour compléter |
| `update($id, $user)` | Mise à jour de tous les champs, gestion des doublons |
| `verify()` | Vérifie que `label` et `name` sont non vides |
| `fetch($rowid, $socid, $label)` | Charge par `rowid` ou par couple `(socid, label)`. Détecte les doublons (retourne 2) |
| `fetch_lines($socid, $all)` | Charge les adresses d'une société. Mode -1 = internes, 1 = toutes triées, 2 = toutes + adresses principales clients |
| `delete($rowid)` | Suppression simple |

Support multi-entité via filtre `getEntity('address')` et champ `entity` par défaut à `$conf->entity`.

### Trigger (`Infraspackplustrigger`)

Le trigger écoute uniquement les événements sur l'élément `societe` :

| Événement | Condition | Action |
|-----------|-----------|--------|
| `COMPANY_CREATE` | `INFRASPLUS_PDF_SET_LOGO_EMET_TIERS` activé | Associe un logo émetteur au tiers via `infraspackplus_setLogoEmet()` |
| `COMPANY_DELETE` | Toujours | Supprime toutes les adresses secondaires liées via `Address::fetch_lines()` + `Address::delete()` (cascade en PHP) |

### Structure du changelog (Changelog structure)

```xml
<changelog>
  <Version Number="21.2.0" MonthVersion="2026-06">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
  </Version>
  <InfraS Downloaded="20260611"/>
  <Dolibarr minVersion="18.0.0" maxVersion="24.x.x"/>
  <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

- Types de changement : `add` (ajout, vert), `chg` (modification, bleu), `fix` (correction, rouge/caution)
- L'attribut `Downloaded` est mis à jour automatiquement lors du téléchargement de la version distante
- Versions ordonnées chronologiquement (la dernière est la plus récente)
- Parsé par `infraspackplus_getChangelogFile()` / `infraspackplus_getLocalVersionMinDoli()`

La fonction `infraspackplus_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "21.2.0",          // Version courante
    1 => "18.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (-1 = KO, 0 = OK)
    3 => <SimpleXMLElement>, // Liste des versions (ou message d'erreur)
    4 => "24.x.x",           // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

### Cycle de vie du module (Module lifecycle)

**`init()`** effectue dans l'ordre :
1. Copie des polices TCPDF du core vers `DOL_DATA_ROOT/{entity}/infraspackplus/fonts`
2. Copie des polices personnalisées du module
3. Chargement des tables SQL (`_load_tables`)
4. Restauration des paramètres sauvegardés (`infraspackplus_restore_module`)
5. Migration de la table `societe_address` si nécessaire
6. Initialisation de `SOCIETE_ADDRESSES_MANAGEMENT` si non défini
7. Enregistrement de `INFRASPLUS_DOL_VERSION` et `INFRASPLUS_MAIN_VERSION`
8. Purge de `MAIN_MODULE_INFRASPACKPLUS_TPL` (constante résiduelle du mécanisme module_parts['tpl'] supprimé en v21.0.0)
9. Appel de `$this->_init()` standard

**`remove()`** effectue :
1. Sauvegarde des paramètres (`infraspackplus_bkup_module`)
2. Nettoyage SQL : suppression des constantes `INFRASPLUS_%` et `INFRASPACKPLUS_PS_%`, des modèles PDF `InfraSPlus_%`, des constantes `%_ADDON_PDF` liées
3. **DROP TABLE** : `infraspackplus_societe_address`, `c_infraspackplus_mention`, `c_infraspackplus_note`
4. Suppression des extrafields via `infraspackplus_search_extf(-1)`

### Classes TCPDF/TCPDI InfraS (PDF rendering override)

Fichier : `class/tcpdf_infrasplus.class.php` — surcharges TCPDF et TCPDI pour corriger le rendu PDF.

Deux classes : `TCPDF_InfraS` (étend `TCPDF`) et `TCPDI_InfraS` (étend `TCPDI`).

**Correction du bug `ColorFlag`** :
Quand une couleur CSS `background-color` correspondait à la couleur de texte, TCPDF ne générait pas les opérateurs `q/Q` de changement d'état graphique. Résultat : le texte passait en noir après un saut de page automatique. Les classes InfraS forcent `$this->ColorFlag = true` dans les méthodes surchargées :

| Méthode | Rôle |
|---------|------|
| `setColor()` | Force `ColorFlag = true` après chaque appel parent |
| `setSpotColor()` | Idem |
| `setGraphicVars()` | Critique lors des changements de page |

**Mécanisme z-order (filigrane en arrière-plan)** :

| Méthode | Description |
|---------|-------------|
| `liftPageContent()` | Sauvegarde et vide le contenu de page courant (permet d'insérer le filigrane/en-tête derrière) |
| `dropPageContent($saved)` | Réinsère le contenu sauvegardé après le filigrane (texte en avant-plan) |

Patron utilisé dans les ~24 modèles PDF aux changements de page :
```php
$savedContent = method_exists($pdf, 'liftPageContent') ? $pdf->liftPageContent() : '';
pdf_InfraSPlus_bg_watermark(...);
$this->_pagehead(...) ou $this->_pagesmallhead(...);
if ($savedContent !== '' && method_exists($pdf, 'dropPageContent')) {
    $pdf->dropPageContent($savedContent);
}
```

**Priorité d'instanciation** dans `infraspackplus.pdf.lib.php` :
`TCPDI_InfraS` → `TCPDF_InfraS` → `TCPDI` → `TCPDF`

### Ajout du support d'une nouvelle version Dolibarr (Adding support for new Dolibarr versions)

Pour supporter une nouvelle version majeure de Dolibarr (ex. 24.x) :

1. Créer le répertoire : `substitutionpages/dlb250x/`
2. Copier le contenu du dossier de la version précédente : `cp -r dlb240x/* dlb250x/`
3. Si la distribution DolInfraS est ciblée : créer aussi `dlb250x-DolInfraS/`
4. Vérifier et adapter les évolutions des pages core Dolibarr en amont (`societe/contact.php`, `admin/dict.php`)
5. Mettre à jour `docs/changelog.xml` :
   ```xml
   <Version Number="X.Y.Z" MonthVersion="YYYY-MM">
       <change type='add'>Compatibilité avec Dolibarr v24</change>
   </Version>
   <Dolibarr minVersion="21.0.0" maxVersion="24.0.x"/>
   ```
6. Tester la redirection des pages de substitution et le fonctionnement de la génération PDF
