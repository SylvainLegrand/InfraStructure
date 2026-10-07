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
- Dernière version locale : `21.11.2` (2026-10)
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

1. Chargement SQL module (`_load_tables`, dont `llx_infraspackplus_pdf_params`)
2. Synchronisation de ressources (polices, templates selon version)
3. Restauration des paramètres sauvegardés (`infraspackplus_restore_module`)
4. Migrations : table `societe_address`, puis constantes `INFRASPLUS_PDF_PARAMS_*_DOC|CUST|USER_*` vers `llx_infraspackplus_pdf_params` et purge des réglages orphelins (v21.11.0)
5. Activation des modèles et mécanismes liés

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue la migration des constantes de réglages PDF restantes vers la table, la sauvegarde du module, le nettoyage des constantes et le retrait des éléments injectés par le module. La table `llx_infraspackplus_pdf_params` n'est pas détruite (données de documents).

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
- `llx_infraspackplus_pdf_params` — réglages PDF enregistrés par document, client ou utilisateur (depuis v21.11.0, voir la note technique *Réglages PDF par document, client et utilisateur*)

Éléments SQL importants :

- `llx_societe-logo_emet.sql` (colonne `logo_emet`),
- `llx_infraspackplus_pdf_params.sql` / `.key.sql` (table des réglages PDF : clé unique `entity, element, scope, fk_object`, index `element, fk_object`),
- `data.sql` (constantes module et données dictionnaires),
- `updates.sql` (évolutions),
- `clean_from_infraspack.sql` (migration/historique).

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `INFRASPLUS_*` (famille principale de paramètres d’affichage et de génération),
- constantes liées aux options de documents (CGV/CGA/CGI, signatures, images, colonnes),
- constantes liées aux dictionnaires de mentions/notes,
- constantes de versions/migrations utilisées au chargement du module.
- `INFRASPLUS_PDF_PARAMS_<element>_TYPE` : seuls réglages PDF encore en constante (un par type de document). Les portées document / client / utilisateur sont en table depuis v21.11.0 : ne jamais recréer de constante `INFRASPLUS_PDF_PARAMS_*_DOC|CUST|USER_*`, passer par `infraspackplus_getPdfParams()` / `infraspackplus_setPdfParams()`.

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
2. Vérifier tables et dictionnaires (`mention`, `note`, `societe_address`, `pdf_params`)
3. Vérifier chargement des modèles PDF InfraSPlus
4. Vérifier hooks de génération (`formBuilddocOptions`, `beforePDFCreation`, `afterPDFCreation`)
5. Vérifier un cas de génération réel (devis/facture) avec options actives

## Points d’attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infraspackplus_getLocalVersionMinDoli`)
- L’extension PHP XML est nécessaire
- Le module applique des substitutions de pages selon version Dolibarr (répertoire `substitutionpages/`)
- Les constantes `INFRASPLUS_*` sont nombreuses ; éviter les changements massifs sans test de génération PDF
- **Valeurs `DOUBLE` lues en base** : le pilote mysqli renvoie les colonnes `DOUBLE(24,8)` (montants, prix, taux) en **chaîne** — un zéro arrive comme `"0.00000000"`, vrai en PHP. Caster en `(float)` avant tout `empty()` / `!$x` / `?:` sur une valeur issue d'un `fetch_object()` (cf. colonne « P.U. TTC » à 0,00, fix v21.8.8 / InfraSProject 21.1.11).
- **Identifiants `rowid` lus en base** : renvoyés en **chaîne** par le pilote (`$obj->rowid`, `fetch_array()['rowid']`, donc aussi `Address->id`). Ne jamais les comparer en `===` à un `GETPOSTINT()` ou à un littéral entier — caster `(int)` des deux côtés. Attention aussi aux défauts en chaîne (`GETPOSTINT('x') ?: '-2'`) qui rendent toujours fausse une comparaison stricte à `-2` (cf. sélecteurs d'adresses jamais présélectionnés, fix v21.9.3).
- **Propriété `$sign` (avoir affiché en positif) : uniquement sur les modèles de facture** (`F`, `FL`, `FT` : `public $sign = 1`, -1 pour un avoir). Les modèles commande, devis et fournisseurs n'ont pas cette propriété : y passer `1` explicitement à `pdf_InfraSPlus_normalizeTotals()`, jamais `$this->sign` (fix v21.8.11).
- `infraspackplus_test_php_ext()` (appelée par le constructeur du descripteur) n'écrit la constante partagée `INFRAS_PHP_EXT_XML` que si sa valeur change (depuis 21.10.2) : la réécriture systématique (DELETE + INSERT dans `llx_const`) pouvait entrer en conflit avec une transaction concurrente (incident d'octobre 2026 avec Infrastructure : lignes de document perdues en silence). Ne jamais réintroduire d'écriture inconditionnelle de constante dans du code exécuté à chaque requête, à chaque connexion ou pendant une transaction métier
- **Fichiers SQL du module (`sql/llx_*.sql`)** : jamais de commentaire `--` en fin de ligne sur la **dernière colonne** d'un `CREATE TABLE`. `run_sql()` (`core/lib/admin.lib.php`) ne retire un commentaire de fin de ligne que s'il suit `,`, `;`, `)` ou les lettres `E R L T 0` ; ailleurs le commentaire avale la fin de l'instruction une fois les lignes concaténées (erreur de syntaxe, table jamais créée — incident 21.11.0 sur rgenergies, ansemble et dolinfras, voir note technique *Réglages PDF…*). Le client `mariadb` lit ces commentaires correctement : un test manuel du fichier ne révèle pas le défaut, seul `run_sql()` ou une activation réelle le fait.
- **Réglages PDF par document / client / utilisateur** : table `llx_infraspackplus_pdf_params` depuis v21.11.0, jamais en constantes (chez Kytom : 14 168 constantes, 89 % de `llx_const`, chargées à chaque requête, jamais purgées). `init()` rejoue systématiquement le dernier `update.<entité>` : un `activateModule()` en CLI sans `remove()` préalable restaure des valeurs potentiellement anciennes (fichier `www-data`, non réécrivable en CLI) — vérifier la date du dump avant toute réinitialisation hors interface.

## Dernières mises à jour (Recent updates)

Voir `docs/changelog.xml` pour l'historique complet des versions.

## Notes techniques (Technical notes)

### Substitution de pages vs hooks

Comme InfraSCusPrice, InfraSPackPlus utilise la **substitution de pages** pour certaines pages Dolibarr :
- **Pages substituées** : `societe/contact.php` (contacts société), `admin/dict.php` (dictionnaires admin), `compta/paiement/cheque/card.php` (fiche bordereau de remise de chèques) et `admin/chequereceipts.php` (gestion des modèles de documents + specimen pour les bordereaux de chèques, cf. ci-dessous)
- **Constante d'activation** : générée dynamiquement depuis le chemin (ex. `/societe/contact.php` → `INFRASPACKPLUS_PS_ACTIVE_SOCIETE_CONTACT`)
- **Branches maintenues** : `dlb210x`, `dlb220x`, `dlb220x-DolInfraS`, `dlb230x`, `dlb240x` (5 branches dont 1 variante DolInfraS)
  - `admin/chequereceipts.php` couvre les 5 branches, chacune construite à partir d'une source Dolibarr stock fournie et vérifiée par diff avant intégration :
    - v22 standard vs instance de développement (DolInfraS) : **strictement identique**.
    - v21 standard vs v22 standard : 2 différences cosmétiques/sans impact — plage de dates du copyright, et `$form->textwithpicto('', $htmltooltip, 1, 0)` (v21) au lieu de `... 1, 'info')` (v22) sur l'exemple de numérotation (tableau natif, pas la partie ajoutée par InfraS).
    - v23 standard vs v22 standard : 2 différences — lien « retour à la liste des modules » modernisé (`dolBuildUrl()` + icône + libellé masqué sur mobile) et lecture `$conf->global->CHEQUERECEIPTS_ADDON` remplacée par `getDolGlobalString('CHEQUERECEIPTS_ADDON')` (l'écriture par défaut en tête de fichier reste `$conf->global->... = ...`, seule la lecture change).
    - v24 standard vs v23 standard : **strictement identique** (mêmes 2 différences par rapport à v22, aucune nouveauté v24) — `dlb240x` est une copie conforme de `dlb230x`.
  - Pour toute future montée de version majeure (v25+), reprendre cette même méthode : diff systématique de la source stock fournie contre la version déjà intégrée la plus proche, avant de reporter uniquement les différences réelles sur la variante de substitution — ne jamais supposer l'absence de différence sans vérifier.
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

### Modèle de bordereau de remise de chèques (InfraSPlus_BC)

Modèle PDF `core/modules/cheque/doc/pdf_InfraSPlus_BC.modules.php` (classe `pdf_InfraSPlus_BC` extends `ModeleChequeReceipts`) au standard InfraS (logo, en-tête avec bloc Réf/Date/Propriétaire/Compte à droite sous le titre, tableau des chèques, zone de signature, pied de page). Réutilise les réglages du module (police, couleurs, bordures `tblLineStyle`/`verLineStyle`, coins arrondis, `ht_top_table`, filigrane, image de pied, options « Corps / colonnage »). Devise affichée après chaque montant ; cadre du tableau ajusté au contenu (méthode `_tableau()` appelée par page, comme les modèles commerciaux).

**Particularité** : le sous-système chèque du core Dolibarr est non standard, ce qui neutralise les mécanismes habituels :
- `ModeleChequeReceipts::liste_modeles()` renvoie `array('blochet')` en dur (ignore la table `llx_document_model`) ;
- `RemiseCheque::generatePdf()` charge en dur `/core/modules/cheque/doc/pdf_<model>.class.php` (classe `BordereauCheque<Model>`), sans `dol_buildpath` ni `commonGenerateDocument()` ;
- `compta/paiement/cheque/card.php` n'initialise **lui-même** aucun hook, et `showdocuments()` n'expose pas de point d'injection de la liste des modèles ; ⚠️ **nuance** : `showdocuments()` (core, `html.formfile.class.php`) initialise en interne le contexte `formfile` (`initHooks(['formfile'])`) et y déclenche bien `formBuilddocOptions`/`showDocuments`/`formattachOptions` — donc le formulaire générique d'options avant génération d'`infraspackplus` **s'affiche** sur cette page (cf. fix ci-dessous). Seul `doActions` (contexte différent, jamais initialisé par cette page) ne se déclenche pas — la conclusion sur `INFRASPLUS_PDF_SEMIAUTOUPDATE` (cf. section dédiée plus bas) reste donc valide.

**Solution retenue (no-core)** : **substitution** de `compta/paiement/cheque/card.php` (dossiers `substitutionpages/dlb{XX}0x{-DolInfraS}/compta/paiement/cheque/card.php`). La page substituée :
1. injecte l'option `InfraSPlus_BC` dans le `<select name="model">` (post-traitement de la sortie de `showdocuments()`, avec garde anti-doublon `strpos(..., '>InfraSPlus_BC<')`) ;
2. génère le PDF via un helper local `infraspackplus_bc_generatePdf()` qui charge le modèle custom (`dol_buildpath` + `write_file`) et délègue au natif `$object->generatePdf()` pour `blochet`.

**Constante d'activation** : `INFRASPACKPLUS_PS_ACTIVE_COMPTA_PAIEMENT_CHEQUE_CARD` (posée dans `data.sql`).

Le modèle suit pourtant la convention générique `pdf_<model>.modules.php` / classe `pdf_<model>` (comme tous les modèles InfraS) ; c'est uniquement le chargeur core non standard qui impose la substitution au lieu du mécanisme générique `commonGenerateDocument()`.

### Retrait complet des patches core RemiseCheque + admin/chequereceipts.php + modules_chequereceipts.php (fix v21.4.2)

**Symptôme initial** : erreur SQL fatale `Unknown column 'bc.model_pdf'` (`DB_ERROR_NOSUCHFIELD`) à **chaque** affichage d'une fiche bordereau de remise de chèques, empêchant toute consultation et toute génération de document.

**Cause** : `compta/paiement/cheque/class/remisecheque.class.php` (core) avait été patché avec plusieurs ajouts jamais opérationnels ou devenus incompatibles entre eux :
- une propriété `$model_pdf` alimentée par `bc.model_pdf`, colonne jamais créée sur `llx_bordereau_cheque` (aucune migration, ni core ni module) — l'écriture correspondante (`setDocModel()`) était d'ailleurs restée commentée dans les 5 pages de substitution, la fonctionnalité n'avait donc jamais fonctionné ;
- une propriété `$account` (objet `Account` complet), `fetch_lines()`/`$lines`/la classe `RemiseChequeLigne`, et un `generatePdf()` détourné vers `commonGenerateDocument()` — ajoutés pour les besoins d'affichage de `pdf_InfraSPlus_BC` (titulaire/IBAN/compte, détail des chèques, chargement du modèle) ;
- `core/modules/cheque/modules_chequereceipts.php` (3ᵉ fichier core, non détecté au premier passage) avait sa méthode **abstraite** `ModeleChequeReceipts::write_file()` réécrite à la convention moderne, pour que `pdf_InfraSPlus_BC` satisfasse le contrat abstrait — mais rendant du même coup `BordereauChequeBlochet` (modèle natif `blochet`, convention historique à 4 paramètres) incompatible avec sa propre classe parente, provoquant une erreur fatale PHP (`Declaration of ... must be compatible with ...`) dès que `generatePdf()` la chargeait.

**Correctif** : les trois fichiers (`remisecheque.class.php`, `modules_chequereceipts.php`, et le fichier compagnon core-tree `core/modules/cheque/doc/pdf_blochet.modules.php` créé par InfraS puis supprimé) sont désormais **strictement identiques au core Dolibarr standard**. Toute la logique est reprise de façon autonome côté module, dans `pdf_InfraSPlus_BC::write_file()` :
```php
// Compte bancaire, à partir de la propriété native $object->account_id
$this->account = new Account($this->db);
if (!empty($object->account_id)) {
    $this->account->fetch($object->account_id);
}
// Détail des chèques, à partir de l'id natif $object->id (ou 2 lignes de démo si $object->specimen)
// -> $this->lines (array de stdClass, pas de classe dédiée nécessaire)
// Convention de write_file() alignée sur l'abstrait stock : write_file($object, $_dir, $number, $outputlangs)
// ($_dir/$number non utilisés, la classe calcule son propre chemin à partir de $object, comme BordereauChequeBlochet)
```
Les 2 points d'appel adaptés à cette convention : `infraspackplus_bc_generatePdf()` (`infraspackplus.lib.php`) et l'action `specimen` de la page de substitution admin. Au passage, correction d'un bug latent dans `infraspackplus_bc_generatePdf()` : l'appel `write_file(...)` passait les arguments dans le désordre d'une ancienne convention, faisant que la langue explicitement demandée pour un document multilingue était silencieusement ignorée.

**Leçon** : avant de revenir au core stock sur une méthode qui **implémente une interface/classe abstraite**, vérifier systématiquement l'ensemble de la hiérarchie de classes (parents ET soeurs qui implémentent la même abstraction) — un patch peut être réparti sur plusieurs fichiers co-dépendants sans qu'aucun ne le signale individuellement. `grep -rn "InfraS" <répertoire>` sur tout le sous-arbre concerné (pas seulement le fichier qu'on modifie) révèle ce genre de patch dispersé.

**admin/chequereceipts.php retiré du core** : cette page gère des actions d'administration (enregistrement/désenregistrement de modèles via `addDocumentModel()`/`delDocumentModel()`, choix du modèle par défaut, génération d'un PDF spécimen) sans aucun point d'extension par hook — comme `card.php`, elle ne peut pas être rendue autonome par simple chargement côté module. Retirée du core par **substitution de page** vers `substitutionpages/<branche>/admin/chequereceipts.php`, activée par la nouvelle constante `INFRASPACKPLUS_PS_ACTIVE_ADMIN_CHEQUERECEIPTS` (`data.sql`, active par défaut — même mécanisme que `admin/dict.php`). Le tableau « Modèles de documents » de cette page recense à la fois la convention historique (`pdf_<model>.class.php` / `BordereauCheque<Model>`) et la convention moderne InfraS (`pdf_<model>.modules.php` / `pdf_<model>`) :
```php
if (preg_match('/^pdf_.*\.modules\.php$/i', $file)) {
    $name      = substr($file, 4, strlen($file) - 16);
    $classname = 'pdf_'.$name;
} elseif (preg_match('/^pdf_.*\.class\.php$/i', $file)) {
    $name      = substr($file, 4, strlen($file) - 14);
    $classname = 'BordereauCheque'.ucfirst($name);
} else {
    continue;
}
```

**Filtrage et présélection du modèle sur card.php** : le menu de sélection de `compta/paiement/cheque/card.php` respecte désormais l'activation/désactivation choisie dans `admin/chequereceipts.php` (colonne « Status », table `llx_document_model`) et présélectionne correctement le modèle configuré par défaut (colonne « Default », constante `CHEQUERECEIPT_ADDON_PDF`) — le core codant en dur `blochet` dans `ModeleChequeReceipts::liste_modeles()` (`array('blochet' => 'blochet')`, ignore volontairement `llx_document_model`, particularité connue du sous-système chèque) et l'injection d'`InfraSPlus_BC` (mécanisme no-core de `card.php`) ne consultaient ni l'un ni l'autre. Point technique retenu : `Form::selectarray()` (core) marque son unique entrée native comme `selected` par défaut dès qu'aucune correspondance exacte n'est trouvée, indépendamment de la valeur transmise — la présélection ne peut donc pas être pilotée en amont de façon fiable ; `card.php` **normalise après coup** (retire tout `selected` généré, puis le réapplique une seule fois sur l'option correspondant réellement à `CHEQUERECEIPT_ADDON_PDF`). **Repli de compatibilité** : si `llx_document_model` ne contient aucune ligne pour `chequereceipt` (aucun modèle jamais configuré), les deux modèles restent proposés comme avant, pour ne pas rendre la génération soudainement indisponible sur des instances qui n'ont jamais utilisé ce tableau.

