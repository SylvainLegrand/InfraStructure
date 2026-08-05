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
- Dernière version locale : `21.7.2` (2026-08)
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

**Point de vigilance (depuis v21.5.5)** : `runTrigger()` est appelé par Dolibarr pour **tous** les événements métier, pas seulement ceux sur `societe` — certains objets passés (ex. `TPropaleHist`, historique de devis) n'exposent pas de propriété `element`. La garde `empty($object->element) ||` placée avant le test `in_array($object->element, ['societe'])` est nécessaire pour sortir immédiatement (`return 0`) sans avertissement PHP « Undefined property » sur ces objets.

### Structure du changelog (Changelog structure)

```xml
<changelog>
  <Version Number="21.4.1" MonthVersion="2026-06">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
  </Version>
  <InfraS Downloaded="20260630"/>
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
    0 => "21.4.1",          // Version courante
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
