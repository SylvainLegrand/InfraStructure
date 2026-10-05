# CLAUDE.md — Contexte module infrasfiles

## Aperçu (Overview)

`infrasfiles` est un module externe Dolibarr apportant, aux objets natifs qui n'en disposent pas, les trois fonctions habituelles des documents Dolibarr :

- génération de documents PDF (section « Fichiers joints » en bas de la fiche, avec choix du modèle),
- onglet « Fichiers joints » en haut de la fiche (fichiers générés ou déposés, fichiers liés),
- envoi par email avec le document en pièce jointe.

C'est un **socle générique** : chaque objet pris en charge est une entrée d'un registre déclaratif + un modèle PDF. Objets pris en charge : bons de prélèvement / virement (`widthdraw`), inventaires (`inventory`).

Informations module :

- Éditeur : InfraS - Lucky Ranasolonirina
- Numéro module : `550100`
- Position dans la famille (`module_position`) : `100014` — famille `DOLINFRAS_FAMILY` « Dolibarr LTS by InfraS » quand dolinfras est activé
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Version locale : `18.1.2` (2026-10) — 18.0.0 : lots L0 à L4 (squelette et registre, couche documents, bordereau, feuille de comptage, couche mail) ; 18.0.1 : corrections de l'audit du 2026-09-10 ; 18.1.0 : découpage par maison mère, bordereau enrichi, zone de stockage via InfraSWorkflow ; 18.1.1 : renumérotation de la position dans la famille ; 18.1.2 : libellé de permission raccourci (activation en français impossible)
- Libellés de permission : `$langs->trans()` les encode en HTML (`é` → `&eacute;`, 8 caractères) avant insertion dans `llx_rights_def.libelle` (`varchar(255)`) ; en mode SQL strict, un dépassement bloque l'activation (« Data too long for column 'libelle' », incident du 2026-10-02 : 223 caractères en français devenus 265). Garder chaque libellé sous ~150 caractères dans toutes les langues.
- Dépendance obligatoire : aucune (extension PHP `xml` requise pour le changelog)
- Emplacement : `htdocs/custom/infrasfiles/`

Convention de lecture : explications fonctionnelles en français, identifiants techniques en anglais.

## Structure (Summary)

```text
htdocs/custom/infrasfiles/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrasfilessetup.php
├── class/
│   ├── actions_infrasfiles.class.php          # hooks : doActions (builddoc / remove_file), printCommonFooter (section + badge), checkSecureAccess
│   ├── infrasfilesdocumenttrait.class.php     # trait : état documentaire (llx_infrasfiles_document), setDocModel(), boucle de génération
│   ├── infrasfileswithdraw.class.php          # extends BonPrelevement : generateDocument(), lignes + RIB + documents liés, unités (tiers / ligne)
│   └── infrasfilesinventory.class.php         # extends Inventory : generateDocument(), lignes avec produit et entrepôt
├── config.php
├── core/
│   ├── lib/
│   │   ├── infrasfiles.lib.php           # registre des objets + helpers (répertoires, droits, chargement, comptage, boîte documents)
│   │   └── infrasfilesAdmin.lib.php      # lib standard InfraS (changelog, sauvegarde, affichage admin)
│   └── modules/
│       ├── modinfrasfiles.class.php
│       └── infrasfiles/
│           ├── modules_infrasfiles.php                 # classe de base abstraite des modèles PDF (ModelePDFInfrasFiles)
│           ├── modules_infrasfileswidthdraw.php        # ModelePDFInfrasfileswidthdraw : liste_modeles() type 'infrasfileswidthdraw'
│           ├── modules_infrasfilesinventory.php        # ModelePDFInfrasfilesinventory : liste_modeles() type 'infrasfilesinventory'
│           ├── widthdraw/doc/pdf_bordereau.modules.php # modèle « bordereau » (prélèvement / virement)
│           └── inventory/doc/pdf_comptage.modules.php  # modèle « comptage » (feuille de comptage par zone)
├── css/infrasfiles.css.php
├── document.php                         # onglet « Fichiers joints » générique (?element=&id=)
├── docs/changelog.xml
├── img/
├── langs/{fr_FR,en_US,es_ES,it_IT}/infrasfiles.lang
└── sql/
    ├── llx_infrasfiles_document.sql
    ├── llx_infrasfiles_document.key.sql
    └── update_data.sql
```

## Feuille de route (Roadmap)