**État** : `remisecheque.class.php`, `admin/chequereceipts.php` et `core/modules/cheque/modules_chequereceipts.php` sont 100% stock sur cette instance (`core/modules/cheque/doc/` ne contient plus que le fichier stock `pdf_blochet.class.php`) ; toute la logique InfraS vit dans le module.

### Génération dès le brouillon, réglage semi-automatique, alias, couverture multi-branches (fix/add v21.5.0)

**Affichage dès le brouillon** : le bloc « Fichiers joints / Générer un document » de `card.php` est désormais affiché dès le brouillon ("à valider"), pas seulement une fois le bordereau validé — alignement sur le comportement des devis/commandes/factures (le core réservait ce bloc au statut validé pour ce sous-système uniquement). Guard `if ($object->statut == 1)` remplacé par un bloc toujours actif.

**Effet de bord traité — migration du dossier de documents** : `RemiseCheque::validate()` (core, non touché) change la référence de `(PROVxxx)` vers la référence définitive **sans renommer le dossier de documents**, à la différence de `Facture::validate()`/`Commande::validate()`/`Propal::validate()`. Sans traitement, tout document généré en brouillon serait resté orphelin sous l'ancien dossier `PROVxxx`. L'action `confirm_validate` de `card.php` capture la référence provisoire avant `$object->validate($user)`, puis renomme le dossier `checkdeposits/<oldref>` vers `checkdeposits/<newref>` et les fichiers qu'il contient — même motif que `Facture::validate()`, adapté au dossier `checkdeposits`.

**Réglage « Génération semi-automatique »** : vérification de la compatibilité entre `RemiseCheque` et `INFRASPLUS_PDF_SEMIAUTOUPDATE` (cf. section *Hook doActions*) a révélé une double incompatibilité — `actions_infraspackplus::doActions()` dispatch entièrement par `instanceof` (`Propal`, `Commande`, `Facture`, `Contrat`, `Fichinter`, `Expedition`, `Reception`, `Delivery`, `SupplierProposal`, `CommandeFournisseur`, aucune branche `RemiseCheque`) et `card.php` n'appelle de toute façon jamais `$hookmanager->initHooks()`/`executeHooks('doActions', ...)`. Or `card.php` générait malgré tout un PDF automatiquement à la validation (`confirm_validate`) et à la création directement validée (`create`), **sans jamais consulter** ce réglage — exception à la politique choisie par l'admin sur les autres types de documents. Les deux appels à `infraspackplus_bc_generatePdf()` concernés sont désormais conditionnés à `getDolGlobalInt('INFRASPLUS_PDF_SEMIAUTOUPDATE', 0)`. L'appel de génération manuelle explicite (action `builddoc`, bouton « Générer ») reste inconditionnel, comme pour tous les autres types de documents ; la migration de dossier ci-dessus aussi, puisqu'elle protège des documents pouvant avoir été générés manuellement en brouillon indépendamment du réglage semi-auto.

**Option « Inclure les Alias dans le nom des tiers »** : cette case, affichée pour tous les objets sauf `product`/`mo`/`bom` (`formBuilddocOptions()`, `actions_infraspackplus.class.php`), apparaissait aussi pour les bordereaux de chèques — qui n'ont pas de tiers, l'option n'avait donc aucun effet possible. `chequereceipt` ajouté à l'exclusion. **Point technique retenu** : `showdocuments()` (core, `html.formfile.class.php`) initialise en interne le contexte `formfile` (`initHooks(['formfile'])`) et y déclenche bien `formBuilddocOptions`/`showDocuments`/`formattachOptions`, même quand `card.php` n'appelle lui-même aucun hook — ne pas conclure qu'un hook ne se déclenche jamais sur une page sans vérifier si une fonction **appelée par** cette page n'initialise pas elle-même un autre contexte ; vérifier empiriquement (appel direct en CLI) plutôt que déduire de la seule lecture du code du délégant. (La même option était par ailleurs affichée mais sans effet sur le PDF **projet** — `pdf_InfraSPlus_PJ.modules.php` n'utilisait pas le point d'entrée commun `pdf_InfraSPlus_Build_Third_party_Name()` comme les autres modèles InfraSPlus ; corrigé de la même volée, sans lien avec les bordereaux de chèques.)

**Couverture des 5 branches** : la page de substitution `admin/chequereceipts.php` et l'ensemble des correctifs `card.php` ci-dessus couvrent désormais les 5 branches (`dlb210x`, `dlb220x`, `dlb220x-DolInfraS`, `dlb230x`, `dlb240x`), à partir de sources stock fournies et vérifiées par diff pour chaque version (cf. « Branches maintenues » ci-dessus pour le détail des différences stock relevées par version). Les correctifs `card.php`, développés d'abord uniquement sur `dlb220x-DolInfraS`, ont été reportés sur les 4 autres branches par recherche/remplacement ciblé sur le code pré-correctif, en préservant les différences stock propres à chaque version (classes CSS, `main_checkbox_left_column`, échappement `dolPrintHTML()`, etc. — vérifié par diff après coup : aucun correctif manquant).

**Règle à retenir (pages substituées multi-versions)** : toute correction apportée à une page substituée existant en plusieurs variantes de version (`card.php`, `admin/chequereceipts.php`, `admin/dict.php`, `societe/contact.php`) doit être reportée sur **toutes** les branches concernées dans la foulée, pas seulement sur celle de l'instance de développement — sinon les autres branches accumulent une dette de synchronisation invisible tant que personne ne les compare explicitement.

**Règle à retenir** : pour toute nouvelle propriété/donnée nécessaire à un modèle PDF InfraS sur un objet dont le core ne l'expose pas nativement, préférer un chargement autonome côté modèle PDF (à partir d'un identifiant déjà natif comme `id` ou `account_id`) plutôt qu'un patch de la classe core — évite la dérive silencieuse (colonne DB jamais créée, écriture jamais branchée) et réduit la surface à rebaser lors des montées de version Dolibarr.

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
- Nettoie la variable de session `$_SESSION['InfraSPackPlus_model']` et lève le drapeau `infraspackplus_isInfraSPlusPdfGeneration(false)`
- Restaure les totaux exacts du document via `pdf_InfraSPlus_restoreTotals($parameters['object'])` (voir *Totaux PDF cohérents, fix v21.8.10*) : les modèles les ont remplacés en mémoire par les totaux comptables arrondis pendant la génération
- Génère la documentation technique séparée (`infraspackplus_build_documentation_pdf()`) si l'option `docseparate` a été demandée
- Nettoie la variable de session `$_SESSION['InfraSPackPlus_model']` et lève le drapeau `infraspackplus_isInfraSPlusPdfGeneration(false)`
- Restaure les totaux exacts du document via `pdf_InfraSPlus_restoreTotals($parameters['object'])` (voir *Totaux PDF cohérents, fix v21.8.10*) : les modèles les ont remplacés en mémoire par les totaux comptables arrondis pendant la génération
- Génère la documentation technique séparée (`infraspackplus_build_documentation_pdf()`) si l'option `docseparate` a été demandée

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

**Filtrage des lignes spéciales d'autres modules** : InfraSPackPlus filtre déjà les lignes Infrastructure, Subtotal ATM et Ouvrage via `infraspackplus_isInfrastructureLine($line) || $isATMLine || $isOuvrageLine → return 0`. Les modules propriétaires de ces lignes (notamment infrastructure pour ses titres/sous-totaux/textes libres) sont responsables du rendu et de leur propre cellule Opt — le sous-hook d'enrichissement n'est pas appelé pour ces lignes côté InfraSPackPlus. Les lignes du module natif Sous-totaux de Dolibarr (`SUBTOTALS_SPECIAL_CODE`, constante définie par le core depuis la 22) ne sont pas filtrées : elles restent dans le pipeline InfraSPackPlus et sont rendues par le template core `core/tpl/subtotal_view.tpl.php`, inclus via une closure liée à `$object` (`Closure::call`) parce que ce template utilise `$this` pour l'objet métier. Un `require` direct depuis le hook ferait pointer `$this` sur la classe de hook et provoquerait une erreur fatale (fix v21.8.7).

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

Le trigger reçoit tous les événements et agit sur :

| Événement | Condition | Action |
|-----------|-----------|--------|
| `*_DELETE` (tout objet, hors lignes `*det` / `*ligne` / `*line`) | `$object->id` renseigné | Supprime les réglages PDF de portée `doc` de l'objet (`infraspackplus_deletePdfParams($object->element, 'doc', $id)`) — v21.11.0 |
| `COMPANY_DELETE` | Toujours | Supprime les réglages PDF de portée `cust` du tiers, puis toutes les adresses secondaires liées via `Address::fetch_lines()` + `Address::delete()` (cascade en PHP) |
| `USER_DELETE` | Toujours | Supprime les réglages PDF de portée `user` de l'utilisateur — v21.11.0 |
| `COMPANY_CREATE` | `INFRASPLUS_PDF_SET_LOGO_EMET_TIERS` activé | Associe un logo émetteur au tiers via `infraspackplus_setLogoEmet()` |

**Point de vigilance (depuis v21.5.5)** : `runTrigger()` est appelé par Dolibarr pour **tous** les événements métier — certains objets passés (ex. `TPropaleHist`, historique de devis) n'exposent pas de propriété `element`. La garde `empty($object->element)` en tête de méthode est nécessaire pour sortir immédiatement (`return 0`) sans avertissement PHP « Undefined property » sur ces objets ; elle précède désormais les suppressions de réglages PDF puis le test `in_array($object->element, ['societe'])` du bloc historique.

### Structure du changelog (Changelog structure)

```xml
<changelog>
  <Version Number="21.9.2" MonthVersion="2026-09">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
  </Version>
  <InfraS Downloaded="20260909"/>
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
    0 => "21.9.1",         // Version courante
    1 => "18.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (-1 = KO, 0 = OK)
    3 => <SimpleXMLElement>, // Liste des versions (ou message d'erreur)
    4 => "24.x.x",           // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

### Images médias dans les notes PDF (Media images in PDF notes, fix v21.2.2)

`pdf_InfraSPlus_formatNotes()` (`core/lib/infraspackplus.pdf.lib.php`) embarque les images médias intégrées dans les notes / lignes libres (gestionnaire de médias Dolibarr) directement dans le HTML sous forme de **data-URI base64**, plutôt que de laisser un lien que TCPDF devrait résoudre.

- **Symptôme** : les `<img src="…/viewimage.php?modulepart=medias…file=image/foo.png">` n'apparaissaient pas dans le PDF sur les instances à sécurité renforcée.
- **Pourquoi les approches "chemin" échouent** (toutes testées et écartées) :
  - **URL HTTP** (code d'origine, `convertBackOfficeMediasLinksToPublicLinks()`) : TCPDF doit faire une requête HTTP vers le serveur → **bloquée** par la couche de sécurité.
  - **`file://`** : TCPDF n'accepte un chemin `file://` dans une balise `<img>` HTML que si `setAllowLocalFiles(true)` a été appelé sur l'objet PDF — **jamais le cas** dans le module (`tcpdf.php`, branche `allowLocalFiles && substr($imgsrc,0,7)==='file://'`).
  - **Chemin absolu nu** (`src="/mnt/data/.../medias/foo.jpg"`) : en requête web, le parseur HTML de TCPDF **préfixe `$_SERVER['DOCUMENT_ROOT']`** devant tout chemin commençant par `/` → chemin inexistant ; et le nom de fichier reste **URL-encodé** (ex. `t%C3%A9l%C3%A9chargement.jpg`).
- **Correctif (base64 data-URI)** : un `preg_replace_callback` sur chaque `<img>` ciblant `viewimage.php?...modulepart=medias...` :
  1. extrait le paramètre `file` via `parse_str` (qui **décode l'URL** — gère accents/espaces) ;
  2. résout le répertoire disque via `$conf->medias->multidir_output[$entity]` (multi-entité, comme le core dans `files.lib.php`), repli `DOL_DATA_ROOT/medias` ;
  3. garde anti-path-traversal (refus si `..`) ;
  4. lit le fichier, détermine le MIME via `getimagesize()` (repli `dol_mimetype()`), et remplace le `src` par `data:<mime>;base64,<contenu>`.
  TCPDF traite le data-URI **en amont** de toute logique de chemin/protocole (`tcpdf.php`, branche `^data:image/...;base64,`) → l'image s'affiche **quel que soit le niveau de sécurité** (aucune requête HTTP, aucun `file://`, aucune réécriture de chemin). Dégradation propre : balise laissée inchangée si le fichier est absent/illisible.
- **Différence avec les photos produit natives** : le core insère les photos produit via `$pdf->Image($realpath, x, y, …)` (méthode directe, chemin disque + position fixe), qui contourne nativement ces problèmes. Cette voie est **inutilisable ici** car l'image est noyée dans du HTML libre rendu par `writeHTMLCell()`, à une position dépendant du flux du texte. Le data-URI est l'adaptation du même principe (« donner le fichier disque à TCPDF, pas une URL ») au contexte HTML.

### Affichage conditionnel des extrafields sur PDF (Conditional extrafield rendering, fix v21.2.4)

`pdf_InfraSPlus_ExtraFieldsLines()` et `pdf_InfraSPlus_ExtraFieldsProd()` (`core/lib/infraspackplus.pdf.lib.php`) respectent l'attribut `printable` des extrafields : `0` = jamais ; `1`/`3` = toujours ; `4` (lignes) / `2` (produits) = uniquement si non vide.

- **Symptôme** : un extrafield de **type `text`** réglé sur « si non vide » s'affichait sur **toutes** les lignes du document, y compris les lignes où la valeur était vide (label imprimé avec valeur en gras vide).
- **Cause** : le test de non-vacuité portait sur `$value`, la **sortie HTML rendue** par `showOutputField()`. Pour un extrafield de type `text`, cette sortie est **toujours** enveloppée dans `<div class="shortmessagecut">…</div>` — donc jamais vide au sens de `empty()`. La condition `!empty($value) && $printable == 4` se comportait comme `printable == 1` (toujours afficher).
- **Correctif** : le test porte désormais sur la **valeur brute stockée** `$options_key` (`$line->array_options['options_'.$key]`) au lieu de la sortie formatée : `!empty($options_key) && $printable == 4` (resp. `== 2` pour les produits).
- **Règle à retenir** : pour décider d'afficher ou non un extrafield, tester la **donnée source** (`array_options['options_*']`), jamais le HTML renvoyé par `showOutputField()` (qui peut être non vide alors que la valeur l'est, selon le type d'extrafield).

### Libellé des lignes d'acompte/avoir sur PDF (Deposit/credit-note placeholder label, fix v21.4.1)

`pdf_InfraSPlus_getlinedesc()` (`core/lib/infraspackplus.pdf.lib.php`) reconnaît les placeholders internes du core Dolibarr sur les lignes de remise (`(DEPOSIT)`, `(CREDIT_NOTE)`, `(EXCESS RECEIVED)`, `(EXCESS PAID)`, portés par `desc` quand `info_bits & 2`) pour les remplacer par le texte traduit ("Acomptes issus de la facture X", etc.).

- **Symptôme** : sur une facture avec des lignes d'acompte issues d'une autre facture, le PDF affichait le libellé brut `(DEPOSIT)` au lieu de "Acomptes issus de la facture X".
- **Cause** : une règle générique du module (« pour les lignes de remise sans `label` propre, réutiliser `desc` comme `label` et vider `desc` ») s'exécutait **avant** la reconnaissance des placeholders. `desc` étant vidé, la condition `!empty($desc)` qui déclenche la traduction n'était plus vraie, donc le libellé restait la chaîne brute `(DEPOSIT)`.
- **Correctif** : la règle générique exclut désormais explicitement les 4 placeholders spéciaux du core, qui continuent leur chemin normal jusqu'au bloc de traduction dédié.
- **Règle à retenir** : toute nouvelle règle générique touchant `desc`/`label` des lignes de remise (`info_bits & 2`) doit exclure ces placeholders core, sous peine de casser leur traduction.
- **Complément v21.10.1** : cette règle générique ne s'applique pas quand `INFRASPLUS_PDF_HIDE_LABEL` est actif. Sinon la description, déplacée dans un libellé qui n'est pas imprimé, disparaissait du PDF (ligne de remise InfraSDiscount affichée avec ses montants mais sans texte). Toute règle qui déplace `desc` vers `label` doit vérifier que le libellé sera bien imprimé.

### Alias du tiers absent du PDF projet (Missing thirdparty alias on project PDF, fix v21.5.0)

- **Symptôme** : l'option avant génération « Inclure les Alias dans le nom des tiers » (`PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME`, champ `includealias`) s'affiche dans le formulaire d'options pour tous les types de documents sauf `product`/`mo`/`bom` (`formBuilddocOptions()`, `actions_infraspackplus.class.php:911`) — y compris pour les projets — mais cochée ou non, elle n'avait aucun effet sur le PDF projet.
- **Cause** : tous les autres modèles InfraSPlus affichent le nom du tiers via le point d'entrée commun `pdf_InfraSPlus_Build_Third_party_Name()` (`infraspackplus.pdf.lib.php`), qui ajoute `$thirdparty->name_alias` quand `$includealias` est vrai. `pdf_InfraSPlus_PJ.modules.php` (projet) affichait le nom directement via `$object->thirdparty->getFullName($outputlangs)`, sans jamais passer par cette fonction ni capturer `$hookmanager->resArray['includealias']` — la donnée existe pourtant bien (`Project::fetch_thirdparty()` peuple `$object->thirdparty` en `Societe`, qui porte `name_alias`), ce n'était pas un cas non applicable.
- **Correctif** : ajout de la propriété `$include_alias`, capture de `$hookmanager->resArray['includealias']` aux côtés des autres résultats du hook `beforePDFCreation` (`logo`, `pied`, etc.), et remplacement de l'appel `getFullName()` par `pdf_InfraSPlus_Build_Third_party_Name($object->thirdparty, $outputlangs, $this->include_alias)` — même motif que `pdf_InfraSPlus_D.modules.php` (devis).
- **Règle à retenir** : quand une option avant génération est affichée pour un type de document (`formBuilddocOptions()` ne l'exclut pas), vérifier que le modèle PDF correspondant capture bien la valeur dans `beforePDFCreation` et l'utilise réellement — l'affichage de la case et son application sont deux endroits séparés, qui peuvent diverger silencieusement.

### Case « Alias dans le nom des tiers » affichée à tort sur les bordereaux de chèques (fix v21.5.0)

- **Symptôme** : la case « Inclure les Alias dans le nom des tiers » s'affichait dans le bloc d'options avant génération de `compta/paiement/cheque/card.php`, alors que `RemiseCheque` n'a pas de tiers (`$thirdparty`) — la case n'avait donc jamais aucun effet possible.
- **Cause** : deux fausses pistes explorées avant la bonne :
  1. Cru d'abord que le hook `formBuilddocOptions` ne se déclenchait jamais sur cette page (`card.php` n'appelle lui-même aucun `initHooks()`) — **faux**, cf. correction de la « Particularité » ci-dessus : `showdocuments()` (core) initialise en interne le contexte `formfile` et déclenche `formBuilddocOptions`, qui s'exécute donc bel et bien pour `RemiseCheque`.
  2. Cru ensuite que le filtre par type de document (`in_array($object->element, ['propal', ..., 'expensereport'])`, `actions_infraspackplus.class.php:160`) empêchait tout affichage pour un élément absent de cette liste (`chequereceipt` n'y figure pas) — **faux également** : vérifié empiriquement (`showdocuments()` exécuté en CLI sur un spécimen `RemiseCheque`) que le formulaire s'affiche quand même, et que `$object->element` vaut bien `'chequereceipt'` (pas `'remisecheque'`).
- **Correctif** : la case spécifique à l'alias (`actions_infraspackplus.class.php:911`) exclut déjà `product`/`mo`/`bom` (objets sans tiers classique) — `chequereceipt` ajouté à cette même exclusion :
  ```php
  if (!in_array($object->element, ['product', 'mo', 'bom', 'chequereceipt'])) {
  ```
  Comme pour `product`/`mo`/`bom`, un champ caché `includealias` vide est conservé en repli (`else`) pour ne pas casser la persistance du formulaire — vérifié par test direct (`showdocuments()` sur specimen : le libellé/case disparaît, seul le champ caché subsiste).
- **Règle à retenir** : ne jamais conclure qu'un hook ne se déclenche pas sur une page sans vérifier si une fonction **appelée par** cette page (ici `showdocuments()`) n'initialise pas elle-même un contexte de hook différent de celui de la page. Vérifier empiriquement (appel direct de la fonction en CLI) plutôt que de déduire uniquement de la lecture du code source du delegant.

### Chevauchement des lignes sur le PDF quand la description ne se rend pas (fix v21.5.1)

- **Symptôme** : sur une commande fournisseur (constaté avec `pdf_InfraSPlus_CF.modules.php`, mais le même mécanisme touche les 22 modèles), les lignes du tableau se chevauchaient visuellement — une ligne sur deux remontait vers le haut du document, jusqu'à chevaucher les blocs d'adresse émetteur/destinataire.
- **Cause racine (hors module)** : le hook `pdf_writelinedesc` (contexte `pdfgeneration`) est appelé pour **chaque** ligne, y compris les lignes standards, et plusieurs modules tiers l'implémentent (`ecotax`, `infrastructure`). Le module tiers `ecotax` (`class/actions_ecotax.class.php::shouldChangePdfBehavior()`) recopiait `$db->lasterror()` dans `$this->error` chaque fois que `isEcotaxLine()` retournait `false` — ce qui est le cas normal pour la quasi-totalité des lignes (produit non éco-taxé, ou ligne libre sans `fk_product`). Comme `$db->lasterror()` n'est jamais réinitialisé après une requête réussie, il restait « collé » à une erreur SQL totalement étrangère survenue plus tôt dans la même requête HTTP/CLI (ex. `Table 'xxx.llx_facture_fourn_ventil' doesn't exist`). `HookManager::executeHooks()` (core, `hookmanager.class.php`) force `$reshook = -1` dès qu'un hook a `->error` non vide (`return ($error ? -1 : $resaction);`), même si ce hook a par ailleurs correctement décliné (`return false`/`0`, il ne gérait pas la ligne). `pdf_InfraSPlus_writelinedesc()` (`infraspackplus.pdf.lib.php`) traite `-1` comme `1` (`if (empty($reshook))` est faux dans les deux cas) et ne dessine donc plus rien pour la ligne (description vide).
- **Effet de bord dans les modèles InfraSPlus** : chaque modèle PDF suppose implicitement que l'appel à `pdf_InfraSPlus_writelinedesc()` avance le curseur PDF interne (`$pdf->y`) jusqu'à `$curY + hauteur de la ligne`, et lit ensuite `$pdf->GetY()` pour positionner la ligne suivante (`$nexY`). Quand ce rendu est annulé (bug ci-dessus, ou tout autre hook tiers défaillant à l'avenir), `$pdf->GetY()` reste sur une position obsolète — celle laissée par le bloc de calcul de hauteur de la colonne Référence (`$pdf->startTransaction(); ... $pdf->rollbackTransaction(true);`), qui lisait lui aussi `$pdf->GetY()` au lieu de `$curY` pour son point de départ. La dérive résultante alterne d'une ligne sur deux (rollback restaure l'état *avant* le bloc, qui est déjà désynchronisé de `$curY`), d'où le chevauchement visuel.
- **Correctifs** :
  - `ecotax/class/actions_ecotax.class.php` (module tiers, tags `// InfraS change`) : suppression de la recopie de `$db->lasterror()` — retourner `false` sans toucher à `$this->error`, puisque « pas une ligne éco-taxe » n'est pas une erreur.
  - Les 22 modèles `pdf_InfraSPlus_*.modules.php` (`core/modules/*/doc/`) : ajout de `$pdf->SetY($curY);` juste avant le bloc `// Hauteur de la référence`, pour resynchroniser systématiquement le curseur PDF sur la position réelle de la ligne, quel que soit le sort du rendu de la description. Dans les 18 modèles qui affichent une colonne Référence avec mesure de hauteur par transaction, `$startline = $pdf->GetY();` devient `$startline = $curY;` pour la même raison.
- **Règle à retenir** : ne jamais faire dépendre le positionnement d'une ligne suivante (`$nexY`) du seul `$pdf->GetY()` après un appel externe (hook, fonction tierce) qui peut légitimement ne rien dessiner — resynchroniser explicitement via `$pdf->SetY($curY)` avant toute lecture de `$pdf->GetY()` utilisée pour un calcul de positionnement. Par ailleurs, un hook qui décline (`return 0`/`false`) ne doit jamais renseigner `->error`/`->errors` : `HookManager::executeHooks()` traite la présence de `->error` comme une erreur globale du hook (`$reshook = -1`) indépendamment de la valeur de retour, ce qui peut casser silencieusement le rendu délégué à un tout autre module.

### Cycle de vie du module (Module lifecycle)

**`init()`** effectue dans l'ordre :
1. Copie des polices TCPDF du core vers `DOL_DATA_ROOT/{entity}/infraspackplus/fonts`
2. Copie des polices personnalisées du module
3. Chargement des tables SQL (`_load_tables`, dont `llx_infraspackplus_pdf_params` depuis v21.11.0)
4. Restauration des paramètres sauvegardés (`infraspackplus_restore_module`, qui termine par `infraspackplus_migration_pdf_params()` : un dump antérieur à 21.11.0 recrée des constantes `_DOC_/_CUST_/_USER_`, aussitôt reversées dans la table)
5. Migration de la table `societe_address` si nécessaire
6. Migration des constantes `INFRASPLUS_PDF_PARAMS_*_DOC|CUST|USER_*` vers `llx_infraspackplus_pdf_params` (`infraspackplus_migration_pdf_params()`), puis purge des réglages orphelins (`infraspackplus_purge_pdf_params()`) — v21.11.0
7. Initialisation de `SOCIETE_ADDRESSES_MANAGEMENT` si non défini
8. Enregistrement de `INFRASPLUS_DOL_VERSION` et `INFRASPLUS_MAIN_VERSION`
9. Purge de `MAIN_MODULE_INFRASPACKPLUS_TPL` (constante résiduelle du mécanisme module_parts['tpl'] supprimé en v21.0.0)
10. Appel de `$this->_init()` standard

**`remove()`** effectue :
1. Migration des constantes de réglages PDF restantes vers la table (`infraspackplus_migration_pdf_params()`), pour que la sauvegarde contienne la table et non les constantes — v21.11.0
2. Sauvegarde des paramètres (`infraspackplus_bkup_module` : modèles, constantes, adresses, dictionnaires et table `pdf_params`)
3. Nettoyage SQL : suppression des constantes `INFRASPLUS_%` et `INFRASPACKPLUS_PS_%`, des modèles PDF `InfraSPlus_%`, des constantes `%_ADDON_PDF` liées
4. **DROP TABLE** : `infraspackplus_societe_address`, `c_infraspackplus_mention`, `c_infraspackplus_note` — **pas** `infraspackplus_pdf_params` (données de documents, conservées comme les tables du cœur)
5. Suppression des extrafields via `infraspackplus_search_extf(-1)`

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

### Page blanche dans les visionneuses PDF strictes — z-order vs pied de page (fix v21.5.3)

- **Symptôme** : une page du PDF généré s'affichait entièrement blanche (hormis le logo et le titre du petit en-tête) dans les moteurs PDF **stricts** — PDFium (Edge/Chrome, donc l'aperçu Dolibarr dans ces navigateurs) et Poppler (`pdftoppm` : `Syntax Error: Too few (1) args to 'Tf' operator`) — alors que Firefox (pdf.js, tolérant) affichait la page correctement et que l'extraction de texte restait complète. Bug **déterministe** : régénérer le document reproduisait la corruption à l'octet près.
- **Cause** : cas limite du mécanisme z-order ci-dessus. Quand un contenu HTML volumineux déborde sur **plusieurs pages en un seul appel** `writeHTMLCell` (ex. description de ligne de ~50 ko s'étalant sur les pages 5→6→7), TCPDF ferme la page intermédiaire (6) — **pied de page déjà rendu**, `footerlen[6] > 0` — avant que le modèle ne reprenne la main pour dessiner filigrane et en-tête. `liftPageContent()` extrayait alors le pied de page avec le corps (tout ce qui suit `intmrk`) mais laissait `footerlen` actif : chaque écriture de l'en-tête passait ensuite par la branche « insertion avant le pied de page » de `TCPDF::_out()` (coupe du buffer à `pagelen - footerlen`), qui tombait au milieu d'un opérateur du setup de page tronqué (ex. `BT /F2 9.000000 Tf` coupé en `9.00` + insertion + `0000 Tf`) → flux de contenu invalide, page abandonnée par les moteurs stricts.
- **Correctif** (`liftPageContent()`/`dropPageContent()`, dans les **deux** classes `TCPDF_InfraS` et `TCPDI_InfraS`) : pendant l'extraction, si `footerlen[page] > 0`, sa valeur est sauvegardée dans la propriété `$liftedFooterLen[page]` puis **neutralisée** (`footerlen = 0`) — les écritures de l'en-tête redeviennent de simples appends. `dropPageContent()` restaure `footerlen` après réinsertion du corps (le pied redevient la fin du buffer) et recale `footerpos` selon l'invariant de `setFooter()` : `footerpos = pagelen - footerlen + 1`.
- **Pourquoi le bug était rare** : il faut qu'une page soit fermée par débordement multi-pages *avant* le dessin de son en-tête (débordement d'un seul tenant sur ≥ 2 sauts de page), *et* que la coupe `pagelen - footerlen` du buffer tronqué tombe au milieu d'un jeton (dépend de la longueur des opérateurs du setup de page) — d'où un déclenchement dépendant du contenu exact du document.
- **Méthode de diagnostic réutilisable** : le bug étant déterministe, cloner `htdocs/includes/tecnickcom/tcpdf/` dans un répertoire temporaire, y instrumenter `setPageBuffer()` (détection d'un motif corrompu type `9.00BT` + backtrace) et générer via un script CLI définissant `define('TCPDF_PATH', '<clone>/')` **avant** l'include de `master.inc.php` (`filefunc.inc.php` respecte une constante pré-définie) — zéro impact sur la production.
- **Règle à retenir** : toute manipulation directe des buffers de page TCPDF (`setPageBuffer`) doit maintenir la cohérence des marqueurs internes associés (`footerpos`, `footerlen`, `intmrk`, `bordermrk`, `cntmrk`) — un `footerlen` orphelin ne provoque pas d'erreur immédiate mais corrompt silencieusement le flux à la prochaine écriture via `_out()`.

### Colonne « P.U. TTC » affichant le HT pour les lignes sans quantité (fix v21.5.4)

- **Symptôme** : sur une fiche document (constaté sur devis), la colonne « P.U. TTC » (`linecoluttc`, toujours affichée sur les devis car `card.php` force `$inputalsopricewithtax = 1`) affichait le prix unitaire **HT** pour une ligne libre marquée « Optionnelle » (module infrastructure, `INFRASTRUCTURE_MANAGE_OL`) saisie sans quantité — `qty = 0` donc `total_ttc = 0` en base.
- **Cause (héritée du core Dolibarr, `core/tpl/objectline_view.tpl.php`)** : `subprice_ttc` n'est pas renseigné sur les lignes, le calcul `total_ttc / qty` est court-circuité quand l'un des deux vaut 0, et le repli final affectait la **mauvaise variable** (`$multicurrency_upinctax` au lieu de `$upinctax`) à partir de la **mauvaise source** (`multicurrency_subprice` au lieu de `subprice`) — copier/coller du bloc multi-devises voisin. `$upinctax` restait donc `null` et le `print` retombait sur `$line->subprice` (HT).
- **Correctif** : le repli calcule désormais `$upinctax = price2num($line->subprice * (1 + ($line->tva_tx / 100)), 'MU')` dans les 5 variantes `lineviews` (v21 à v24 + v22-DolInfraS). Les variantes v21/v22 (forme ancienne sans repli) reçoivent en plus un garde `&& $line->qty` sur la division `total_ttc / qty` (sinon `DivisionByZeroError` fatale en PHP 8 quand `MAIN_UNIT_PRICE_WITH_TAX_IS_FOR_ALL_TAXES` est active sur une ligne sans quantité). Même correctif appliqué au template core de l'instance (tags `// InfraS change`) et aux `lineviews`/`lineedits` d'InfraSProject (21.1.5), utilisés quand InfraSPackPlus est inactif.
- **Règle à retenir** : toute correction dans un bloc de calcul d'un template `lineviews` doit être reportée sur les 5 variantes du module **et** vérifiée dans les templates homologues d'InfraSProject (lineviews/lineedits) ainsi que dans le template core d'origine — le même code hérité vit en plusieurs exemplaires.

### Avertissements PHP sur extrafield de livraison libre absent et trigger société (fix v21.5.5)

- **Symptôme 1** : à chaque génération de document via le modèle spécimen interne (`core/modules/specialhead/doc/interne.pdf.head.php`), avertissements PHP `Undefined array key "livr"` / `Undefined array key "options_livr"`, puis (une fois ces deux accès neutralisés) une seconde vague de 14 avertissements `Undefined array key` **dans le core Dolibarr** (`core/class/extrafields.class.php:2080-2093`, méthode `showOutputField()` : `label`, `type`, `size`, `default`, `computed`, `unique`, `required`, `param`, `perms`, `langfile`, `list`, `help`, `cssview`, `alwayseditable`).
- **Cause 1** : `pdf_interne_getAddresses()` lit `$extrafields->attributes[$object->table_element]['printable'][$free_addr_livr]` et `$object->array_options['options_'.$free_addr_livr]` sans vérifier que la clé existe, **et** appelle inconditionnellement `$extrafields->showOutputField($free_addr_livr, ...)` — l'extrafield désigné par la constante `INFRASPLUS_PDF_FREE_LIVR_EXF` (ex. `livr`) n'existe pas ou plus pour ce type d'objet, ni côté module ni côté core (`$this->attributes[$extrafieldsobjectkey]`), et `showOutputField()` (core) ne fait lui non plus aucune vérification d'existence avant de lire ses propriétés.
- **Correctif 1** : ajout d'une garde `isset($extrafields->attributes[$object->table_element]['label'][$free_addr_livr])` **avant** tout traitement — si l'extrafield n'est pas défini pour ce type d'objet, `$free_addr_livr` est mis à `''` directement, sans jamais appeler `showOutputField()` avec une clé inexistante (évite d'atteindre le core, qui reste non modifié).
- **Symptôme 2** : avertissement PHP `Undefined property: TPropaleHist::$element` déclenché par le trigger `Infraspackplustrigger` (cf. section *Trigger* ci-dessus).
- **Cause 2** : le trigger étant appelé pour tous les événements Dolibarr, `runTrigger()` testait `$object->element` sans vérifier au préalable que la propriété existe — certains objets internes (historique de devis, etc.) n'ont pas cette propriété.
- **Correctif 2** : ajout d'un `empty($object->element) ||` avant le test `in_array` (court-circuit, sortie immédiate `return 0`).
- **Règle à retenir** : dans un trigger générique appelé pour tous les événements métier, toujours tester `empty($object->element)` (ou `isset()`) avant toute comparaison sur `$object->element` — ne jamais supposer que l'objet reçu est de type `CommonObject`.

### Quantité masquée sur les lignes « Option » quand infrastructure est actif (fix v21.5.6, affinée en v21.5.7)

- **Symptôme** : la colonne quantité (`linecolqty`) des 5 variantes `lineviews` reste vide pour toute ligne marquée « Option » (`special_code = 3`), y compris les lignes marquées optionnelles via la case « Opt » du module infrastructure (qui pose ce même `special_code = 3`, cf. CLAUDE.md du module infrastructure, section *Colonne « Opt »*).
- **Cause** : les 5 variantes reprennent littéralement la logique cosmétique native Dolibarr (`core/tpl/objectline_view.tpl.php`) qui vide la cellule (`&nbsp;`) dès que `$line->special_code == 3`, sans distinction entre une ligne marquée « Option » nativement et une ligne marquée optionnelle par le module infrastructure.
- **Correctif** : le module infrastructure exclut désormais le montant de ces lignes du sous-total du bloc (`infrastructure_get_totalLineFromObject()`) mais veut pouvoir conserver leur quantité visible individuellement. La condition devient `$line->special_code != 3 || (isModEnabled('infrastructure') && getDolGlobalString('INFRASTRUCTURE_OL_SHOW_DETAILS'))` dans les 5 fichiers `lineviews` (v21.5.7) : la quantité reste affichée quand infrastructure est actif **et** que son option `INFRASTRUCTURE_OL_SHOW_DETAILS` (désactivée par défaut) est cochée, quelle que soit l'origine du `special_code = 3` (case Opt du module ou marquage natif Dolibarr manuel). Sans infrastructure actif, ou avec l'option désactivée, le masquage natif reste inchangé. Même correctif dans les 5 `lineviews` d'InfraSProject (21.1.7/21.1.8, ses lineviews gèrent le rendu quand InfraSPackPlus est inactif).

### Colonne « Num » chevauchant le libellé des titres infrastructure (fix v21.5.8)

- **Symptôme** : avec `INFRASPLUS_PDF_WITH_NUM_COLUMN` actif et `INFRASPLUS_PDF_NUMCOL_REF=1` (valeur par défaut — colonne Num en 1ère position), le libellé des titres de niveau 1 du module infrastructure se superpose visuellement au numéro de ligne (`$i + 1`) imprimé dans la colonne Num.
- **Cause** : dans les 10 modèles `pdf_InfraSPlus_{C,CBL,CP,D,DP,DST,F,FL,OF,OM}.modules.php`, le calcul de `$ref` (contenu de la colonne Num) ne vide cette valeur que pour les titres/sous-totaux du module **Sous-Total (ATM)** (`$isSubTitle`/`$isSubTotal`), jamais pour ceux du module **infrastructure**. La variable `$isInfraSLine` (calculée via `infraspackplus_isInfrastructureLine()`) existait déjà dans 7 de ces 10 modèles mais n'était jamais utilisée dans cette condition (code mort) ; absente des 3 autres (`CBL`, `OM`, `OF`). Cumulé à un bug du **module infrastructure** (`pdfAddTitle()` positionnait le texte du titre à la marge brute au lieu de la position réelle de la Désignation, cf. CLAUDE.md du module infrastructure, fix 21.7.1), les deux textes s'imprimaient au même point X/Y.
- **Correctif** : `$isInfraSLine` ajoutée à la condition qui vide `$ref` dans les 10 modèles, au même titre que `$isSubTitle`/`$isSubTotal` ; ajoutée aux 3 modèles où elle manquait (`$isInfraSLine = infraspackplus_isInfrastructureLine($object->lines[$i]) ? 1 : 0;`, juste après le calcul de `$isSubTotalLine`, même position que dans les 7 autres modèles).
- **Règle à retenir** : toute variable de détection de ligne spéciale (`$isInfraSLine`, `$isSubTitle`, `$isSubTotal`, `$isOuvrage`...) calculée dans un modèle PDF doit être systématiquement recoupée avec les conditions qui pilotent l'affichage de la colonne Num/Réf — une variable calculée mais jamais branchée (code mort) est un piège classique lors de l'ajout d'un nouveau type de ligne spéciale à un modèle existant.

### Colonne « Num » et lignes optionnelles infrastructure — style/couleur (add, version courante)

- Le module infrastructure a ajouté 2 options PDF pour ses lignes marquées « Opt » (extrafield `options_infrastructure_ol`) : `INFRASTRUCTURE_PDF_OL_STYLE` et `INFRASTRUCTURE_PDF_OL_COLOR` (style/couleur appliqués à toutes les colonnes de la ligne en PDF, cf. CLAUDE.md du module infrastructure, section *Style, couleur et détails PDF des lignes optionnelles*). Ces colonnes sont rendues via les hooks Dolibarr standard (`pdf_getline*`), sur lesquels infrastructure peut intervenir directement — **sauf la colonne « Num »**, propre à InfraSPackPlus et rendue sans aucun hook (`$ref = ... $i + 1`, cf. fix v21.5.8 ci-dessus).
- **Nouvelle fonction** `infraspackplus_applyInfrastructureOlPdfStyle(&$pdf, &$object, $i)` (`core/lib/infraspackplus.pdf.lib.php`), sur le même principe que `infraspackplus_isInfrastructureLine()` (inclusion paresseuse de la classe `TInfrastructure`, garde `isModEnabled('infrastructure')`) : détecte l'extrafield `options_infrastructure_ol` sur la ligne (avec `fetch_optionals()` de secours si non chargé) et applique `INFRASTRUCTURE_PDF_OL_STYLE`/`INFRASTRUCTURE_PDF_OL_COLOR` sur le `$pdf` courant, si `INFRASTRUCTURE_PDF_OL_SHOW_DETAILS` est actif.
- Appelée dans les 10 modèles `pdf_InfraSPlus_{C,CBL,CP,D,DP,DST,F,FL,OF,OM}.modules.php`, juste avant le **second** rendu de la colonne Ref/Num (`$pdf->writeHTMLCell($this->tableau['ref']...)`, celui qui suit la réinitialisation systématique de police/couleur `$pdf->SetFont('', '', $default_font_size - 1); $pdf->SetTextColor(...)`) — le premier rendu, dans le bloc `startTransaction()`/`rollbackTransaction()`, ne sert qu'à mesurer la hauteur et n'a pas besoin d'être stylé.
- **Règle à retenir** : toute nouvelle colonne ou tout nouveau style ajouté côté module infrastructure et censé s'appliquer uniformément à **toutes** les colonnes du tableau de lignes doit vérifier si la colonne « Num » (propre à InfraSPackPlus, sans hook Dolibarr) est concernée — dans ce cas, le correctif doit être reporté ici, dans les 10 modèles, et non pas seulement côté module infrastructure.

### Extrafield de livraison libre absent oublié dans la fonction commune, date de livraison absente sur commande fournisseur (fix v21.5.9)

- **Symptôme 1** : avertissements PHP `Undefined array key "livraison"` / `"options_livraison"` à chaque génération de document utilisant l'adresse de livraison libre (option `INFRASPLUS_PDF_FREE_LIVR_EXF`), suivis d'une seconde vague de 14 avertissements dans le core Dolibarr (`core/class/extrafields.class.php:2080-2093`, méthode `showOutputField()`).
- **Cause 1** : le fix v21.5.5 (cf. section *Avertissements PHP sur extrafield de livraison libre absent et trigger société* ci-dessus) n'avait été appliqué qu'au modèle spécimen interne (`core/modules/specialhead/doc/interne.pdf.head.php`). La fonction partagée `pdf_InfraSPlus_getAddresses()` (`core/lib/infraspackplus.pdf.lib.php`), utilisée par tous les autres modèles PDF InfraSPlus, contenait le même code non protégé — elle n'avait simplement pas encore été exercée avec un extrafield de livraison libre configuré mais absent pour le type de document généré.
- **Correctif 1** : même garde que le fix v21.5.5, reportée dans `pdf_InfraSPlus_getAddresses()` : `isset($extrafields->attributes[$object->table_element]['label'][$free_addr_livr])` avant tout accès aux attributs de l'extrafield et avant l'appel à `showOutputField()` ; repli à `''` si l'extrafield n'est pas défini pour ce `table_element`.
- **Symptôme 2** : avertissement PHP `Undefined variable $txtC12b` à la génération d'une commande fournisseur (modèle `InfraSPlus_CF`) sans date de livraison renseignée.
- **Cause 2** : `$txtC12b` n'est affecté que dans un bloc conditionnel (`if (!empty($date_livraison))` pour `CF`), mais lu ensuite inconditionnellement pour composer la ligne de dates d'en-tête (`$txtC12.(empty($this->dates_br) ? ' / '.$txtC12b : '')` et, si `dates_br` actif, une seconde `MultiCell` dédiée). Le même schéma existait, non encore constaté en log, dans 6 autres modèles partageant ce bloc d'en-tête : `pdf_InfraSPlus_CFBL`/`_DF` (date de livraison), `_D`/`_DP`/`_DST` (date de fin de validité), `_F` (date de point de taxe / date d'échéance).
- **Correctif 2** : initialisation de `$txtC12b = '';` avant le premier test susceptible de l'affecter, reportée par cohérence dans les 7 modèles (`CF`, `CFBL`, `DF`, `D`, `DP`, `DST`, `F`) dans la foulée, sans attendre que chaque occurrence remonte individuellement dans les logs.
- **Règle à retenir** : quand un correctif touche un schéma de code partagé par plusieurs modèles PDF InfraSPlus (variable affectée dans un `if` mais lue sans condition), vérifier et corriger tous les modèles porteurs du même bloc dans la foulée plutôt que d'attendre que chaque occurrence remonte individuellement dans les logs.

### Débordement du bloc pied de tableau sur le pied de page — fuite de padding TCPDF (fix v21.5.9, 19 modèles)

- **Symptôme** : sur un devis (constaté sur `pdf_InfraSPlus_D`, mais le même mécanisme touche les 18 autres modèles partageant ce schéma), le bloc affiché sous le tableau des lignes présentait trois défauts visuels simultanés : le libellé « Conditions de règlement » et son texte descriptif n'étaient pas alignés sur la même ligne (texte décalé vers le bas de ~2,7pt) ; la ligne « BIC/SWIFT » se retrouvait imprimée sur la même ligne que le premier texte du pied de page (« Siège social : ... ») au lieu d'être au-dessus, chevauchement confirmé par mesure des coordonnées PDF (`pdftotext -bbox`) ; le cadre de signature ne laissait qu'un espace de ~0,7mm avant la ligne de séparation du pied de page.
- **Cause** : `pdfAddTotal()` (et `pdfAddTitle()`) du module `infrastructure` (`class/actions_infrastructure.class.php`) applique délibérément un padding haut/bas de 1mm aux cellules TCPDF lors du rendu d'un titre ou d'un sous-total, en s'appuyant sur le fait que la ligne **suivante** restaurera ce padding via `pdf_writelinedesc` (mécanisme utilisé pour aligner verticalement le libellé du sous-total avec les colonnes voisines TVA/Total — cf. section *Structure du changelog*). Quand ce titre/sous-total est la **toute dernière ligne** du document (cas courant : un sous-total qui clôt une section), aucune ligne suivante ne vient jamais restaurer le padding, qui reste actif à 1mm pour tout ce qui est dessiné ensuite dans la même génération PDF.
- **Mécanisme de l'écart** : `_tableau_info()` (conditions de règlement + coordonnées bancaires), `_tableau_tot()` (totaux) et `_signature_area()` (cadre de signature) sont chacune appelées deux fois par `write_file()` : une première fois en mode « calcul seul » (`$calculseul=1`, `$pdf->rollbackTransaction(true)`) **avant** la boucle de rendu des lignes — donc avec un padding encore propre — pour mesurer la hauteur à réserver en bas de page ; puis une seconde fois pour de vrai (`$calculseul=0`, `$pdf->commitTransaction()`) **après** cette boucle, avec le padding potentiellement pollué à 1mm si la dernière ligne était un titre/sous-total infrastructure. Chaque `MultiCell`/`writeHTMLCell` dessiné avec ce padding résiduel occupe alors 1 à 2mm de plus que ce qui avait été mesuré, et la hauteur réellement consommée dépasse l'espace réservé (`$bottomlasttab`/`$heightforinfotot`), débordant sur la zone du pied de page.
- **Correctif** : dans les 19 modèles `pdf_InfraSPlus_{D,C,CP,CBC,CBL,OM,OF,F,FL,FT,DP,DST,CF,CFBL,FF,DF,BL,RE,BR}`, chacune des méthodes `_tableau_info()`/`_tableau_tot()`/`_signature_area()` **qui existe dans le modèle concerné** (les modèles factures n'ont pas de zone de signature ; certains modèles commande/propal n'ont pas de méthode `_tableau_tot()` séparée) sauvegarde le padding courant juste après `$pdf->startTransaction();`, le force à `top=0/bottom=0`, puis restaure la valeur d'origine juste après `$pdf->commitTransaction();` (uniquement dans la branche de dessin réel — le rollback de transaction restaure déjà l'intégralité de l'état de l'objet PDF, padding inclus, en mode calcul seul). Ajout en complément d'une constante `MIN_GAP_BEFORE_FOOTER` (0.75mm, ≈ 2px à 96dpi) ajoutée à `$heightforinfotot` avant l'appel `_pagefoot(calculseul=1)`, garantissant un espace plancher entre le bas du dernier bloc dessiné et la ligne de séparation du pied de page, indépendamment de tout futur écart de calcul.
- **Règle à retenir** : toute méthode de mise en page appelée deux fois selon le même patron mesure-puis-dessine (`calculseul=1` avant la boucle de rendu des lignes, `calculseul=0` après) est vulnérable à un état TCPDF résiduel (padding, police, couleur...) laissé par le rendu d'une ligne spéciale de module tiers qui ne se restaure qu'« au début de la ligne suivante » — un mécanisme qui échoue silencieusement dès que cette ligne spéciale est la dernière du document. Pour toute nouvelle méthode de ce type, réinitialiser explicitement l'état sensible en entrée (padding à 0, police/couleur connues) plutôt que de faire confiance à l'état hérité de la boucle précédente.

### Numéro de série reçu et colonne « Reliquat » absents sur le bon de réception — table renommée par Dolibarr 20.0.0 (fix v21.5.11)

- **Symptôme 1** : sur le modèle `pdf_InfraSPlus_RE` (réception), le numéro de lot/série saisi au dispatching (`reception/dispatch.php`) ne s'affichait jamais en fin de description de ligne, quelle que soit la réception.
- **Symptôme 2** : avec l'option `INFRASPLUS_PDF_BR_WITH_REL_COLUMN` active, la colonne « Reliquat » affichait systématiquement `0`, y compris quand la quantité reçue était inférieure à la quantité commandée.
- **Cause commune** : `infraspackplus_get_serialreceived()` et `infraspackplus_get_alreadyreceived()` (`core/lib/infraspackplus.lib.php`) interrogeaient la table `llx_commande_fournisseur_dispatch`, **renommée `llx_receptiondet_batch` depuis la migration Dolibarr 19.0.0→20.0.0** (`htdocs/install/mysql/migration/19.0.0-20.0.0.sql:245`), avec au passage `fk_commande` → `fk_element` et `fk_commandefourndet` → `fk_elementdet` (+ nouvelle colonne `element_type`, valeur `supplier_order` pour les commandes fournisseur — cf. `reception/class/receptionlinebatch.class.php`). La table interrogée n'existant plus sur les instances à jour, `$db->query()` échouait silencieusement et les deux fonctions retournaient un résultat vide (`0` / `[]`) au lieu du résultat attendu.
- **Correctif** : les deux requêtes sont réécrites sur `llx_receptiondet_batch`, filtrées sur `fk_element = <origin_id>` et `element_type = 'supplier_order'`, regroupées/indexées par `fk_elementdet`. Vérifié en base sur une réception réelle (produit avec lot renseigné, quantité partiellement reçue) : le numéro de lot et le reliquat (`qty_asked - SUM(qty reçue toutes réceptions confondues)`) sont désormais correctement récupérés.
- **Symptôme 3 (associé)** : sur ce même modèle, le numéro de série reçu ($serialStd) pouvait rester affiché sur une ligne de réception qui n'en avait pas — variable jamais réinitialisée en tête de boucle par ligne, ne recevant une valeur que dans la branche de correspondance (`$commandefourndet == $fk_commandefourndet`). Corrigé par `$serialStd = '';` avant le test `if (!empty($serialreceived))`.
- **Règle à retenir** : toute fonction InfraS qui requête directement une table core Dolibarr (plutôt que de passer par une classe/méthode native) doit être revérifiée à chaque montée majeure de version Dolibarr — un renommage de table/colonne côté core échoue silencieusement (`$db->query()` retourne `false`, aucune exception), le symptôme visible n'étant qu'une donnée manquante sur le document, sans erreur PHP ni log explicite.

### Filigrane image et conformité PDF/A — police non embarquée injectée par la fusion TCPDI (fix v21.8.1)

- **Symptôme** : avec `PDF_USE_A` actif (PDF/A-1b ou PDF/A-3b, réglage core Dolibarr `admin/pdf.php`) et un filigrane image configuré (`INFRASPLUS_PDF_IMAGE_WATERMARK`, fichier non déjà au format PDF), les documents générés par les modèles InfraSPlus n'étaient en réalité **pas conformes PDF/A**, malgré des métadonnées XMP annonçant une conformité correcte (`pdfaid:part`/`pdfaid:conformance`) — non-conformité invisible à l'affichage (tout lecteur substitue silencieusement la police standard « Helvetica ») mais bloquante pour tout contrôle réel (veraPDF, Adobe Preflight, portail de facturation électronique/archivage légal).
- **Cause** : `pdf_InfraSPlus_bg_watermark()` (`core/lib/infraspackplus.pdf.lib.php`) convertit l'image de filigrane en PDF temporaire de fond (mis en cache dans `DOL_DATA_ROOT/admin/temp/watermark_<md5>.pdf`, régénéré si l'image source est plus récente) en instanciant un `TCPDF` nu, **sans passer le flag PDF/A** au constructeur (7ᵉ paramètre) — contrairement à `pdf_InfraSPlus_getInstance()` (même fichier, fabrique du `$pdf` principal) qui le fait correctement. Ce TCPDF temporaire, généré hors mode PDF/A, embarque donc la police standard « Helvetica » **non embarquée** (TCPDF ne force l'embarquement complet des polices — substitution automatique `helvetica` → `pdfahelvetica` dans `AddFont()` — qu'en mode PDF/A). Cette police orpheline est ensuite recopiée telle quelle dans le document final lors de la fusion TCPDI (`setSourceFile`/`importPage`/`useTemplate`), qui importe intégralement le dictionnaire de ressources de la page source, indépendamment de ce que le document principal utilise réellement.
- **Vérification empirique** : génération de spécimens de facture (`pdf_crabe` core natif vs `pdf_InfraSPlus_F`) avec `PDF_USE_A=3`, comparaison des polices (`pdffonts`) — le modèle core n'a que des polices embarquées, le modèle InfraSPlus (filigrane configuré) contenait un objet `Helvetica` distinct marqué non embarqué. Confirmé en isolant le PDF temporaire du cache filigrane : `pdffonts` y montrait directement `Helvetica ... emb no`.
- **Correctif** : le TCPDF temporaire reçoit désormais le même réglage `PDF_USE_A` (`getDolGlobalInt('PDF_USE_A', 0)`) que le document principal. Vérifié après correctif (et suppression du cache existant, cf. ci-dessous) : plus aucune police non embarquée dans le document final.
- **Point opérationnel** : le correctif ne s'applique pas rétroactivement au cache déjà présent sur disque (`DOL_DATA_ROOT/admin/temp/watermark_*.pdf`) — celui-ci n'est régénéré que si le fichier image source (`filigrane.png` etc.) est modifié après le fichier de cache. Sur une instance déjà affectée, supprimer manuellement ce cache après mise à jour du module pour que les prochains documents générés en bénéficient immédiatement.
- **Règle à retenir** : toute instanciation directe de `TCPDF`/`TCPDI` en dehors de la fabrique commune `pdf_InfraSPlus_getInstance()` (recherche exhaustive : seuls ces deux points dans tout le module au moment du fix) doit reprendre le même réglage `PDF_USE_A` — une instance PDF secondaire créée pour un usage interne (conversion d'image, mesure, gabarit...) et ensuite fusionnée dans le document final via TCPDI hérite silencieusement de tous les défauts de conformité de son propre mode de génération, que TCPDF ne peut pas corriger après coup au moment de la fusion.

### Champ « Format des documents PDF » (PDF_USE_A) dans l'onglet Paramètres Dolibarr (add v21.8.1)

- Ajout, dans `admin/generalpdf.php` (onglet « Paramètres Dolibarr »), du champ natif Dolibarr `PDF_USE_A` (libellé core réutilisé tel quel via `$langs->trans('PDF_USE_A')` — clé déjà traduite dans `admin.lang` des 4 langues du module, aucune nouvelle clé de traduction nécessaire côté InfraSPackPlus) — sélecteur PDF 1.7 standard / PDF/A-1b / PDF/A-3b, identique à celui de `admin/pdf.php` (core). Positionné juste avant le réglage `MAIN_PDF_FORCE_FONT_SIZE` (taille de police de base).
- Intégré au même mécanisme de sauvegarde que les autres réglages natifs de cet onglet : ajouté à `$list['Gen']` (tableau des constantes persistées par l'action `update_Gen`, déclenchée par le bouton « Enregistrer » commun à toute la section), donc aucune logique de sauvegarde spécifique à écrire.
- **Point de vigilance (numérotation)** : les lignes de cet onglet sont numérotées séquentiellement (`$num`, compteur incrémenté par `infraspackplus_print_input()`) et cette numérotation est **documentée 1:1 dans `README.md`** (section « Onglet Paramètres Dolibarr », puces `***N***`). Toute insertion ou suppression d'une ligne dans `admin/generalpdf.php` doit être répercutée par un **décalage de la numérotation de toutes les lignes suivantes** côté code (commentaires `// $num = X` disséminés dans le fichier, purement indicatifs mais à garder synchronisés) **et** côté `README.md` (renumérotation de toutes les puces `***N***`/`***N-M***` suivantes, jusqu'à la fin de cette section précise — les sections numérotées des autres onglets, ex. « Paramètres InfraS », redémarrent à 1 et ne sont pas concernées). Un oubli de renumérotation ne casse rien fonctionnellement (le compteur est recalculé dynamiquement à l'affichage) mais désynchronise silencieusement la documentation utilisateur du `README.md` par rapport à l'ordre réel des options affichées.

### Avertissements PHP « Undefined array key » sur les couleurs de texte d'en-tête/cadres (fix v21.8.2)

- **Symptôme** : à la génération d'un devis (`pdf_InfraSPlus_D`), avertissements PHP `Undefined array key 1` / `Undefined array key 2` sur les appels `SetTextColor()` de `_pagehead()` (`pdf_InfraSPlus_D.modules.php`) et de la fonction commune `pdf_InfraSPlus_writeFrame()` (`core/lib/infraspackplus.pdf.lib.php`).
- **Cause** : les constantes de couleur de texte `INFRASPLUS_PDF_HEADER_TEXT_COLOR`, `INFRASPLUS_PDF_FRM_E_TEXT_COLOR` et `INFRASPLUS_PDF_FRM_R_TEXT_COLOR` étaient enregistrées en base avec la valeur `"0"` au lieu du triplet attendu `"0,0,0"` (vérifié en base sur cette instance). Le code lisait la constante via `getDolGlobalString($key, '0,0,0')` puis faisait un `explode(',', ...)` brut, sans valider le nombre de parties obtenues — sur `"0"`, `explode()` ne retourne qu'un tableau à 1 élément, d'où les avertissements sur les index 1/2 lus ensuite par `SetTextColor()`.
- **Correctif** : remplacement des deux `getDolGlobalString(...); explode(',', ...)` par `colorStringToArray(getDolGlobalString(...), array(0, 0, 0))` (fonction native Dolibarr, `core/lib/functions2.lib.php`) — dans `pdf_InfraSPlus_getValues()` pour `headertxtcolor` (propriété alimentant tous les modèles PDF du module, pas seulement `pdf_InfraSPlus_D`) et dans `pdf_InfraSPlus_writeFrame()` pour `frmeTxtColor`/`frmrTxtColor`. `colorStringToArray()` garantit toujours un tableau à 3 entiers, avec repli sur le défaut fourni en cas de valeur vide ou malformée — même pattern déjà utilisé dans ce module pour `INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR` (cf. changelog).
- **Portée non couverte** : `core/lib/infraspackplus.pdf.lib.php` contient une trentaine d'autres `explode(',', ...)` du même type sur d'autres constantes de couleur (`bg_color`, `tblLineColor`, `verLineColor`, `horLineColor`, couleurs de sous-titre/sous-total, etc.) — non corrigées ici faute de symptôme observé en log sur cette instance (leurs constantes contiennent actuellement des triplets valides). **Règle à retenir** : si un nouvel avertissement `Undefined array key` apparaît sur l'une de ces variables, appliquer le même remplacement par `colorStringToArray($valeur, $repli3entiers)` plutôt que de raisonner au cas par cas — c'est le même défaut structurel, latent tant que la constante correspondante reste valide en base.

### Jeton CSRF manquant sur les liens de suppression d'adresse (fix v21.8.3)

- **Symptôme** : avec `MAIN_SECURITY_CSRF_WITH_TOKEN` ≥ 2 (protection CSRF étendue aux actions GET sensibles), le bouton « Supprimer » d'une adresse (`comm/address.php`) et le picto de suppression de la liste des adresses du tiers (`infraspackplus.lib.php`) étaient bloqués par un 403 `die` de `main.inc.php` — leur action `delete` matche la regex des actions GET sensibles du core.
- **Correctif** : ajout de `&token='.newToken()` aux deux liens. Les liens voisins `create`/`edit` figurent dans la liste d'exclusion du core et n'ont pas besoin de jeton.
- **Règle à retenir** : tout nouveau lien GET dont l'action matche `^(confirm_)?(add|classify|close|confirm|copy|del|disable|enable|remove|set|unset|update|save)` doit inclure `&token='.newToken()` — sans lui, il casse dès que l'instance active `MAIN_SECURITY_CSRF_WITH_TOKEN` à 2 ou plus.

### Avertissement PHP sur le test d'activation de multicompany dans l'extrait de compte tiers (fix v21.8.4)

- **Symptôme** : avertissement PHP `Undefined property: stdClass::$enabled` à chaque génération du modèle `pdf_InfraSPlus_account_statut` (`core/modules/societe/doc/pdf_InfraSPlus_account_statut.modules.php`), sur le test `$conf->multicompany->enabled` (2 occurrences : chargement conditionnel de `dao_multicompany.class.php` en tête de fichier, et suffixe du nom de fichier PDF avec le libellé de l'entité en cas de partage inter-entités du tiers).
- **Cause** : `$conf->multicompany` n'est pas garanti d'exposer une propriété `enabled` quand le module `multicompany` n'est pas activé — c'est un `stdClass` générique dans cet état, contrairement au test générique attendu par la convention Dolibarr.
- **Correctif** : remplacement des deux occurrences par `isModEnabled('multicompany')`, la fonction native Dolibarr de contrôle d'activation d'un module (cf. règle globale *Projets Dolibarr — privilégier les méthodes natives*).
- **Règle à retenir** : ne jamais tester l'activation d'un module via `$conf->nommodule->enabled` — toujours passer par `isModEnabled('nommodule')`.

### Messages parasites « Mauvaise valeur de paramètre » en double quand une validation échoue dans un trigger (fix v21.8.5)

- **Symptôme** : quand la validation d'un document interceptée par le hook `doActions` (mode `INFRASPLUS_PDF_SEMIAUTOUPDATE=1`) échoue à cause d'un trigger tiers qui retourne -1 **sans renseigner** `$object->error` ni `$object->errors` (cas réel : trigger `BILL_VALIDATE` d'infras2bridge en erreur suite à un refus HTTP 400 de l'API Bridge), deux messages parasites identiques s'affichent : « Mauvaise valeur de paramètre. Ceci arrive lors d'une tentative de traduction d'une clé non renseignée. » — masquant la cause réelle de l'échec.
- **Cause** : le schéma `setEventMessages($langs->trans($object->error), null, 'errors')` (branche `else` quand `count($object->errors) == 0`) était répété en 11 exemplaires — 10 branches `instanceof` de `ActionsInfraSPackPlus::doActions()` (`actions_infraspackplus.class.php`) et la fin de `infraspackplus_semiauto_update()` (`infraspackplus.lib.php`). Avec `$object->error` à `null`, `$langs->trans(null)` renvoie la clé littérale `ErrorBadValueForParamNotAString` (`Translate::getTradFromKey()`, garde `!is_string($key)`), poussée en session puis **retraduite** à l'affichage par `get_htmloutput_mesg()` (qui repasse chaque message stocké dans `$langs->trans()`) — d'où le texte français complet. Le doublon vient de l'empilement des deux niveaux (`semiauto_update()` pousse son message, retourne -1, puis `doActions()` pousse le sien).
- **Correctif** : les 11 occurrences testent désormais la valeur avant traduction — `$langs->trans(!empty($object->error) ? $object->error : 'ErrorUnknown')` — avec repli sur la clé core `ErrorUnknown` (`main.lang`, « Erreur inconnue ») pour qu'un échec silencieux reste visible sans message trompeur.
- **Règle à retenir** : ne jamais passer à `$langs->trans()` une valeur potentiellement `null`/non-string (`$object->error` après un échec de trigger n'est pas garanti renseigné — `CommonObject::call_trigger()` ne remplit que `$this->errors`, et seulement si le trigger a peuplé les siens). Tester la valeur et replier sur une clé de traduction réelle ; le résultat de `trans()` étant réaffiché via un second `trans()` par `get_htmloutput_mesg()`, une clé brute stockée en session ressort traduite à l'écran, ce qui rend ce type de défaut difficile à tracer.

### Code comptable client jamais affiché — propriété `Societe::$code_compta` dépréciée (fix v21.8.4)

- **Symptôme** : avec les options `INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT` (n° 44 de l'onglet Paramètres InfraS, « Afficher le code comptable client… ») et `INFRASPLUS_PDF_CODE_CLI_COMPT_FRM` (n° 45, position en en-tête) actives, le code comptable du client n'apparaissait nulle part sur la facture régénérée (constaté sur `pdf_InfraSPlus_F`, Dolibarr 22.0.5) — ni sous les dates de l'en-tête, ni dans le cadre d'adresse destinataire. Même comportement sur les 18 modèles de la chaîne des ventes et l'en-tête spécial `interne.pdf.head.php`. À ne pas confondre avec le **numéro client** (`code_client`, ex. `CU2511-03048`), piloté par les options distinctes n° 42/43 (`INFRASPLUS_PDF_SHOW_NUM_CLI` / `INFRASPLUS_PDF_NUM_CLI_FRM`) — si seules 44/45 sont cochées, l'absence du numéro client est normale.
- **Cause** : les 40 lectures du module (19 modèles PDF + `interne.pdf.head.php` + `pdf_InfraSPlus_build_address()` dans la lib) faisaient `$object->thirdparty->code_compta`. Cette propriété est marquée `@deprecated` dans `Societe` et **n'est plus remplie par `Societe::fetch()`** sur les versions récentes de Dolibarr — seule `code_compta_client` l'est (`$this->code_compta_client = $obj->code_compta;`, sans recopie vers `code_compta`). Vérifié sur une instance Dolibarr 18 du serveur : la ligne de compatibilité `$this->code_compta = $this->code_compta_client;` y est déjà commentée. La valeur lue était donc toujours `null`, la condition d'affichage jamais vraie, sans aucun avertissement PHP (propriété déclarée mais vide) — d'où un bug silencieux.
- **Correctif** : nouvelle fonction commune `pdf_InfraSPlus_getCustomerAccountancyCode($thirdparty)` (`core/lib/infraspackplus.pdf.lib.php`, à côté de `pdf_InfraSPlus_build_IDs()`), qui renvoie `code_compta_client` si renseigné, sinon `code_compta` (repli pour d'anciennes versions), sinon `''`. Les 40 occurrences ont été remplacées par un appel à cette fonction (script Python avec comptage exact attendu, contrôle de taille et `php -l` avant toute écriture).
- **Règle à retenir** : ne jamais lire directement une propriété de classe core marquée `@deprecated` (`code_compta`, `statut`, `code_compta_fournisseur` historique…) dans un modèle PDF — vérifier dans `fetch()` de la classe concernée qu'elle est encore alimentée sur la version Dolibarr minimale **et** maximale supportées par le module, et centraliser la lecture dans un helper de la lib pour ne corriger qu'un seul endroit lors du prochain renommage.

### Module natif Sous-totaux de Dolibarr (modSubtotals, Dolibarr ≥ 22) — fiche et PDF (fix/chg v21.8.7)

Deux modules de sous-totaux coexistent et ne se reconnaissent pas : le module **ATM Subtotal** (`special_code` = numéro du module, `qty` 1..9 titre, 91..99 sous-total, 50 texte libre, options en extrafields) et le module **natif Sous-totaux** du core (`special_code` = `SUBTOTALS_SPECIAL_CODE` = 81, `product_type` 9, `qty` signée = niveau : +n titre, −n sous-total, montants à 0, libellé dans `desc`, options dans `extraparams['subtotal']` : `titleshowuponpdf`, `titleshowtotalexludingvatonpdf`, `titleforcepagebreak`, `subtotalshowtotalexludingvatonpdf`, actives quand la clé est présente). Le module natif n'a **aucun hook** : là où ATM remplit lui-même les hooks `pdf_getline*`, personne ne le fait pour le natif.

- **Fiche (templates `lineviews`)** : cf. section *Filtrage des lignes spéciales* — inclusion du template core `subtotal_view.tpl.php` via une closure liée à `$object` (`Closure::call`), parce que ce template utilise `$this` pour l'objet métier. Un `require` direct depuis le hook ferait pointer `$this` sur la classe de hook → `Call to undefined method Actionsinfraspackplus::getSubtotalColors()`. L'édition d'une ligne native est laissée au core (InfraSPackPlus ne surcharge pas le mode édition).
- **Détection (`core/lib/infraspackplus.lib.php`)** : point unique pour les deux conventions — `infraspackplus_getSubtotalLineSource()` ('native' / 'atm' / ''), `infraspackplus_isSubtotalModuleLine()`, `infraspackplus_isSubtotalTitle()`, `infraspackplus_isSubtotalTotal()`, `infraspackplus_isSubtotalFreeText()` (ATM seulement), `infraspackplus_getSubtotalLevel()` (1 = premier niveau, ATM : titre `qty`, sous-total `100 - qty` ; natif : `abs(qty)`). Côté natif : `infraspackplus_isNativeSubtotalLine()`, `infraspackplus_getNativeSubtotalOptions()` / `getNativeSubtotalOption()`, `infraspackplus_getNativeSubtotalAmounts()` (même algorithme que `CommonSubtotal::getSubtotalLineAmount()` mais numérique et HT/TVA/TTC/multidevise : somme des lignes au-dessus du sous-total jusqu'au premier titre natif de niveau ≤), `infraspackplus_getNativeBlockOptions()` (options du titre natif encore ouvert qui englobe une ligne ordinaire). **Règle** : ne plus écrire de test `qty < 10` / `qty > 90` / `100 - qty` dans un modèle ou dans la lib PDF, passer par ces helpers.
- **Rendu PDF (`infraspackplus.pdf.lib.php`)** : `pdf_InfraSPlus_writelinedesc()` calcule `$subSource` / `$subLevel` et applique aux lignes natives le même bandeau / style / opacité par niveau que pour ATM (réglages `INFRASPLUS_PDF_BODY_SUBTI_COLOR`, `TEXT_SUBTI/SUBTO_COLOR`, `BODY_SUBTO_COLOR`…, visibles en admin dès que l'un des deux modules est actif) ; `pdf_InfraSPlus_getlinedesc()` construit le libellé « Sous-total de <titre> : » (clé core `SubtotalOf`, constante `SUBTOTAL_LINE_TEXT_DOES_NOT_INCLUDE_TITLE_TEXT`) ; saut de page avant un titre portant `titleforcepagebreak` (jamais sur la première ligne) ; `pdf_InfraSPlus_subtotal_getrecap()` et `pdf_InfraSPlus_separateLine()` connaissent les deux conventions.
- **Colonnes PDF (`class/actions_infraspackplus.class.php`)** : InfraSPackPlus joue le rôle du hook manquant via ses propres méthodes `pdf_getlineqty`, `pdf_getlineupexcltax`, `pdf_getlineupwithtax`, `pdf_getlinevatrate`, `pdf_getlineremisepercent`, `pdf_getlineunit`, `pdf_getlineprogress`, `pdf_getlinetotalexcltax`, `pdf_getlinetotalwithtax` (toutes déléguées à `nativeSubtotalPdfCell()`), **actives uniquement pendant la génération d'un modèle InfraSPlus** : drapeau `infraspackplus_isInfraSPlusPdfGeneration()` (statique) posé par `pdf_InfraSPlus_getInstance()` quand un modèle instancie son PDF (jamais en mode `$onlyConf`), remis à zéro par `beforePDFCreation` et `afterPDFCreation`. Ligne native → cellule vide, sauf le montant du bloc sur un sous-total dont l'option « imprimer le montant » est cochée ; ligne ordinaire dans un bloc natif → PU et total HT vides si le titre englobant n'a pas l'option correspondante (sémantique du core : option absente = colonne masquée). Les modèles natifs de Dolibarr ne sont pas affectés (drapeau à faux).
- **Modèles** : D, DP, DST, C, CP, CBC, CBL, OF, OM, F, FL, FT, BLC utilisent les helpers aux 4 points historiques (préparation, exclusion du tableau des TVA, boucle de rendu, colonne Réf) — remplacement fait par script avec comptage attendu par fichier (2 / 1 / 1 / 1 sauf FL 2 / 0 / 1 / 1, FT et BLC 1 seul point). BL : colonnes Commandé / Reliquat / Expédié / Total vides sur titres et sous-totaux, totaux de quantités hors lignes de sous-totaux (leur `qty` est un niveau, pas une quantité).
- **Non couvert** : page récapitulative des sous-totaux (case du formulaire de génération fournie par le hook ATM, aucune constante native), options ATM `print_as_list` / `print_condensed` / `hideInnerLines` (extrafields ATM sans équivalent natif), `SUBTOTAL_HIDE_LINES_UNDER_TITLE` (constante cachée du core). Défaut préexistant : la branche `shipping` / `delivery` de `infraspackplus_isLineFromExternalModule()` lit `$line->fk_origin_line`, propriété absente en Dolibarr 23 (la détection native n'en dépend pas : `ExpeditionLigne` porte directement `special_code`, `product_type` et `qty`).
- **Test** : spécimens F, FL, C, CP, D, DP, BL générés en CLI avec des lignes natives injectées en mémoire (titres niveaux 1 et 2, sous-totaux, options PU / total HT / montant / saut de page), sortie dans un répertoire temporaire — aucune écriture en base ni dans le répertoire documents.

### Colonne « P.U. TTC » à 0,00 sur les lignes saisies en HT — `subprice_ttc` lu en chaîne (fix v21.8.8)

- **Symptôme** : sur une fiche document (constaté sur devis, instance fitantanana), la colonne « P.U. TTC » (`linecoluttc`) affichait `0,00` pour toutes les lignes saisies en HT, y compris les lignes ordinaires à quantité et total TTC non nuls (ex. PU HT 800,00, qté 12, total TTC 10 368,00 → « P.U. TTC » 0,00).
- **Cause** : le champ `subprice_ttc` des tables de lignes (ajout Osden dans la distribution LTS, colonne `DOUBLE(24,8)`) reste à `0` quand la ligne est saisie en HT (`$price_base_type !== 'TTC'`). Le pilote mysqli de Dolibarr renvoie les `DOUBLE` sous forme de **chaîne** : la valeur arrive dans l'objet ligne comme `"0.00000000"`, qui est **vraie** en PHP (seules `"0"` et `""` sont fausses). Dans le hook `printObjectLine` (`actions_infraspackplus.class.php`, mode view), `empty($line->subprice_ttc)` ne détectait donc jamais le cas « non renseigné » : le repli `total_ttc / qty` n'était pas appliqué et `pu_ttc` transmis aux templates valait `"0.00000000"` ; côté template, le test `!$upinctax` échouait pour la même raison et `price()` affichait `0,00`.
- **Correctif** : dans le hook, `subprice_ttc` est converti en `(float)` **avant** le test `empty()` (`$line->subprice_ttc = isset($line->subprice_ttc) ? (float) $line->subprice_ttc : 0.0;`), puis `pu_ttc` reçoit directement la valeur normalisée. Les 5 variantes `lineviews` (v21 à v24 + v22-DolInfraS) sont inchangées : elles reçoivent désormais un float, et `0.0` déclenche correctement leurs replis. Même correctif dans le hook homologue d'InfraSProject (21.1.11) et, avec tags `// InfraS change`, dans les deux tests `isset()` du template core `core/tpl/objectline_view.tpl.php` de l'instance (chemin de repli quand aucun des deux modules n'est actif — report vers le core de la base LTS à faire manuellement). Vérifié par rendu CLI du hook sur les lignes réelles : 864,00 et -4 368,00 au lieu de 0,00.
- **Nuance non traitée** : le repli hérité (`total_ttc / qty`) donne un PU TTC **après** remise de ligne (864,00 pour PU HT 800,00 et remise 10 %), alors que la colonne « P.U. HT » voisine affiche le prix avant remise. Afficher 960,00 imposerait de diviser par `(1 - remise_percent / 100)` — comportement upstream conservé en l'état.
- **Règle à retenir** : toute valeur numérique issue d'un `fetch_object()` / `fetch_lines()` (montants, prix, taux, colonnes `DOUBLE`) doit être castée en `(float)` avant un `empty()`, un `!$x` ou un `?:`, sinon un zéro stocké en base passe pour une valeur renseignée. Voir aussi *Colonne « P.U. TTC » affichant le HT pour les lignes sans quantité (fix v21.5.4)* : même colonne, même code hérité en plusieurs exemplaires (5 lineviews + InfraSProject + template core) — vérifier les trois couches à chaque correction.

### Totaux PDF cohérents (HT + TVA = TTC) sur Dolibarr LTS by InfraS — normalisation avant `_tableau_tot()` (fix v21.8.10)

- **Contexte** : le core « Dolibarr LTS by InfraS » stocke lignes et totaux document **sans arrondi** (8 décimales, tags `InfraS change Arrondis` dans `core/lib/price.lib.php` et `CommonObject::update_price()`). Le core expose en contrepartie `CommonObject::getRoundedTotals($multicurrency, $rule)` / `getRoundedTotalTTC($multicurrency)` : HT, TVA, taxes locales et timbre arrondis séparément au centime, TTC = somme des arrondis, règle de TVA `totalofround` (Mode 1, somme des HT/TVA de ligne arrondis ligne par ligne, défaut fournisseurs) ou `roundoftotal` (Mode 2, Σ par taux de arrondi(HT_taux × taux)) lue dans `extraparams['calculationrule']` de la facture (liens Mode 1 / Mode 2 de la fiche facture fournisseur) sinon dans `MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND(_SUPPLIER)`. Cas particulier intégré : TTC exact déjà au centime (saisie TTC, ex. 9,99) → TTC conservé, TVA dérivée.
- **Symptôme** : le tableau des totaux des modèles imprimait `total_ht`, chaque TVA par taux (`$this->tva_array` / `$this->tva`, sommes exactes des lignes) et `total_ttc` en les arrondissant **indépendamment** via `pdf_InfraSPlus_price()` → `price()`. Facture OVH FF-202609439 (fitantanana, 2026-09-09) : HT 2,20418496, TVA 0,44083699, TTC 2,64502195 → PDF « 2,20 + 0,44 = 2,65 », alors que la facture OVH et le reste à payer de la fiche (corrigée le même jour côté core) disent 2,64.
- **Correctif** (`core/lib/infraspackplus.pdf.lib.php`, 4 fonctions après `pdf_InfraSPlus_price()`) :
  - `pdf_InfraSPlus_roundAmounts(&$amounts, $key, $target)` : arrondit une liste de montants (valeurs directes ou sous-clé d'un tableau) à `MAIN_MAX_DECIMALS_TOT` et, si l'écart au total cible est un simple résidu d'arrondi (≤ 4 centimes), le répartit centime par centime sur les plus gros montants ;
  - `pdf_InfraSPlus_normalizeTotals(&$object, &$tva_array, &$tva, &$localtax1, &$localtax2, $multicurrency, $sign)` : remplace **en mémoire** `total_ht/tva/localtax1/localtax2/ttc` et `multicurrency_total_*` de l'objet par `getRoundedTotals(0)` / `getRoundedTotals(1)`, arrondit les TVA collectées par taux en ajustant `amount` sur la TVA arrondie et `base` sur le HT arrondi (signe du modèle appliqué, `$this->sign = -1` pour les avoirs affichés en positif), arrondit les taxes locales. Valeurs exactes sauvegardées dans `$object->context['infrasplus_exact_totals']`. Sans effet si la méthode `getRoundedTotals` n'existe pas (Dolibarr standard), si l'objet est déjà normalisé, ou sur une facture de situation de rang > 1 (totaux nets des situations précédentes, gérés par les modèles) ;
  - `pdf_InfraSPlus_restoreTotals(&$object)` : restaure les valeurs exactes, appelée par le hook `afterPDFCreation` (`$parameters['object']`) — indispensable car `Facture::update()`, `Propal::update()`, `Commande::update()`… réécrivent `total_*` depuis la mémoire ;
  - `pdf_InfraSPlus_getTotalTTC($object, $multicurrency)` : `getRoundedTotalTTC()` si disponible, `total_ttc` sinon (utilisée par le relevé de factures FR pour les factures, acomptes et avoirs listés).
- **Appel dans les modèles** : une ligne juste avant le premier `_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1)` (calcul de hauteur), une fois `$this->tva_array` / `$this->tva` / `$this->localtax*` remplis par la boucle de préparation des lignes. Modèles concernés : D, DP, C, CP, CBC, F, FL, FT (`$this->tva_array`, `$this->sign`) ; FF, CF (`$this->tva` seul, tableau vide passé pour `tva_array`) ; DF (HT et TTC seuls, tableaux vides). Non concernés : BL/BLX/BR/RE (objet sans totaux propres), NDF (lignes saisies TTC au centime), FI (`pricefichinter`), extrait de compte, relevé FR (traité par `pdf_InfraSPlus_getTotalTTC()`).
- **Vérification** : génération CLI vers un répertoire temporaire (surcharger `$conf->fournisseur->facture->dir_output` **et** `$conf->facture->multidir_output[$conf->entity]` — le modèle F lit `multidir_output`, l'oublier régénère le PDF réel de la facture). FF-202609439 → 2,20 / 0,44 / 2,64 ; FF-202609436 (MGA, multidevise) → 132 500 / 26 500 / 159 000 ; FA-202609180 → 452,96 / 90,59 / 543,55 ; totaux exacts restaurés après génération (`context` vide).
- **Règle à retenir** : tout nouveau modèle qui imprime HT, TVA et TTC doit appeler `pdf_InfraSPlus_normalizeTotals()` avant son tableau des totaux et ne jamais arrondir le TTC exact seul (`price2num($object->total_ttc, 'MT')`) pour un montant à payer : utiliser `pdf_InfraSPlus_getTotalTTC()`. Le module reste compatible avec un core Dolibarr standard (toutes ces fonctions sont sans effet si `getRoundedTotals()` est absente).

### Modèle de bordereau de prélèvement / virement pour InfraSFiles (InfraSPlus_Bon, add v21.9.0)

Modèle PDF `core/modules/infrasfiles/widthdraw/doc/pdf_InfraSPlus_Bon.modules.php` (classe `pdf_InfraSPlus_Bon` **extends `ModelePDFInfrasfileswidthdraw`**, classe de base du module InfraSFiles) : même contenu que le modèle `bordereau` d'InfraSFiles — un document par tiers ou par maison mère, factures, échéances, avoirs appliqués, statut des lignes, total et rejets — rendu au standard InfraSPlus (gabarit `InfraSPlus_BC`) : `pdf_InfraSPlus_getValues()`, `pdf_InfraSPlus_getInstance()`, logo, titre / réf / dates / statut à droite, cadres d'adresses `pdf_InfraSPlus_getAddresses()` + `pdf_InfraSPlus_writeAddresses()` (compte bancaire de la société ajouté sous son adresse, maison mère et RIB du destinataire sous la sienne), tableau `_tableau()` par page, bande de total, zone de signature, mentions `pdf_InfraSPlus_free_text()` au-dessus du pied, pied `pdf_InfraSPlus_pagefoot()`, notes du dictionnaire `pdf_InfraSPlus_Notes()` (`typeNotes = -1`), filigrane image + filigrane brouillon d'InfraSFiles, fusion `pdf_InfraSPlus_files()`.
Points techniques :
- **Découverte** : InfraSFiles scanne `core/modules/infrasfiles/<élément>/doc/` dans tous les modules déclarant `models` ; le modèle est donc listé, activé (`llx_document_model`, type `infrasfileswidthdraw`) et prévisualisé (spécimen) dans **les paramètres d'InfraSFiles**, jamais dans ceux d'InfraSPackPlus. Le fichier commence par `dol_include_once()` de la classe de base puis `return` si elle n'existe pas : sans InfraSFiles, rien n'est déclaré.
- **Données** : lignes, unités (tiers / maison mère), nom de fichier, indexation ECM et dernier document viennent de la classe de base (`infrasfilesGetFile()`, `infrasfilesBefore()`, `infrasfilesFinish()`) ; le modèle n'ajoute que le rendu. `pdf_InfraSPlus_getValues()` force `update_main_doc_field = 1` : remis à 0 dans le constructeur (la table native n'a pas la colonne, InfraSFiles mémorise le fichier lui-même).
- **Destinataire** : `infrasplusLoadAddressee()` charge le tiers ou la maison mère de l'unité dans `$object->thirdparty` (objet `Societe`, ou objet minimal pour le spécimen) ; `pdf_InfraSPlus_getAddresses()` est appelé avec `typeadr = 'accountStatus'` pour qu'il utilise ce tiers tel quel (sans le détour `INFRASPLUS_PDF_FACTURE_PARENT_ADDR_FACT`, la maison mère étant déjà décidée par InfraSFiles).
- **Options** : `formBuilddocOptions()` et `beforePDFCreation()` acceptent l'élément `widthdraw` dans leurs listes générales (titre, logo, adresse expéditeur, adresse destinataire, mentions, notes, image de pied, fichiers, alias, séparateur de fin) ; les blocs spécifiques (CGV, images produits, colonnes, remises, PAD de signature…) restent gérés par leurs propres listes. `infraspackplus_defaultParam()` déclare `widthdraw` dans `listModulesFreeT` (mention système de base = `INFRASFILES_WIDTHDRAW_FREE_TEXT`) et `listModulesNoteP` (pas de note native). Les choix sont mémorisés par les constantes `INFRASPLUS_PDF_PARAMS_widthdraw_*` comme pour les autres éléments.
- **Préfixe** : code `Bon` dans `$listModeles` / `$listModelesModule` (`admin/infrasplussetup.php`, conditionné au module `infrasfiles`), constante `INFRASPLUS_PDF_ADD_PREFIX_TO_Bon` (casse mixte, comme `PJ_Dossier`), suffixe `_Bon` en mode multi-fichiers ; appliqués par `infrasplusApplyPrefix()` au nom calculé par InfraSFiles (`<REF>-<code destinataire>.pdf`).
- **Propriétés dynamiques** : `pdf_InfraSPlus_getValues()` renseigne ~150 propriétés ; la classe déclare celles qu'elle utilise et porte `#[\AllowDynamicProperties]` (commentaire pour PHP < 8.0) pour les autres.
- **Test** : `infrasfiles/test/phpunit/InfrasfilesPdfPresenceTest::testModeleInfraSPlusBonDInfraspackplus` (ignoré si le modèle est absent).
### Chemin des specialfiles du dossier de contrat factorisé (chg v21.9.4)

- `pdf_InfraSPlus_CTS::write_file()` (`core/modules/contract/doc/pdf_InfraSPlus_CTS.modules.php`) calcule le chemin relatif des specialfiles, préfixe d'entité inclus en multicompany, **une seule fois** dans `$reldirpdfs`. Cette variable sert à construire le répertoire absolu (`$dirpdfs = DOL_DATA_ROOT.'/'.$reldirpdfs`) et est passée à `completeFileArrayWithDatabaseInfo()`.
- Même correctif sur `pdf_InfraSPlus_BLC`, `pdf_InfraSPlus_PJ_Dossier` et `pdf_InfraSPlus_PJ_Docs`, qui passaient le chemin en dur `'infraspackplus/specialfiles'` (sans préfixe d'entité) à `completeFileArrayWithDatabaseInfo()` : aucun specialfile fusionné en entité ≥ 2. Le hook `formBuilddocOptions` (`actions_infraspackplus.class.php`) applique déjà le préfixe aux deux endroits.
- Avant : la même expression était écrite deux fois (`$dirpdfs` puis `$relativedirpdfs`). Le comportement était identique, mais les deux chemins pouvaient diverger lors d'une modification. Variante déjà présente sur l'instance acfincendie (21.8.9), reprise ici sans changement fonctionnel.
- **Règle à retenir** : le chemin relatif passé à `completeFileArrayWithDatabaseInfo()` doit être strictement celui des enregistrements `llx_ecm_files` (préfixe d'entité inclus), sinon Dolibarr tente de réinsérer les fichiers (erreurs `uk_ecm_files`) et aucun `rowid` n'est récupéré.

### Dossier projet unifié — mode générique / mode « reçu de documents » (add v21.10.0)

`pdf_InfraSPlus_PJ_Dossier.modules.php` existait en deux variantes incompatibles sous le même nom : la base LTS (et dolinfras) fusionnait les specialfiles ; rgenergies construisait un « reçu de documents » (page de présentation puis documents cochés dans l'extrafield `recudocs`), variante absente de la base et donc perdue à chaque resynchronisation. Un seul fichier porte désormais les deux comportements.

- **Choix du mode** : `isRecudocsMode()`, appelé une fois dans le constructeur — mode « reçu de documents » si `htdocs/BRAND` contient exactement `vcc` (lecture `trim(file_get_contents())`, même convention que `dolinfras_activate_brand_modules()` et `infrashelpdesk_resolve_brand()`) **et** si l'extrafield `recudocs` est défini sur les projets (`ExtraFields::fetch_name_optionals_label('projet')`). Sinon mode générique. Pas de constante d'activation, pas de nom d'instance en dur. Si BRAND vaut `vcc` sans l'extrafield, repli générique consigné dans les logs (`LOG_WARNING`).
- **Mode générique** (`buildMergeFile()`) : inchangé — fichiers choisis dans le formulaire de génération, sinon specialfiles de `INFRASPLUS_PDF_SPECIAL_FILES` activés par `INFRASPLUS_PDF_SPECIAL_FILE_PROJECT_<NOM>_AUTO` ; aucune page donc aucun fichier s'il n'y a rien à fusionner ; `update_main_doc_field = 0`.
- **Mode « reçu de documents »** (`buildRecudocsFile()`, `_pagehead()`, `_pagefoot()`) : page de présentation (logo, titre `PDFInfraSPlusProjectDossierTitle`, réf., références/dates des devis et factures listés, cadres d'adresses, notes, « Liste des documents : » + valeur de `recudocs`, nombre de pages écrit après coup sur la page 1), puis fusion par ordre de clé de `$this->files` : 10 PV de réception (signé, sinon de base), 12 PV de levée de réserves, 20 cadre CEE, 30 devis signé (sinon PDF de base du devis), 40+ factures d'acompte, 50 facture, 60 attestation RGE (GED `ecm/Docs Societe`, choix Qualipac / Qualibois / Chauffage+ / Qualisol / Ventil+ selon l'extrafield `categorie`), 70 note de dimensionnement, 80 certificat Qualigaz. Adresse de livraison par défaut : adresse « INSTAL » du tiers. Nom du PDF : `<ref>_<Nom_du_chef_de_projet_externe>.pdf` ; `update_main_doc_field = 1`. Les specialfiles génériques ne sont **pas** enchaînés dans ce mode (rgenergies a des `_AUTO` actifs pour les projets, gérés par son modèle `PJ_Docs`).
- **Correctifs embarqués** : liste des fichiers initialisée en tableau (chaîne vide → `ksort()` fatal en PHP 8), `$sizeBC` initialisé, `$file` non écrasé dans la boucle des specialfiles, repli « devis de base » = premier PDF dont le nom commence par la référence hors `_NoteDim_` (l'ancien code comparait un nom obsolète et retenait le dernier fichier). Ce dernier point est le seul changement de rendu volontaire par rapport à rgenergies.
- **Déploiement** : aucune des instances du serveur n'a de fichier `htdocs/BRAND` au 2026-10-01 (seule dolinfras24 : `dolinfras2026`). Sur rgenergies, le mode « reçu de documents » ne s'active qu'après création de `htdocs/BRAND` contenant `vcc`. Les modèles `PJ_Docs` et `PJ_Chantier` de rgenergies sont repris dans la base avec le même verrou (section suivante).
- **Préfixe de nom** : le modèle lit `INFRASPLUS_PDF_ADD_PREFIX_TO_PJ_Dossier` (code `PJ_Dossier` de `$listModeles` dans `admin/infrasplussetup.php`), et plus `..._PJ_D` qui n'était jamais enregistré par l'administration (fix v21.10.0).
- **Règle à retenir** : un comportement propre à une marque ou à un client se branche sur `htdocs/BRAND` et sur la présence des données qu'il exige (extrafields, modules), jamais sur le nom du répertoire de l'instance ni sur un fichier dupliqué hors de la base.

### Modèles projet réservés à la marque vcc — PJ_Docs et PJ_Chantier (add v21.10.0)

Deux modèles qui n'existaient que sur rgenergies (hors base, donc sans versionnement) sont repris dans la base avec le même verrou que le mode « reçu de documents » de PJ_Dossier : `infraspackplus_getBrand() === 'vcc'` (contenu de `htdocs/BRAND`, lecture mise en cache statique) et présence des données exigées via `infraspackplus_isExtrafieldDefined($db, 'projet', <code>)` (`core/lib/infraspackplus.lib.php`). Hors de ces conditions, le constructeur pose `$this->version = 'development'` — le core masque alors le modèle de la liste de la configuration des projets tant que `MAIN_FEATURES_LEVEL` < 2 (`projet/admin/project.php`) — et `write_file()` refuse la génération avec `FeatureDisabled`. Un modèle déjà activé dans `llx_document_model` reste proposé dans le formulaire de génération jusqu'à sa désactivation : seul le refus de `write_file()` le neutralise.

- **`pdf_InfraSPlus_PJ_Docs`** (`core/modules/project/doc/pdf_InfraSPlus_PJ_Docs.modules.php`) : modèle autonome (choix retenu plutôt qu'une sous-classe) reprenant la logique du mode générique de `pdf_InfraSPlus_PJ_Dossier` — fichiers choisis dans le formulaire, sinon specialfiles activés par `INFRASPLUS_PDF_SPECIAL_FILE_PROJECT_<NOM>_AUTO`, chemin ECM avec préfixe d'entité — avec gabarit `InfraSPlus_PJ_Docs`, suffixe `_PJ_Docs`, constante de préfixe `INFRASPLUS_PDF_ADD_PREFIX_TO_PJ_Docs` et `update_main_doc_field = 0`. Toute correction du mode générique est à reporter dans les deux fichiers. C'est le modèle par défaut des projets sur rgenergies : il déclenche la création des documents du chantier par les scripts specialfiles (ABE, PV de réception, bulletins de visite…), qui écrivent leurs propres PDF dans le dossier du projet ; il ne produit en général aucun fichier à son nom. Condition : marque `vcc` seulement (ailleurs, doublon du mode générique de PJ_Dossier). Le code `PJ_Docs` n'est pas dans `$listModeles` de l'administration : pas d'option de préfixe pour ce modèle.
- **`pdf_InfraSPlus_PJ_Chantier`** (`core/modules/project/doc/pdf_InfraSPlus_PJ_Chantier.modules.php`) : référence de chantier pour l'organisme de qualification RGE. Concatène les ABE signées du dossier du projet (`*_ABE*-signé`), puis les devis signés des devis au statut ≥ signé (`*-signé` / `*-signe`), puis le PDF principal de chaque facture ; s'arrête sans produire de fichier si un groupe est vide. Les PDF annotés sont aplatis par Ghostscript (`gs -sDEVICE=pdfwrite -dPreserveAnnots=false`, jamais de détour PostScript qui perdrait la transparence) car TCPDI ignore les annotations. Nom du fichier : `<code>_<Nom_du_chef_de_projet_externe>.pdf`, code déduit de l'extrafield `categorie` (QPAC : 1, 2, 3, 8, 11 ; QB : 5, 6, 18 ; CPLUS : 4 ; QS : 9 ; VPLUS : 15, 16 ; sinon vide) — correspondance volontairement laissée telle quelle (celle de PJ_Dossier pour l'attestation RGE couvre en plus 17 et 19). Sans chef de projet externe : avertissement `PDFInfraSPlusProjectChantierNoProjectLeader` et nom `<ref>_Chantier.pdf`. Conditions : marque `vcc` et extrafield `categorie`.
- **Déploiement rgenergies** : créer `htdocs/BRAND` contenant `vcc`, puis resynchroniser le module depuis la base avec `rsync -rlt --inplace` sans `--delete` (les 27 scripts `core/modules/specialfiles/*.php` propres à rgenergies sont conservés ; leurs traductions, autrefois dans un `langs/fr_FR/specialfiles.lang` hors base disparu de l'instance, sont dans `infraspackplus.lang` des 4 langues depuis la v21.10.1). Vérifié le 2026-10-01 sur le projet PJ2608-0329 avec un banc hors production (htdocs fantôme, copie des documents, transaction annulée).
- **Règle à retenir** : un modèle propre à un client entre dans la base verrouillé par `infraspackplus_getBrand()` + `infraspackplus_isExtrafieldDefined()`, avec `version = 'development'` et refus dans `write_file()` hors conditions ; jamais de test sur le nom du répertoire de l'instance, jamais de fichier laissé hors de la base.

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

### Avertissement PHP « Undefined property: pdf_InfraSPlus_C::$sign » sur les modèles commande et devis (fix v21.8.11)

- **Symptôme** : à chaque génération d'un PDF de commande (`C`, `CP`, `CBC`) ou de devis (`D`, `DP`), une ligne `PHP Warning: Undefined property: pdf_InfraSPlus_C::$sign ... on line 912` dans le journal d'erreurs PHP du pool (constaté sur infras.store / fitantanana après chaque achat en ligne). Aucun effet sur les montants imprimés.
- **Cause** : la version 21.8.10 (« Totaux PDF cohérents ») a ajouté dans 11 modèles l'appel `pdf_InfraSPlus_normalizeTotals($object, ..., $this->use_multicurrency, $this->sign)`. La propriété `$sign` n'existe que dans les trois modèles de facture (déclarée `public $sign = 1`, passée à -1 pour afficher un avoir en positif). Les modèles fournisseurs (`FF`, `CF`, `DF`) passaient déjà `1` explicitement ; les cinq modèles commande/devis ont repris la forme facture par copie. La fonction remplace un signe vide par 1 (`empty($sign) ? 1 : $sign`), d'où l'absence d'impact fonctionnel.
- **Correctif** : les cinq modèles passent le signe `1` explicitement, avec un commentaire (pas d'avoir sur ces types de documents). Le module de l'instance était identique à la base LTS (`diff -rq` vide, 21.8.10 des deux côtés) : numérotation 21.8.11 sans collision, report vers la base via InfraSTools.
- **Leçon** : lors d'un ajout transversal à plusieurs modèles PDF, vérifier pour chaque propriété `$this->xxx` utilisée qu'elle est déclarée dans la classe cible (`grep -n "public \$xxx"`), les modèles n'ayant pas tous les mêmes propriétés malgré une structure commune.

### Sélecteurs d'adresses jamais présélectionnés — comparaisons strictes entier vs rowid chaîne (fix v21.9.3)

- **Symptôme** : avec l'option « Adresse de la société émettrice » en mode « Enregistrement par Utilisateur » (ou par client / document / type), le choix est bien enregistré en base (`llx_const INFRASPLUS_PDF_PARAMS_propal_USER_x` contient `adr=1` / `adr=2`) mais le sélecteur revient systématiquement à « par défaut » à l'ouverture du document suivant. Même défaut sur l'adresse de livraison / d'intervention et sur le bloc sous-traitant. Signalé **avec diagnostic et correctif** par Lounès Halfaoui (HLS QSE), instance dlb.havez-levage.fr (module 21.8.4, présent dès 21.8.2, Dolibarr 22.0.3).
- **Cause** : dans `formBuilddocOptions()` (`actions_infraspackplus.class.php`), la présélection comparait avec `===` une valeur **entière** issue de `GETPOSTINT()` (ou de l'application des paramètres mémorisés) à `$lineadr->id`, alimenté par `$obj->rowid` — **chaîne** renvoyée par le pilote (`Address::fetch()` / `fetch_lines()` font `$line->id = $obj->rowid;` sans cast). `2 === "2"` est toujours faux → aucune option n'est jamais `selected`. Introduit par la « modernisation PHP » de la 18.15.5 (passage de comparaisons souples à strictes sans harmonisation des types). Cas aggravé sur le sélecteur de sous-traitant : les défauts `GETPOSTINT('Sst') ?: '-2'` / `GETPOSTINT('adrSst') ?: '-2'` sont des **chaînes** `'-2'`, donc `$SstPost === -2` toujours faux et `$SstPost !== -2` toujours vrai — le sélecteur d'adresse sous-traitant s'affichait en permanence.
- **Correctif** : cast `(int)` des deux côtés sur les 8 comparaisons concernées — adresse émettrice (l.332), adresse de livraison (l.539), adresse sous-traitant (`-2` / `-1` / `$lineadr->id`, l.589/590/593), plus les 3 non relevées par le client sur le sélecteur `Sst` (`=== -2`, `=== $ar_listSsT['rowid']` issu de `fetch_array()`, et la condition d'affichage `!== -2`). Durcissement préventif dans `admin/adresses.php` (présélection de l'adresse de livraison par défaut, `getDolGlobalString()` vs rowid — chaîne contre chaîne donc fonctionnelle, mais même motif fragile).
- **Revue exhaustive associée** : toutes les autres occurrences `===` / `!==` des PHP du module vérifiées saines — retours **entiers** d'`Address::fetch()` (`$res === 1`, `$adrfound == 1`, `$addresslivrfound !== 1` des modèles PDF), sélecteurs logo / pied / CGV-CGI-CGA (chaîne contre chaîne), mode livraison mixte fournisseur (`$prefixLabel.$adrlivrfourPost === $value` : concaténations, donc chaînes des deux côtés par construction), idiomes `strpos()/array_search()/substr() !== false`, clé de contrôle mod 97 (`intval() % 97`).
- **Règle à retenir** : tout identifiant lu en base (`$obj->rowid`, `fetch_array()['rowid']`, donc `Address->id`) est une **chaîne** — ne jamais le comparer en `===` à un `GETPOSTINT()` ou à un littéral entier sans cast `(int)` des deux côtés ; et ne jamais donner un défaut en **chaîne** (`?: '-2'`) à une variable comparée strictement à un littéral entier. Pendant, pour les identifiants, du point « Valeurs `DOUBLE` lues en base » (fix v21.8.8). Lors d'une « modernisation » `==` → `===`, harmoniser les types au moment du durcissement, pas après coup.


### Réglages PDF par document, client et utilisateur (table dédiée, chg v21.11.0)

Avant 21.11.0, les options choisies dans le formulaire de génération étaient enregistrées par `beforePDFCreation` dans quatre constantes globales à chaque génération : `INFRASPLUS_PDF_PARAMS_<element>_USER_<id>`, `_DOC_<id>`, `_TYPE` et `_CUST_<socid>`, relues par `infraspackplus_defaultParam()`. Chaque option a une portée `INFRASPLUS_PDF_OPTION_<option>` (`user`, `doc`, `type`, `cust` ou `none`, page Génération) qui décide dans quel jeu elle est écrite puis lue. Mesures (retour Kytom du 05/10/2026) : 14 168 constantes sur paris, 89 % de `llx_const`, 1,5 Mo chargés à chaque requête, 773 orphelines ; 8 251 orphelines sur 8 296 à barcelona (base copiée puis vidée) ; un nouveau document portant l'identifiant d'un ancien héritait de ses réglages. Sur fitantanana : 4 268 constantes (56 %), 576 réglages document et 295 réglages client orphelins.

Depuis 21.11.0 :

- `_TYPE` reste une constante (un réglage d'administration par type de document), réécrite seulement si sa valeur change ;
- `user`, `doc` et `cust` sont dans `llx_infraspackplus_pdf_params` (`entity, element, scope, fk_object, params`), clé unique sur les quatre premières colonnes ; `params` garde le format chaîne de requête (`clé=valeur&…`) que `infraspackplus_defaultParam()` décodait déjà ;
- lecture : `infraspackplus_getPdfParams($element, $fk_doc, $fk_soc, $fk_user)` — une requête, trois lignes au plus ; écriture : `infraspackplus_setPdfParams($element, $scope, $fk_object, $params)` (`INSERT … ON DUPLICATE KEY UPDATE`, chaîne vide = suppression de la ligne, aucune ligne vide n'est créée) ; suppression : `infraspackplus_deletePdfParams($element, $scope, $fk_object)` (toutes entités) ;
- trigger : toute action `*_DELETE` d'un objet (hors lignes `*det` / `*ligne` / `*line`) supprime ses réglages `doc` ; `COMPANY_DELETE` → `cust` ; `USER_DELETE` → `user` ;
- `infraspackplus_migration_pdf_params()` (admin lib) : constantes → table par lots de 200 en `INSERT IGNORE` (les lignes existantes priment sur un dump plus ancien), `None` → `none`, identifiants nuls supprimés sans migration, puis suppression des constantes ; appelée par `init()`, par `remove()` avant la sauvegarde et à la fin de `infraspackplus_restore_module()` ;
- `infraspackplus_purge_pdf_params()` : supprime les lignes dont l'objet n'existe plus (table résolue par `getElementProperties()`, éléments inconnus ou tables absentes conservés) ; appelée par `init()` et par le bouton « Purger les orphelins » de `admin/generation.php` (`infraspackplus_count_pdf_params()` affiche les compteurs par portée) ;
- `infraspackplus_copy_entity()` copie les portées `cust` et `user`, pas `doc` (documents propres à chaque entité) ;
- `remove()` ne détruit pas la table ; elle figure dans `update.<entité>` pour Sauvegarder / Restaurer.

Alternative écartée : `extraparams` du document. `CommonObject::setExtraParameters()` tronque le JSON à 250 caractères (colonne `varchar(255)`, déjà utilisée par le chantier Arrondis pour `calculationrule`) ; les réglages `doc` atteignent 175 caractères en forme chaîne (153 documents au-dessus de 150 sur fitantanana), `expedition`, `expensereport`, `delivery` et `reception` ne décodent pas la colonne dans `fetch()` et `product` n'en a pas.

Sauvegarde / restauration corrigées au passage (`infraspackplusAdmin.lib.php`) :

- `infraspackplus_bkup_table()` posait le marqueur `__ENTITY__` sur la deuxième colonne de chaque table (`$j == 1`). Pour `infraspackplus_societe_address`, `entity` est la première colonne : `datec` recevait le marqueur (remplacé par le numéro d'entité à la restauration) et les adresses n'étaient pas restaurées (date invalide). La position est désormais `array_search('entity', $listeCols)`.
- la table `pdf_params` est ajoutée au dump (`ON DUPLICATE KEY UPDATE params`), les constantes `_DOC_` / `_CUST_` / `_USER_` en sont exclues, et l'`UPDATE llx_const … None → none` du pied de fichier (préfixe `llx_` en dur) est retiré ;
- `infraspackplus_restore_module()` : fichier absent géré sans avertissement, `$result` initialisé, constantes rejouées par un ancien dump reversées dans la table.

Incident de déploiement 21.11.0 (06/10/2026, rgenergies, ansemble, dolinfras) : le commentaire de fin de ligne de la dernière colonne (`tms … CURRENT_TIMESTAMP -- last modification date`) n'était pas retiré par `run_sql()` et avalait `) ENGINE=innodb;` : la table n'était jamais créée, la migration échouait (message « Erreur lors de la migration des réglages PDF enregistrés »), et comme `remove()` excluait déjà les constantes `_DOC_/_CUST_/_USER_` de la sauvegarde avant de supprimer toutes les constantes `INFRASPLUS_%`, les réglages ont été perdus. Récupération depuis les dumps SQL nocturnes (`/var/backups/<instance>/<instance>.AAAAMMJJ.sql.gz`, section `llx_const` chargée dans une table temporaire puis `INSERT IGNORE` filtré) et, pour dolinfras (pas de dump nocturne), depuis la sauvegarde datée du module (`DOL_DATA_ROOT/admin/infraspackplus_update<date>.1`, 4 jours d'ancienneté). Correctifs 21.11.1 : commentaire déplacé ; `infraspackplus_migration_pdf_params()` crée la table depuis les fichiers SQL du module si elle manque ; `infraspackplus_bkup_module()` ne retire les constantes de réglages que si la table existe.

Test d'équivalence réalisé sur fitantanana (CLI, transaction annulée) : `infraspackplus_defaultParam()` identique avant et après migration sur les 2 513 devis, commandes, factures et interventions ayant des réglages, pour les deux utilisateurs concernés.

### Colonne « Unité » décalée sur le PDF d'ordre de fabrication — index de `$object->lines` (fix v21.11.2)

- **Symptôme** : sur `pdf_InfraSPlus_MRP`, avec `PRODUCT_USE_UNITS` actif, chaque composant affichait l'unité de la ligne précédente de l'OF : la première ligne celle du produit à fabriquer (« pce »), les suivantes celle du composant précédent (nomenclature en g et kg : le composant en g s'affichait en pièce, celui en kg en g). Signalé avec diagnostic et correctif par un client (Dolibarr 23.0.3 et 23.0.4) ; reproduit sur fitantanana (Dolibarr 22.0.5), donc indépendant de la version de Dolibarr. Sur fitantanana, le défaut n'est pas visible en exploitation : modules MRP et BOM désactivés, aucun OF, `PRODUCT_USE_UNITS` vide (la colonne n'est alors pas dessinée).
- **Cause** : le modèle imprime `$linesToUse` (lignes `toconsume`, ou `consumed` si l'OF est produit, statut ≥ 3) mais appelait `pdf_getlineunit($object, $i, …)`, qui lit `$object->lines[$i]` (`core/lib/pdf.lib.php`, comme les hooks `pdf_getlineunit` d'infraspackplus et d'infrastructure). `$object->lines` contient toutes les lignes de l'OF, dans l'ordre `toproduce`, `toconsume`, puis `consumed` / `produced` : l'index ne correspond plus. Même défaut, sans effet visible sur un OF, sur `pdf_InfraSPlus_separateLine($object, $i)` et sur le test `$object->lines[$i + 1]->pagebreak` de la même boucle (une ligne d'OF n'a ni `special_code` ni `pagebreak`).
- **Correctif** : avant la boucle, `$printedObject = clone $object` dont `lines` vaut `$linesToUse` (ou `$bom->lines` dans la branche `$frombom`) ; il remplace `$object` dans les trois appels indexés. Les hooks restent appelés, comme dans tous les autres modèles InfraSPlus, et `$object->lines` n'est pas modifié (relu ensuite par `afterPDFCreation` et par la fiche de l'OF). Le correctif proposé par le client (appel direct à `getLabelOfUnit()` sur la ligne imprimée) a été écarté : il contourne les hooks et laisse les deux autres appels désalignés. Remplacer `$object->lines` par `$linesToUse` a aussi été écarté (l'objet de l'appelant serait modifié).
- **Branche `$frombom`** : inatteignable. `count($object->lines)` (début de `write_file()`) lève une `TypeError` en PHP 8 quand `lines` n'est pas un tableau, avant le test de la branche ; `Mo::fetch()` et `Mo::initAsSpecimen()` initialisent toujours un tableau. Branche conservée, alimentée par le même objet.
- **Spécimen (même version)** : `Mo::initAsSpecimen()` et `BOM::initAsSpecimen()` ne créent aucune ligne (contrairement à `Propal`, `Commande`, `Facture`…), donc les spécimens de `pdf_InfraSPlus_MRP` et `pdf_InfraSPlus_Bom` avaient un tableau vide. Quand `$object->specimen` est vrai et que la liste est vide, les deux modèles appellent `pdf_InfraSPlus_getSpecimenLines($lineClass, $qty, $nbLines)` (`infraspackplus.pdf.lib.php`) : trois lignes (`MoLine` au rôle `toconsume`, ou `BOMLine`) construites sur les trois premiers produits de type bien (`fk_product_type = 0`) de l'entité, quantités 1, 2, 3 (multipliées par la quantité de l'OF pour le MRP), unité = unité du produit. Sans produit en base, la liste reste vide et le tableau aussi. Lecture seule, aucun effet hors spécimen. Le spécimen d'OF n'a toujours ni produit à fabriquer ni tiers (avertissements PHP sur `$object->thirdparty` nul, non traités).
- **Spécimens de tous les modèles (même version)** : banc hors production, un spécimen par modèle avec l'objet de la classe Dolibarr correspondante (`Entrepot` pour `ST`, `Mo` pour `MRP`…), hooks limités à infraspackplus et infrastructure, sorties redirigées (sauf `mycompany` et les dossiers produits, lus par les modèles), `$_SERVER` web simulé, gestionnaire d'erreurs qui journalise les avertissements, transaction annulée. Le gestionnaire doit ignorer les erreurs silencées (`if (!(error_reporting() & $no)) { return true; }`) : PHP appelle un gestionnaire utilisateur même sous `@`, ce qui avait fait compter à tort les avertissements de `tcpdi_parser.php`. Un gestionnaire qui retourne `true` masque les erreurs fatales de `DolDeprecationHandler` (appel d'une méthode absente) : les journaliser à part. Résultat : 38 modèles sur 40 génèrent leur spécimen, tous sans aucun avertissement réel ; `PJ_Chantier` et `PJ_Docs` sont refusés par le verrou `vcc`, comme prévu ; `BC` est hors banc (appel de génération propre aux bordereaux). Corrigé :
	- `RE` : `$dir` jamais défini dans la branche spécimen (génération impossible) ; tableau vide, car les lignes de spécimen n'ont pas de `fk_commandefourndet` et sont toutes prises pour des doublons de la même ligne de commande (le modèle en attribue une à chacune) ; `$heightforheader` jamais défini (saut de page avec photo sous la description) ;
	- `ST` : spécimen enregistré sous un nom daté au lieu de `SPECIMEN.pdf` (détection par la référence, absente d'un `Entrepot` spécimen : `$object->specimen` est aussi testé) ; tableau vide (lignes lues dans `llx_product_stock` pour l'entrepôt d'id 0 : `pdf_InfraSPlus_getSpecimenStockLines()` construit 3 lignes sur les produits de la base) ; compteurs d'en-tête à zéro ;
	- `account_statut` : l'état de compte est piloté par `$object->context['account_statut']`, absent du spécimen : le spécimen reçoit des paramètres (client, mois écoulé) ; son tableau reste vide par nature (aucune facture pour un tiers d'id 0) ;
	- `NDF` : `getSumProCard()` n'existe que sur la classe du module InfraSExpense, pas sur `ExpenseReport` : erreur fatale sur le spécimen de la page d'administration, d'où le test `method_exists()` ;
	- `ET` : le bloc destinataire affichait « -1 » : avec l'option « adresse de réception » (`INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION`), le modèle attendait la chaîne `'Default'` ou un objet adresse alors que `beforePDFCreation` renvoie `GETPOSTINT('adrlivr')` (0 absent, -1 adresse de base, -2 aucune, identifiant d'adresse secondaire sinon, comme `BL` le lit) ; le tiers est désormais imprimé pour 0, -1 et -2, l'adresse secondaire (`Address::fetch()`) pour un identifiant. La commande d'origine n'était jamais lue (référence, date et nombre d'articles absents de l'étiquette) : `fetch_origin()` des cœurs 22 à 24 renseigne `origin_object` et plus `$object->commande` ;
	- `FT` : le code-barres C128 recevait `$object->id` ; TCPDF attend une chaîne, avec un entier (spécimen : `id` vaut 0) il émet deux avertissements et dessine un autre code (560 pixels de différence avec la chaîne `'150'`) : cast `(string)` ;
	- avertissements PHP sans effet sur le rendu : tiers absent (`infraspackplus_check_parent_addr_fact()` renvoie un `Societe` vide ; `pdf_InfraSPlus_Build_Third_party_Name()`, mots-clés `MRP` / `P2` / `PBC`, en-tête interne), `$height_SsT`, `$ht_url`, colonne `comm` (`BR`, `RE`), `$txtref` / `$txtdt` / `$txtnbprod` (`ET`), `$totalEfPaySpec` et expédition absente (`FL`), `$objectref` (`FR`), `$ht_colpay` (`FT`), `$hasimg` (`P2`), `$listKeyOk` (`PJ`), `special_code` des lignes d'entrepôt (`ST`) ;
	- deux corrections modifient le rendu des vrais documents : `PBC` lisait `INFRASPLUS_PDF_BODY_TEXT_COLOR` avec `getDolGlobalInt()` (« 50,50,50 » devenait 50 : couleur fausse), maintenant `colorStringToArray()` ; `ET` utilisait `$bodytxtcolor`, non défini, pour la couleur du code QR, maintenant `$this->bodytxtcolor`.
- **Avertissements de `tcpdi_parser.php` (corrigé dans le core de l'instance)** : en analysant certains PDF (CGV exporté par Microsoft Word 365, documents signés, scans, factures fournisseurs : 14 des 80 PDF de l'instance testés), `includes/tcpdi/tcpdi_parser.php:819` (bibliothèque Dolibarr) lit jusqu'à 3 caractères en avance (`@$data[$offset+1]` à `+3`) et dépasse la fin des données. Ces lectures portent un `@` voulu par la bibliothèque : `error_reporting()` vaut 4437 pendant l'avertissement, rien n'est affiché ni journalisé en production ; seul un gestionnaire d'erreurs qui ignore `@` (banc de test, Xdebug avec scream) les voit. Mesuré sur le CGV en instrumentant une copie du parseur : 57 exécutions de la ligne, 55 lectures normales, 2 lectures en avance hors limites, 0 accès non protégé hors limites. Correctif core, à reporter à la main dans la base LTS (fichier hors module) : `$frag = (string) substr($data, $offset, 4); // InfraS change`, même valeur sans lecture hors limites. Équivalence mesurée sur 80 PDF de l'instance (pages et rendu `pdftoppm` identiques, 0 exception) : 65 avertissements, 0 après. Réécrire le CGV avec Ghostscript en PDF 1.4 n'est pas une solution : il en reste. L'avertissement de `tcpdf_barcodes_1d.php` (FT) venait en revanche du modèle (voir plus haut).
- **Banc de test** (hors production, rien écrit en base ni dans les documents) : nomenclature 500 g / 2 kg / 3 pièces et OF de 10 créés dans une transaction annulée (`BOM::create()` / `Mo::create()` sans trigger), `PRODUCT_USE_UNITS` et module mrp forcés en mémoire, hooks limités à infraspackplus et infrastructure, PDF écrits dans un répertoire temporaire et lus par `pdftotext -layout`. Scénarios : OF brouillon, validé, produit (deux lignes consommées pour un même composant), `lines` à `null`, spécimen. Avant : g → « u. », kg → « g », pièces → « Kg » ; après : g, Kg, u.
- **Règle à retenir** : un modèle qui n'imprime qu'une partie des lignes d'un objet (filtre par rôle, par type) ne doit jamais passer `$object` et l'index de boucle à une fonction ou à un hook qui lit `lines[$i]` de l'objet (`pdf_getlineunit`, `pdf_getlineqty`, `pdf_InfraSPlus_separateLine`…) : construire un objet dont `lines` est la liste réellement imprimée. `pdf_InfraSPlus_MRP` est le seul modèle du module qui construit une liste filtrée (`$linesToUse`) ; les autres parcourent `$object->lines` en entier, le modèle BOM compris.