| Lot | Contenu | État |
|-----|---------|------|
| L0 | Squelette, descripteur, table, registre, page de paramètres | ✅ livré (18.0.0) |
| L1 | Couche documents : classes filles `generateDocument()`, page `document.php`, onglet, section en bas de fiche (jQuery via `printCommonFooter`), premier modèle PDF | ✅ livré (18.0.0) — testé en CLI, recette web à faire |
| L2 | Greffon bordereau de prélèvement / virement : aperçu SPECIMEN en admin, texte libre de pied de page, filigrane brouillon | ✅ livré (18.0.0) — testé en CLI, recette web à faire |
| L3 | Greffon feuille de comptage d'inventaire (regroupement par zone, case de relevé, quantité théorique optionnelle) | ✅ livré (18.0.0) — testé en CLI, recette web à faire |
| L4 | Couche mail (`card_presend.tpl.php`, modèles de mails, événement agenda), destinataires = contacts des tiers des lignes ou saisie libre | ✅ livré (18.0.0) — testé en CLI, recette web à faire |

## Descripteur module (Module descriptor : `modinfrasfiles`)

- **Module parts** : hooks `login`, `directdebitprevcard`, `inventorycard` ; `models => 1` ; CSS `/infrasfiles/css/infrasfiles.css.php`
- **Répertoires de données** : `/infrasfiles`, `/infrasfiles/temp`, `/infrasfiles/inventory`
- **Constantes par défaut** : `INFRASFILES_WIDTHDRAW_SPLIT_MODE = thirdparty`
- **Permissions** : `paramMenu` (défaut), `paramInfraSFiles`, `paramBkpRest`, `read` (**non** défaut : les fichiers des objets sont servis sur la permission **native** de l'objet via le hook `checkSecureAccess`, qui n'**accorde** l'accès que dans ce cas et laisse sinon la décision générique du core — le core n'applique la décision d'un hook que si elle autorise, d'où l'absence de branche « refus ». La décision générique repose sur `infrasfiles→read` : cette permission ouvre donc **tous** les fichiers du module sans le droit natif, son libellé le dit, à réserver aux administrateurs)
- **Menus** : Outils → InfraS → InfraSFiles → Changelog / Paramètres (positions 75-77)
- **Famille** : `DOLINFRAS_FAMILY` si dolinfras activé, sinon « Modules InfraS »

`init()` : `_load_tables('/infrasfiles/sql/')`, restauration des paramètres sauvegardés, `INFRASFILES_DOL_VERSION`, `INFRASFILES_MAIN_VERSION`.
`remove()` : sauvegarde des paramètres (`infrasfiles_bkup_module()` — requête `name LIKE "INFRASFILES_%"` ; incident du 2026-09-09 : la lib générée par renommage depuis `infrassearch` filtrait encore `INFRASSEARCH_%`, sauvegarde vide et paramètres perdus à chaque réactivation) puis suppression des constantes `INFRASFILES_%` de l'entité ; `init()` les restaure depuis `<data>/infrasfiles/sql/update.<entité>`.

## Registre des objets (`infrasfiles_get_registry()`)

Tableau déclaratif dans `core/lib/infrasfiles.lib.php`, une entrée par objet. Clés :

| Clé | Rôle |
|-----|------|
| `label`, `picto` | Libellé (clé de traduction) et picto |
| `class`, `classpath` | Classe fille du module ajoutant `generateDocument()` (L1) |
| `parentclass`, `parentpath`, `table` | Classe et table natives |
| `modelspath` | Répertoire relatif des modèles PDF, cherché dans tous les modules déclarant `models` |
| `modulepart` | Valeur pour `document.php` / contrôle d'accès : **natif quand il existe** (`prelevement`), `infrasfiles` sinon |
| `dirout` | Répertoire de sortie relatif à `DOL_DATA_ROOT` |
| `tabcontext`, `hookcontext`, `cardurl` | Accrochage sur la fiche native |
| `needmodule` | Modules natifs dont **au moins un** doit être actif (sinon ligne grisée en admin) |
| `permread`, `permwrite` | Droits natifs (tableaux pour `hasRight()`) |
| `langs` | Fichiers de langue à charger avec l'objet |
| `options` | Options spécifiques affichées sur la ligne de l'objet en admin (`type`, `label`, `values`, `default`, `help` facultatif = clé de traduction de l'infobulle, rendue par le 4e paramètre `$help` de `infrasfiles_print_input()` — icône d'aide standard des libs InfraS) |

Extension par un module tiers : hook `infrasFilesRegisterObjects`, contexte `infrasfilesregistry` (HookManager dédié instancié dans la fonction), retour dans `$this->results[element] = définition`.

Helpers : `infrasfiles_const_name($element, $suffix)`, `infrasfiles_module_available($def)`, `infrasfiles_is_enabled($element, $feature)`, `infrasfiles_get_option($element, $option)`, `infrasfiles_get_models($element)`.

## Constantes de configuration (Key settings)

Une série par objet, préfixe `INFRASFILES_<ELEMENT>_` (`WIDTHDRAW` — faute de frappe historique de `BonPrelevement::$element`, à conserver — ou `INVENTORY`) :

| Suffixe | Type | Rôle |
|---------|------|------|
| `ENABLED` | bool | Interrupteur général du document |
| `DOCUMENT` | bool | Génération PDF + section + onglet Fichiers joints |
| `EMAIL` | bool | Bouton et écran d'envoi par email |
| `ADDON_PDF` | string | Modèle par défaut |
| `SPLIT_MODE` | `thirdparty` / `parent` | Bons de prélèvement uniquement : un PDF par tiers (regroupant ses factures, maison mère ignorée) ou un PDF par maison mère (regroupant les tiers qui ont une maison mère **et** l'attribut `infrasfiles_to_parent` coché ; tout autre tiers garde son PDF). L'ancienne valeur `line` (un PDF par ligne, retirée) retombe sur `thirdparty` |
| `FREE_TEXT` | texte (HTML restreint) | Texte libre imprimé en pied de page par `pdf_pagefoot()` (vide = aucun) |
| `WATERMARK` | texte | Filigrane `pdf_watermark()` sur les documents au statut brouillon (vide = aucun) |
| `SHOW_QTY` | bool | Inventaires uniquement : affiche la colonne « Stock physique » (quantité théorique) sur la feuille de comptage, masquée par défaut |

La **zone de stockage** de la feuille de comptage n'est pas une option du module (l'ancienne option `ZONE_FIELD` a été retirée le 2026-09-10) : elle suit la configuration de la colonne « Zone » du module **infrasworkflow** (section « Gestion des inventaires » : `INFRASWORKFLOW_DISPLAY_ZONE_COLUMN`, `INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD`, `INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY`), lue par `infrasfiles_inventory_zone_config()`. Dépendance optionnelle : sans infrasworkflow (ou colonne désactivée), pas de regroupement, sans erreur.
Les options sont déclarées dans la clé `options` du registre (`type` = `select` / `text` / `textarea` / `on_off`) et rendues automatiquement sous la ligne du document en admin. Un `select` porte soit `values` (clés de traduction statiques), soit `values_callback` (fonction renvoyant `valeur => libellé`) — `infrasfiles_get_option_values()` fait l'arbitrage, à l'affichage comme à la validation. Un `on_off` est enregistré par son propre interrupteur (`set_<CONST>`), pas par le bouton *Modifier*.

**Feuille de comptage (L3)** : `infrasfiles_inventory_zone_config()` demande à infrasworkflow (`infrasworkflow_inventoryZoneSqlParts()`, chargée par `dol_include_once` seulement si le module est actif) **quelle source** sert de zone — attribut produit en priorité, sinon sous-catégories d'une catégorie parente — pour que la feuille et la colonne « Zone » de la page de saisie montrent toujours la même chose ; `infrasfiles_inventory_zone_list()` renvoie les zones **ordonnées** (`clé => libellé, rang`) : valeurs d'un attribut de type liste dans l'ordre de la liste, ou sous-catégories dans l'**ordre de création** (`rowid`, « ordre chronologique » demandé par le client). `InfrasFilesInventory::infrasfilesFetchLines()` fait ses propres jointures (attribut : `llx_product_extrafields` ; catégorie : sous-requête `MIN(c.rowid)` = plus ancienne sous-catégorie du produit, résolue en libellé / rang) et trie les lignes par rang de zone, puis référence produit, puis lot ; valeurs inconnues (attribut texte, valeur retirée de la liste) après les zones connues par ordre alphabétique, lignes sans zone en dernier. `infrasfilesGetFilters()` renvoie les filtres de l'inventaire (catégories, produit) pour l'en-tête. Le modèle `pdf_comptage` imprime les informations de l'inventaire en en-tête avec le logo (réf, libellé, entrepôt, catégories et produit filtrés, date, statut), une bande par zone avec le nombre de références, une case vide « Quantité relevée » par ligne, les colonnes Entrepôt (inventaire multi-entrepôts) et Lot/série (produits à lots) seulement si nécessaire, et une zone « Compté par / Date / Signature » en fin de feuille. infraspackplus fournit `pdf_InfraSPlus_INV` (même contenu, mise en page InfraSPlus).

**Aperçu des modèles (SPECIMEN)** : `infrasfiles_load_specimen($element)` instancie la classe fille et appelle `infrasfilesInitAsSpecimen()` (objet rempli sans base : `id = 0`, `specimen = 1`, lignes factices) ; la page admin appelle directement `write_file()` du modèle (pas `generateDocument()`, pour ne rien indexer dans l'ECM) puis redirige vers `document.php?modulepart=infrasfiles&file=<dirout>/SPECIMEN.pdf`. Les classes filles sautent le chargement des lignes et l'écriture de l'état documentaire quand `specimen` est levé.

Cascade : `ENABLED` off → rien n'apparaît ; `DOCUMENT` off → onglet sans encadré de génération ; `EMAIL` off → pas de bouton d'envoi.

Autres : `INFRASFILES_DOL_VERSION`, `INFRASFILES_MAIN_VERSION`, `INFRASFILES_DISABLE_CHECK_VERSION_MIN`, `INFRASFILES_DISABLE_CHECK_VERSION_MAX`, `INFRAS_PHP_EXT_XML` (partagée entre modules InfraS).

## Données / SQL (Data model)

### `llx_infrasfiles_document`

Mémorise, pour un objet natif dépourvu des colonnes `model_pdf` / `last_main_doc`, le dernier modèle utilisé et le dernier fichier généré — **sans modifier les tables natives**.

| Colonne | Type | Description |
|---------|------|-------------|
| `rowid` | int (PK) | Identifiant |
| `entity` | int | Entité |
| `element` | varchar(64) | Clé du registre (`widthdraw`, `inventory`) |
| `fk_element` | int | Id de l'objet natif |
| `model_pdf` | varchar(255) | Dernier modèle choisi |
| `last_main_doc` | varchar(255) | Dernier fichier généré (relatif à `DOL_DATA_ROOT`) |
| `date_creation`, `tms`, `fk_user_modif` | | Traçabilité |

Clé unique `(entity, element, fk_element)`.

Les modèles activés utilisent la table native `llx_document_model` (`type` = `docpart` du registre, ex. `infrasfileswidthdraw`), comme les factures ou les devis.

## Page de paramètres (`admin/infrasfilessetup.php`)

1. Sauvegarde / restauration (droit `paramBkpRest`).
2. **Documents pris en charge** — présentation hiérarchique au format standard InfraS (`infrasfiles_print_input()`, tableau `#` / Description / Statut-Valeur / bouton *Modifier* en `rowspan`) : un sous-titre par entrée du registre, sa ligne *Activé*, puis — seulement quand il est activé — une ligne indentée par option : Génération PDF + Fichiers joints, Envoi par email, et les `options` du registre (`select`, `text`, `textarea`). Le lien qui entoure chaque interrupteur (`action=set_<CONST>`) recharge la page, donc les lignes dépendantes apparaissent ou disparaissent immédiatement. Ligne grisée avec le module manquant si `needmodule` non satisfait. Bouton *Modifier* (`action=update_Options`) pour les options saisies.
3. **Modèles de documents** — un tableau par document dont `DOCUMENT` est actif : modèles trouvés par `infrasfiles_get_models()`, activation `set` / `del` (`addDocumentModel()` / `delDocumentModel()`), défaut `setdoc` (`ADDON_PDF`, active le modèle si besoin). Les trois actions sont **idempotentes** (`infrasfiles_document_model_is_active()` avant tout `INSERT` : un rejeu de l'URL, double clic ou F5, provoquait `DB_ERROR_RECORD_ALREADY_EXISTS` sur `uk_document_model`, incident du 2026-09-10) et se terminent par une redirection vers la page sans paramètre `action` (POST-redirect-GET, `page_y` conservé pour le repositionnement).

## Couche documents (Documents layer — L1)

### Principe

Les objets natifs n'ont pas de `generateDocument()`, et `CommonObject::commonGenerateDocument()` est `protected`. Le module fournit donc une **classe fille par objet** (`InfrasFilesWithdraw extends BonPrelevement`, `InfrasFilesInventory extends Inventory`) qui ajoute `generateDocument()` et partage, via le trait `InfrasFilesDocumentTrait` :

- `infrasfilesLoadDocumentState()` / `infrasfilesSaveDocumentState()` : `model_pdf` et `last_main_doc` lus/écrits dans `llx_infrasfiles_document` ;
- `setDocModel()` surchargé (la table native n'a pas de colonne `model_pdf`) ;
- `infrasfilesGenerate()` : boucle sur des **unités** (une unité = un PDF) et appelle `commonGenerateDocument()` pour chacune avec `$moreparams['infrasfiles_unit']`.

Le chargement standard passe par `infrasfiles_load_object($element, $id)` (include parent + fille, `fetch()`, état documentaire).

### Bons de prélèvement — unités de génération

`InfrasFilesWithdraw::infrasfilesFetchLines()` charge les lignes (`llx_prelevement_lignes`) avec le tiers, sa **maison mère** (champ natif `societe.parent`, coordonnées jointes), l'attribut `infrasfiles_to_parent` (`llx_societe_extrafields`), le RIB **réellement utilisé par la ligne** (`pl.fk_soc_rib`, ajout InfraS du core LTS ; repli sur le RIB par défaut du tiers — `iban_prefix` **déchiffré** avec `dolDecrypt()`, RUM, BIC) et les documents liés (`llx_prelevement` → factures clients / fournisseurs / salaires) avec date d'échéance, tiers facturé et **avoirs appliqués** (`llx_societe_remise_except`, source de type avoir → facture). Les colonnes `fk_soc_rib` et `infrasfiles_to_parent` sont lues seulement si elles existent (`infrasfilesColumnExists()`, un `DESC` en cache) : la requête ne casse jamais sur un Dolibarr sans l'ajout InfraS ou avant la création de l'attribut. Quatre requêtes groupées au total (lignes, RIB, documents, avoirs), jamais une par ligne.
`infrasfilesGetUnits()` construit les unités selon `INFRASFILES_WIDTHDRAW_SPLIT_MODE` : `thirdparty` (un PDF par tiers) ou `parent` (un PDF par maison mère pour les lignes où `infrasfiles_line_addressed_to_parent()` est vrai = maison mère renseignée **et** attribut coché, un niveau seulement ; les autres tiers gardent leur PDF). Chaque unité porte son **destinataire** (`addressee` : id, nom, code, adresse — la maison mère ou le tiers) et `toparent` ; suffixe = code client/fournisseur du destinataire (`ID<id>` à défaut). Fichiers : `<REF>-<suffixe>.pdf`. L'attribut booléen est créé sur les tiers par `init()` (`ExtraFields::addExtraField()`, libellé `InfraSFilesExtraParent` traduit via `langfile`, visible tant que le module est actif, jamais supprimé à la désactivation).

Le modèle `pdf_bordereau` imprime le bloc débiteur / bénéficiaire depuis `addressee` (mention « Maison mère : X » quand le PDF n'est pas adressé à elle), le RIB dans le bloc si toutes les lignes du PDF partagent le même IBAN, sinon l'IBAN sous le nom du tiers de chaque ligne ; colonnes Document / Date / Échéance / Tiers / Statut / Montant, et une sous-ligne grise « Avoir <réf> : -montant » par avoir appliqué.
### Modèles PDF

Classe de base `ModelePDFInfrasFiles` (`core/modules/infrasfiles/modules_infrasfiles.php`) : `infrasfilesGetFile()` (répertoire + nom), `infrasfilesInitPdf()`, `infrasfilesWriteHead()` (logo ou raison sociale + titre + lignes d'info), `infrasfilesWriteBlock()` (cadre titré), `infrasfilesWriteTableHeader()`, `infrasfilesCheckPageBreak()`, `_pagefoot()` (`pdf_pagefoot()` natif avec texte libre `INFRASFILES_<EL>_FREE_TEXT`), `infrasfilesFinish()` (hook `afterPDFCreation`, `dolChmod`, `$object->last_main_doc`). `update_main_doc_field = 0` pour que le core ne tente pas de mettre à jour la table native.

Une classe intermédiaire par objet (`ModelePDFInfrasfileswidthdraw`, `ModelePDFInfrasfilesinventory`) porte `liste_modeles()` : elle est chargée par `FormFile::showdocuments()` grâce au `modulepart` de la forme `infrasfiles:<docpart>` (fichier `core/modules/infrasfiles/modules_<docpart>.php`, classe `ModelePDF<Docpart>`), mécanisme identique en Dolibarr 18 et 22. Les noms sont préfixés `infrasfiles` pour ne jamais entrer en collision avec une future classe native.

Les modèles se trouvent dans `core/modules/infrasfiles/<element>/doc/pdf_<nom>.modules.php` (chemin relatif `modelspath` du registre, cherché dans tous les modules déclarant `models`). Un module tiers peut donc fournir un modèle sans rien changer ici : **infraspackplus** livre `pdf_InfraSPlus_Bon` (bons de prélèvement / virement, même contenu que `bordereau` avec la mise en page et les options InfraSPackPlus), classe fille de `ModelePDFInfrasfileswidthdraw`, listé et activé dans la page de paramètres de ce module (test `InfrasfilesPdfPresenceTest::testModeleInfraSPlusBonDInfraspackplus`, ignoré si le modèle est absent).

### Fichiers : répertoires et téléchargement

Tout est sous le module : `$conf->infrasfiles->multidir_output[entity]/<dirout>/<REF>/` (`infrasfiles_get_output_dir()`), `modulepart = infrasfiles` partout. Le téléchargement passe par le cas générique de `dol_check_secure_access_document()` (permission `infrasfiles→read`) **et** le hook `checkSecureAccess` qui exige la permission native de l'objet (`permread` / `permwrite` du registre) selon le premier segment du chemin (`widthdraw/…`, `inventory/…`). Convention `upload_dir` : pour `actions_builddoc.inc.php` (fiche native) = répertoire de base du module, le paramètre `file` contenant `<dirout>/<REF>/<nom>` ; pour `actions_linkedfiles.inc.php` (`document.php`) = répertoire complet de l'objet.

### Intégration sur la fiche native

- **Onglet « Fichiers joints »** : déclaré dans `$this->tabs` du descripteur pour chaque entrée du registre (`<tabcontext>:+infrasfilesdoc:Documents:main:<condition>:/infrasfiles/document.php?element=<el>&id=__ID__`). Stocké en JSON à l'activation, d'où une condition (`isModEnabled` + `getDolGlobalInt(ENABLED)` + `$user->rights->…`) sans `:` ni virgule — c'est la condition qui suit la page de paramètres, pas la déclaration. Le libellé réutilise la clé core `Documents`.
- **Section en bas de fiche** : hook `printCommonFooter` (contextes `directdebitprevcard`, `inventorycard`, page vérifiée = `cardurl`, chaîne ou liste de pages — pour l'inventaire : la fiche `card.php` **et** la page des lignes `inventory.php`) : imprime la boîte `showdocuments()` (`infrasfiles_get_document_box()`) dans un `div` caché puis la déplace en jQuery après `div.tabsAction` (présent à l'identique en Dolibarr 18, 22, 23, 24) et ajoute le badge de comptage sur l'onglet. Si `div.tabsAction` est lui-même dans un `<form>` (cas de `inventory.php` : formulaire `formrecord` des lignes), la section est insérée **après ce formulaire** — la boîte contient son propre formulaire, et un formulaire imbriqué ferait soumettre celui de la page par le bouton *Générer*. Ce hook **imprime lui-même** et retourne 0 (`printCommonFooter()` n'affiche pas `resprints`, et un retour non nul supprimerait le script natif de pied de page).
- **Actions** : le formulaire de génération poste sur la fiche native (`action=builddoc` / `remove_file`) ; le hook `doActions` charge la classe fille et inclut `core/actions_builddoc.inc.php` — le natif fait le reste (`setDocModel()`, `generateDocument()`, messages, redirection après suppression). Le hook **retourne 1 et vide `$action`** : la fiche inventaire native (18 → 24) inclut elle-même `actions_builddoc.inc.php` dans son bloc `if (empty($reshook))`, et retraiterait `builddoc` sur l'objet natif (`UPDATE llx_inventory SET model_pdf` → `DB_ERROR_NOSUCHFIELD`, incident du 2026-09-09).

## Couche mail (Mail layer — L4)

Tout est natif, la page `document.php` du module sert de support :

- **Bouton « Envoyer par email »** : hook `addMoreActionsButtons` sur les fiches natives (imprimé directement — les fiches n'affichent pas `resprints` de ce hook), visible si l'option `EMAIL` du document est active et que l'utilisateur a le droit d'écriture natif. Lien : `document.php?element=…&id=…&action=presend&mode=init`.
- **Écran d'envoi** : `document.php` inclut `core/tpl/card_presend.tpl.php` (dernier PDF pré-attaché depuis `last_main_doc`, sinon fichier le plus récent de `<diroutput>/<ref>`), avec `$modelmail` = `mailtype` du registre, `$defaulttopic` = `mailtopic` (clé avec `__REF__`), `$trackid` = `trackid` + id.
- **Envoi** : `core/actions_sendmails.inc.php` inclus dans la section Actions (`$triggersendname` = `trigger` du registre, `$paramname = 'element=<el>&id'` pour que la redirection native conserve l'élément). Il envoie, gère les pièces jointes, puis déclenche `<OBJET>_SENTBYMAIL` : le trigger natif de l'agenda (branche générique) crée l'événement si `MAIN_AGENDA_ACTIONAUTO_<TRIGGER>` est posé — constante ajoutée par le descripteur, ligne insérée dans `llx_c_action_trigger` par `init()` (idempotent, `INSERT … SELECT … WHERE NOT EXISTS`), libellé `Notify_<TRIGGER>` traduit.
- **Destinataires et pièces jointes** : hook `getFormMail` (contexte `formmail`) — quand `param['models']` est un `mailtype` du registre : (1) `infrasfilesGetRecipients()` de la classe fille complète la liste `withto` / `withtocc` avec les contacts des tiers des lignes du bon (clé = id de contact, résolu nativement par `actions_sendmails` ; une seule requête) — **rien n'est pré-rempli dans le champ libre** (retour utilisateur du 2026-09-09) ; (2) `param['fileinit']` est remplacé par **tous les PDF** du répertoire de l'objet (le template natif n'en attache qu'un, `last_main_doc`, insuffisant en mode « un PDF par tiers / par maison mère ») — attachés au premier affichage (`mode=init`) ou au changement de modèle, si le modèle de mail a `joinfiles` (vrai par défaut et pour nos modèles) ; (3) `param['returnurl']` (bouton Annuler) est corrigé vers la page du module. Les inventaires n'ont pas de tiers : utilisateurs (si `MAIN_MAIL_ENABLED_USER_DEST_SELECT`) ou saisie libre. Vérification d'un envoi réel : `dolibarr.log` en niveau DEBUG trace `CMailFile::CMailfile: filename_list[i]=…` pour chaque pièce jointe.
- **Modèles de mails** : hook `emailElementlist` (contexte `emailtemplates`) ajoute les types `infrasfiles_widthdraw` / `infrasfiles_inventory` à la page Configuration → Emails → Modèles ; `FormMail::fetchAllEMailTemplate()` propose ces types plus les modèles « Tous ». `init()` crée un **modèle par défaut** par document (clés `mailtemplate` + `Label` / `Topic` / `Content` du registre, langue de l'activation, `INSERT … WHERE NOT EXISTS` sur `entity + type_template`) — modifiable ensuite dans l'interface, jamais écrasé.
- **Bordereau** : colonne *Statut* par ligne (libellés natifs `StatusWaiting` / `StatusDebited` ou `StatusCredited` / `StatusRefused` de `LignePrelevement::LibStatut()`), lignes rejetées (`statut = 3`) en rouge, montant rejeté récapitulé sous le total (`InfraSFilesPdfRejected`).

## Hooks et comportement (Hook behavior)

`Actionsinfrasfiles` :

| Hook | Contexte | Rôle |
|------|----------|------|
| `afterLogin` | `login` | Avertissement si la version Dolibarr dépasse la version max supportée |
| `doActions` | `directdebitprevcard`, `inventorycard` | Génération (`builddoc`) et suppression (`remove_file`) depuis la fiche native |
| `addMoreActionsButtons` | `directdebitprevcard`, `inventorycard` | Bouton « Envoyer par email » |
| `printCommonFooter` | `directdebitprevcard`, `inventorycard` | Section « Fichiers joints » + badge de l'onglet |
| `checkSecureAccess` | `document` | Permission native exigée pour les fichiers `modulepart = infrasfiles` |
| `getFormMail` | `formmail` | Destinataires des objets du module + URL de retour |
| `emailElementlist` | `emailtemplates` | Types de modèles de mails du module |

## Points d'attention (Watchpoints)

- `BonPrelevement::$element` vaut `widthdraw` (faute de frappe historique) : cette chaîne est la clé partout (répertoire, hook, type de modèle de mail) — ne pas « corriger ».
- `commonGenerateDocument()` est `protected` : seules des classes filles peuvent l'appeler (L1).
- `llx_prelevement_bons` et `llx_inventory` n'ont ni `model_pdf` ni `last_main_doc` : `setDocModel()` doit être surchargé dans les classes filles (écriture dans `llx_infrasfiles_document`), et le modèle PDF ne doit pas demander la mise à jour de `last_main_doc` (`update_main_doc_field = 0`).
- `inventoryAdminPrepareHead()` et `inventoryPrepareHead()` utilisent le même contexte `inventory` : un onglet déclaré apparaît aussi dans la configuration Inventaire de l'admin — à neutraliser côté page (test sur l'id).
- Téléchargement des fichiers : `modulepart = prelevement` est géré nativement par `dol_check_secure_access_document()` ; `modulepart = infrasfiles` passe par le cas générique, qui exige la permission `read` du module.
- Insertion de la section en bas de fiche (L1) : ancrage jQuery sur `div.tabsAction` via le hook `printCommonFooter`, structure vérifiée identique en Dolibarr 18, 22, 23 et 24.
- Destinataires du mail (L4) : la liste propose les contacts des tiers des lignes du bon (hook `getFormMail`), rien n'est pré-rempli dans le champ libre ; un bon peut concerner plusieurs tiers.
- Fichiers du module : un fichier est toujours traité avec la permission de **son** élément, déduit du premier segment de son chemin par `infrasfiles_element_from_file()` — suppression depuis une fiche native (`remove_file` refuse un fichier d'un autre élément que celui de la fiche) et téléchargement (`checkSecureAccess`). Audit du 2026-09-10.
- `document.php` : les templates natifs construisent leurs URL en `PHP_SELF?id=` ; `$moreparam` (formulaire d'ajout de fichier / lien) et `$backtopage` (redirection après suppression) portent le paramètre `element`, sinon la page répond « accès refusé » (bug corrigé le 2026-09-10).
- `infrasfiles_test_php_ext()` (appelée par le constructeur du descripteur) n'écrit la constante partagée `INFRAS_PHP_EXT_XML` que si sa valeur change (depuis 18.1.3) : la réécriture systématique (DELETE + INSERT dans `llx_const`) pouvait entrer en conflit avec une transaction concurrente (incident d'octobre 2026 avec Infrastructure : lignes de document perdues en silence). Ne jamais réintroduire d'écriture inconditionnelle de constante dans du code exécuté à chaque requête, à chaque connexion ou pendant une transaction métier

## Conventions de développement

Règles InfraS du serveur (`/etc/claude-code/CLAUDE.md`) : tabs, corps indenté d'un tab, en-têtes copyright + docfile, pas de texte brut (tout via `$langs->trans()`), clés de traduction alignées et identiques dans les 4 langues, changelog `fix → chg → add` avec `Downloaded` à jour, numéro de version `X.Y.Z` où `X` = version minimum de Dolibarr (`18`).

Tests de non-régression : désactiver / réactiver le module, vérifier `llx_infrasfiles_document`, les constantes `INFRASFILES_*`, la page de paramètres avec et sans les modules Prélèvement / Stock.

## Tests automatisés (`test/phpunit/`, skill `infras-module-selftest`)

Lancement : `bash /etc/claude-code/skills/infras-module-selftest/scripts/run-tests.sh <htdocs> infrasfiles` (PHPUnit partagé `/opt/infras/infrasmagicktools/phpunit/`, jamais de Composer). Aucune écriture persistante : constantes posées en mémoire et restaurées, lecture en base sous transaction annulée, PDF générés dans un répertoire temporaire supprimé.

| Fichier | Niveau | Couvre |
|---------|--------|--------|
| `InfrasfilesRegistryTest.php` | 2 | `infrasfiles_const_name()`, structure du registre, sous-répertoires / répertoire de sortie (sans double barre), cascade `ENABLED` → `DOCUMENT` / `EMAIL`, valeurs par défaut et listes d'options, clés `label` / `help` du registre toutes traduites (aucune clé orpheline affichée brute en admin), `infrasfiles_element_from_mailtype()` |
| `InfrasfilesWithdrawUnitsTest.php` | 2.5 (objet stub) | `InfrasFilesWithdraw::infrasfilesGetUnits()` : un PDF par tiers (maison mère ignorée) / par maison mère (regroupe seulement les tiers rattachés **et** cochés, cas « 10 tiers, 8 cochés → 3 PDF » en réduit), règle `infrasfiles_line_addressed_to_parent()`, repli de l'ancien mode `line`, repli `ID<fk_soc>`, suffixes sûrs pour le système de fichiers |
| `InfrasfilesInventoryZoneTest.php` | 3 (lecture, transaction) | configuration de zone posée en mémoire sur les constantes infrasworkflow : attribut liste (ordre de la liste, tri des lignes), catégories (sous-catégories par ordre de création, lignes cohérentes avec la liste), colonne désactivée (aucune zone) — ignoré sans infrasworkflow ou sans le jeu de test `ZZTEST-INV-02` |
| `InfrasfilesPdfPresenceTest.php` | mode B (spécimens) | bordereau : titre, blocs, RUM, texte libre, filigrane (`pdftotext -raw`), maison mère / échéance / avoir ; feuille de comptage : bandes de zone, case de relevé, signature, colonne « Stock physique » selon `SHOW_QTY` ; modèles `InfraSPlus_Bon` et `InfraSPlus_INV` d'infraspackplus (ignorés si absents) |

Trouvailles issues de la première exécution (corrigées) : `$mysoc` absent hors `main.inc.php` (chargé par `Societe::setMysoc()` dans la classe de base des modèles) ; libellés du spécimen en entités HTML (`transnoentities()`).

**Piège de la double barre — ne PAS normaliser les chemins.** Quand `dolibarr_main_data_root` finit par `/`, Dolibarr construit `$conf-><module>->dir_output` = `<root>/<module>` avec une double barre, et l'index ECM est rapproché du disque par égalité de chaînes brutes `DOL_DATA_ROOT.'/'.filepath` (`completeFileArrayWithDatabaseInfo()`). Une normalisation (`//` → `/`) dans `infrasfiles_get_base_dir()` a provoqué, à chaque affichage de la section Fichiers joints, une tentative de ré-indexation des PDF déjà indexés → `DB_ERROR_RECORD_ALREADY_EXISTS` sur `uk_ecm_files` (incident du 2026-09-09). Règle : `infrasfiles_get_base_dir()` renvoie la valeur brute de `$conf->infrasfiles` (seule une barre finale est retirée) et `checkSecureAccess` compare en brut ; le test `InfrasfilesRegistryTest::testSousRepertoireEtRepertoireDeSortie` le verrouille.
