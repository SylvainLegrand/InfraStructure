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
- Version locale : `18.5.0` (2026-10) — 18.0.0 : lots L0 à L4 (squelette et registre, couche documents, bordereau, feuille de comptage, couche mail) ; 18.0.1 : corrections de l'audit du 2026-09-10 ; 18.1.0 : découpage par maison mère, bordereau enrichi, zone de stockage via InfraSWorkflow ; 18.1.1 : renumérotation de la position dans la famille ; 18.1.2 : libellé de permission raccourci (activation en français impossible) ; 18.1.3 : constante partagée `INFRAS_PHP_EXT_XML` écrite seulement si sa valeur change ; 18.2.0 : colonne « Tiers » dans les listes de fichiers, envoi d'un email par tiers pour les bons depuis la fiche, bouton « Appliquer » du modèle de mail corrigé ; 18.3.0 : répertoires automatiques de la GED ; 18.4.0 : suppression en masse des fichiers ; 18.4.1 : corrections de l'audit du 2026-10-07 (POST obligatoire, tiers introuvable, email libre validé, mode renommage) ; 18.5.0 : feuille de comptage avec la zone en première colonne, colonne « Recomptage » et ligne « Recompté par », toujours en portrait
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
│   │   ├── infrasfiles.lib.php           # registre des objets + helpers (répertoires, droits, chargement, comptage, boîte documents, colonne « Tiers »)
│   │   ├── infrasfilesmail.lib.php       # envoi par tiers : tableau des destinataires injecté dans le formulaire natif, boucle d'envoi
│   │   └── infrasfilesAdmin.lib.php      # lib standard InfraS (changelog, sauvegarde, affichage admin)
│   ├── tpl/
│   │   └── infrasfiles_presend.tpl.php   # écran d'envoi « un email par tiers » (remplace card_presend.tpl.php pour les objets 'mailbythirdparty')
│   └── modules/
│       ├── modinfrasfiles.class.php
│       └── infrasfiles/
│           ├── modules_infrasfiles.php                 # classe de base abstraite des modèles PDF (ModelePDFInfrasFiles)
│           ├── modules_infrasfileswidthdraw.php        # ModelePDFInfrasfileswidthdraw : liste_modeles() type 'infrasfileswidthdraw'
│           ├── modules_infrasfilesinventory.php        # ModelePDFInfrasfilesinventory : liste_modeles() type 'infrasfilesinventory'
│           ├── widthdraw/doc/pdf_bordereau.modules.php # modèle « bordereau » (prélèvement / virement)
│           └── inventory/doc/pdf_comptage.modules.php  # modèle « comptage » (feuille de comptage, zone en première colonne)
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

- **Module parts** : hooks `login`, `document`, `formmail`, `emailtemplates`, `directdebitprevcard`, `inventorycard`, `ecmautocard` (18.3.0, GED ; tout changement de cette liste exige une désactivation / réactivation du module, la liste étant figée dans `MAIN_MODULE_INFRASFILES_HOOKS` à l'activation) ; `models => 1` ; CSS `/infrasfiles/css/infrasfiles.css.php`
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
| `mailbythirdparty` | `true` = l'écran d'envoi envoie **un email par tiers** avec ses propres PDF (la classe fille fournit `infrasfilesGetAddressees()` et `infrasfilesGetMailBatches()`) ; absent = écran natif à un seul mail |
| `nativemailtype` | Type de modèle de mail du formulaire d'envoi que la **fiche native possède déjà** (`inventory` : `product/inventory/card.php` a son bouton, son formulaire et son action, trigger `INVENTORY_SENTBYMAIL`). Le module n'ajoute alors rien sur cette fiche (ni bouton — il y en avait deux de 18.0.0 à 18.1.3 —, ni formulaire, ni action) et se contente de joindre ses PDF au formulaire natif via `getFormMail` (`infrasfiles_element_from_mailtype()` reconnaît aussi ce type). Absent = la fiche n'a pas de formulaire (bons de prélèvement) et le module affiche le sien |
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

La **zone de stockage** de la feuille de comptage n'est pas une option du module (l'ancienne option `ZONE_FIELD` a été retirée le 2026-09-10) : elle suit la configuration de la colonne « Zone » du module **infrasworkflow** (section « Gestion des inventaires » : `INFRASWORKFLOW_DISPLAY_ZONE_COLUMN`, `INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD`, `INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY`), lue par `infrasfiles_inventory_zone_config()`. Dépendance optionnelle : sans infrasworkflow (ou colonne désactivée), pas de colonne « Zone », sans erreur.
Les options sont déclarées dans la clé `options` du registre (`type` = `select` / `text` / `textarea` / `on_off`) et rendues automatiquement sous la ligne du document en admin. Un `select` porte soit `values` (clés de traduction statiques), soit `values_callback` (fonction renvoyant `valeur => libellé`) — `infrasfiles_get_option_values()` fait l'arbitrage, à l'affichage comme à la validation. Un `on_off` est enregistré par son propre interrupteur (`set_<CONST>`), pas par le bouton *Modifier*.

**Feuille de comptage (L3)** : `infrasfiles_inventory_zone_config()` demande à infrasworkflow (`infrasworkflow_inventoryZoneSqlParts()`, chargée par `dol_include_once` seulement si le module est actif) **quelle source** sert de zone — attribut produit en priorité, sinon sous-catégories d'une catégorie parente — pour que la feuille et la colonne « Zone » de la page de saisie montrent toujours la même chose ; `infrasfiles_inventory_zone_list()` renvoie les zones **ordonnées** (`clé => libellé, rang`) : valeurs d'un attribut de type liste dans l'ordre de la liste, ou sous-catégories dans l'**ordre de création** (`rowid`, « ordre chronologique » demandé par le client). `InfrasFilesInventory::infrasfilesFetchLines()` fait ses propres jointures (attribut : `llx_product_extrafields` ; catégorie : sous-requête `MIN(c.rowid)` = plus ancienne sous-catégorie du produit, résolue en libellé / rang) et trie les lignes par rang de zone, puis référence produit, puis lot ; valeurs inconnues (attribut texte, valeur retirée de la liste) après les zones connues par ordre alphabétique, lignes sans zone en dernier. `infrasfilesGetFilters()` renvoie les filtres de l'inventaire (catégories, produit) pour l'en-tête. Le modèle `pdf_comptage` imprime les informations de l'inventaire en en-tête avec le logo (réf, libellé, entrepôt, catégories et produit filtrés, date, statut), puis le tableau : colonne « Zone » en **première position**, renseignée sur chaque ligne (depuis 18.5.0 ; avant, une bande « Zone : … - N référence(s) » au-dessus de chaque groupe), présente seulement si au moins une ligne a une zone ; référence, libellé ; colonnes Entrepôt (inventaire multi-entrepôts) et Lot/série (produits à lots) seulement si nécessaire ; « Stock physique » selon `SHOW_QTY` ; deux cases vides « Quantité relevée » et « Recomptage ». En fin de feuille, le total puis deux lignes « Compté par » et « Recompté par », chacune avec nom, date et signature (`infrasfilesSignLine()`).
**Toujours en portrait** (demande de lucky du 2026-10-07, une version intermédiaire basculait en paysage) : largeurs fixes mesurées sur les titres des 4 langues en gras 9 pt (zone 26, réf 32, entrepôt 26, lot 28, stock 26, relevé 30, recomptage 26 mm), le libellé prend le reste et garde **au moins 40 mm** (sinon toutes les autres colonnes sont réduites en proportion) ; un texte trop long passe à la ligne dans sa cellule, la hauteur de la ligne est celle de la plus haute cellule (`getStringHeight()` sur toutes les colonnes de texte, pas seulement le libellé) ; un titre de colonne trop long passe à la ligne et l'en-tête grandit (`infrasfilesWriteTableHeader()`, commun à tous les modèles du module). infraspackplus fournit `pdf_InfraSPlus_INV` (même contenu, mise en page InfraSPlus, voir *Modèles PDF*).

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
### Colonne « Tiers » des listes de fichiers (18.2.0)
Un bon de 50 tiers produit 50 PDF nommés par code client : la colonne « Tiers » (clé core `ThirdParty`) affiche, dans la section de la fiche **et** dans l'onglet, le tiers (ou la maison mère) à qui chaque PDF est adressé, avec lien vers sa fiche.
- **Rapprochement** : `InfrasFilesWithdraw::infrasfilesGetAddressees()` liste tous les destinataires possibles (tiers de chaque ligne **et** sa maison mère, quel que soit le mode de découpage courant, pour reconnaître aussi des PDF générés sous l'autre mode) avec leur suffixe de fichier (même règle que `infrasfilesGetUnits()`) ; `InfrasFilesDocumentTrait::infrasfilesGetFileAddressees($files)` attribue chaque fichier au destinataire dont `<REF>-<suffixe>` est dans son nom, suffixe le plus long d'abord et jamais comme début d'un code plus long (`CU2609-0006` ne capte pas `CU2609-00065`), préfixe / suffixe InfraSPackPlus (`Bon_de_prelevement_…`, `…_Bon`) tolérés. Un objet sans `infrasfilesGetAddressees()` (inventaires) n'a pas de colonne. Aucune table : les noms sont déterministes, les PDF déjà générés sont reconnus.
- **Affichage** : `infrasfiles_get_file_thirdparty_links($object, $files)` (lib) construit `nom de fichier => Societe::getNomUrl(1, '', 0, 1)` sans `fetch()` (id, nom et code posés à la main, pas d'infobulle : un bon peut avoir des dizaines de tiers) ; `infrasfiles_get_thirdparty_column_script($selector, $links)` insère la colonne en jQuery : cellule après le nom de chaque ligne de fichier (fichier reconnu par le paramètre `file=` de son lien), en-tête après la première cellule d'en-tête, `colspan` des autres lignes (formulaire de génération, lignes des hooks InfraSPackPlus, liens) incrémenté. Appelé par le hook `printCommonFooter` (table `#infrasfiles_docsection table.formdoc`) et par `document.php` (table `#tablelines`). Pourquoi jQuery : `list_of_documents()` n'a qu'un hook « tout remplacer » (`showFilesList`) et `formBuilddocLineOptions` de `showdocuments()` ajouterait une colonne vide sur **toutes** les boîtes documents de Dolibarr. Vérifié en exécutant le script avec jsdom sur les pages rendues (jQuery seul injecté).
### Modèles PDF

Classe de base `ModelePDFInfrasFiles` (`core/modules/infrasfiles/modules_infrasfiles.php`) : `infrasfilesGetFile()` (répertoire + nom), `infrasfilesInitPdf()`, `infrasfilesWriteHead()` (logo ou raison sociale + titre + lignes d'info), `infrasfilesWriteBlock()` (cadre titré), `infrasfilesWriteTableHeader()`, `infrasfilesCheckPageBreak()`, `_pagefoot()` (`pdf_pagefoot()` natif avec texte libre `INFRASFILES_<EL>_FREE_TEXT`), `infrasfilesFinish()` (hook `afterPDFCreation`, `dolChmod`, `$object->last_main_doc`). `update_main_doc_field = 0` pour que le core ne tente pas de mettre à jour la table native.

Une classe intermédiaire par objet (`ModelePDFInfrasfileswidthdraw`, `ModelePDFInfrasfilesinventory`) porte `liste_modeles()` : elle est chargée par `FormFile::showdocuments()` grâce au `modulepart` de la forme `infrasfiles:<docpart>` (fichier `core/modules/infrasfiles/modules_<docpart>.php`, classe `ModelePDF<Docpart>`), mécanisme identique en Dolibarr 18 et 22. Les noms sont préfixés `infrasfiles` pour ne jamais entrer en collision avec une future classe native.

Les modèles se trouvent dans `core/modules/infrasfiles/<element>/doc/pdf_<nom>.modules.php` (chemin relatif `modelspath` du registre, cherché dans tous les modules déclarant `models`). Un module tiers peut donc fournir un modèle sans rien changer ici : **infraspackplus** livre `pdf_InfraSPlus_Bon` (bons de prélèvement / virement, même contenu que `bordereau` avec la mise en page et les options InfraSPackPlus), classe fille de `ModelePDFInfrasfileswidthdraw`, listé et activé dans la page de paramètres de ce module (test `InfrasfilesPdfPresenceTest::testModeleInfraSPlusBonDInfraspackplus`, ignoré si le modèle est absent), et `pdf_InfraSPlus_INV` (feuille de comptage, classe fille de `ModelePDFInfrasfilesinventory`, test `testModeleInfraSPlusINVDInfraspackplus`).
**Incident InfraSPlus_INV (constaté le 2026-10-07)** : le modèle, écrit sur dolinfras le 2026-09-10, n'avait jamais été reporté dans la base LTS. La mise à jour InfraSTools suivante (resynchronisation du module depuis la base avec `--delete-after`) l'a supprimé avec ses intégrations dans infraspackplus, sans sauvegarde possible. Il a été récupéré depuis la transcription de la session Claude qui l'avait écrit, puis repris dans infraspackplus 21.12.0. **Règle** : un modèle livré par un autre module pour InfraSFiles est un fichier de **ce** module ; il doit être reporté dans la base via InfraSTools aussitôt écrit, sinon il disparaît à la mise à jour suivante de l'instance.

### Fichiers : répertoires et téléchargement

Tout est sous le module : `$conf->infrasfiles->multidir_output[entity]/<dirout>/<REF>/` (`infrasfiles_get_output_dir()`), `modulepart = infrasfiles` partout. Le téléchargement passe par le cas générique de `dol_check_secure_access_document()` (permission `infrasfiles→read`) **et** le hook `checkSecureAccess` qui exige la permission native de l'objet (`permread` / `permwrite` du registre) selon le premier segment du chemin (`widthdraw/…`, `inventory/…`). Convention `upload_dir` : pour `actions_builddoc.inc.php` (fiche native) = répertoire de base du module, le paramètre `file` contenant `<dirout>/<REF>/<nom>` ; pour `actions_linkedfiles.inc.php` (`document.php`) = répertoire complet de l'objet.

### Intégration sur la fiche native

- **Onglet « Fichiers joints »** : déclaré dans `$this->tabs` du descripteur pour chaque entrée du registre (`<tabcontext>:+infrasfilesdoc:Documents:main:<condition>:/infrasfiles/document.php?element=<el>&id=__ID__`). Stocké en JSON à l'activation, d'où une condition (`isModEnabled` + `getDolGlobalInt(ENABLED)` + `$user->rights->…`) sans `:` ni virgule — c'est la condition qui suit la page de paramètres, pas la déclaration. Le libellé réutilise la clé core `Documents`.
- **Section en bas de fiche** : hook `printCommonFooter` (contextes `directdebitprevcard`, `inventorycard`, page vérifiée = `cardurl`, chaîne ou liste de pages — pour l'inventaire : la fiche `card.php` **et** la page des lignes `inventory.php`) : imprime la boîte `showdocuments()` (`infrasfiles_get_document_box()`) dans un `div` caché puis la déplace en jQuery après `div.tabsAction` (présent à l'identique en Dolibarr 18, 22, 23, 24) et ajoute le badge de comptage sur l'onglet. Si `div.tabsAction` est lui-même dans un `<form>` (cas de `inventory.php` : formulaire `formrecord` des lignes), la section est insérée **après ce formulaire** — la boîte contient son propre formulaire, et un formulaire imbriqué ferait soumettre celui de la page par le bouton *Générer*. Ce hook **imprime lui-même** et retourne 0 (`printCommonFooter()` n'affiche pas `resprints`, et un retour non nul supprimerait le script natif de pied de page).
- **Actions** : le formulaire de génération poste sur la fiche native (`action=builddoc` / `remove_file`) ; le hook `doActions` charge la classe fille et inclut `core/actions_builddoc.inc.php` — le natif fait le reste (`setDocModel()`, `generateDocument()`, messages, redirection après suppression). Le hook **retourne 1 et vide `$action`** : la fiche inventaire native (18 → 24) inclut elle-même `actions_builddoc.inc.php` dans son bloc `if (empty($reshook))`, et retraiterait `builddoc` sur l'objet natif (`UPDATE llx_inventory SET model_pdf` → `DB_ERROR_NOSUCHFIELD`, incident du 2026-09-09).

## Couche mail (Mail layer — L4)

Tout est natif, la page `document.php` du module sert de support :

- **Bouton « Envoyer par email »** : hook `addMoreActionsButtons` sur les fiches natives (imprimé directement — les fiches n'affichent pas `resprints` de ce hook), visible si l'option `EMAIL` du document est active et que l'utilisateur a le droit d'écriture natif. Lien : la fiche elle-même, `PHP_SELF?id=…&action=presend&mode=init#formmailbeforetitle`, comme une facture (depuis 18.2.0 ; jusqu'en 18.1.3 le bouton renvoyait vers `document.php`, ce que les utilisateurs habitués à envoyer depuis la fiche ne comprenaient pas).
- **Écran d'envoi sur la fiche** (18.2.0) : la fiche des bons (`compta/prelevement/card.php`) n'a aucune gestion d'email et ignore les actions qu'elle ne connaît pas (la fiche inventaire a la sienne : clé `nativemailtype`, le module n'y ajoute que ses PDF) ; le module s'y greffe avec les deux hooks qu'il utilise déjà pour la section documents, **sans substitution de page ni modification du core** : (1) `printCommonFooter` — quand `$action == 'presend'` (lu en `global $action`, le hook ne reçoit pas l'action et après « Appliquer » ou une erreur d'envoi la valeur postée diffère), il imprime le formulaire (`infrasfilesGetPresendForm()` : notre `core/tpl/infrasfiles_presend.tpl.php` pour les objets `mailbythirdparty`, le natif `core/tpl/card_presend.tpl.php` sinon, avec les mêmes variables que `document.php`) dans un `div` caché déplacé sous `div.tabsAction` par un script **synchrone** (pas dans `ready()` : CKEditor et select2 s'initialisent sur `ready()`, déplacer un éditeur déjà initialisé le casse), à la place de la section documents (comportement natif : documents masqués pendant la saisie) ; (2) `doActions` → `infrasfilesDoMailActions()` — `cancel` remet l'action à vide, `modelselected` (« Appliquer ») force `presend`, `infrasfiles_sendbythirdparty` appelle la boucle d'envoi puis redirige vers la fiche (ou réaffiche le formulaire si rien n'est parti), `send` inclut le natif `core/actions_sendmails.inc.php` avec `$paramname = 'id'` (redirection native vers la fiche). Le hook retourne 1 : la fiche native ne traite pas ces actions.
- **Écran d'envoi sur `document.php`** (conservé, plus de bouton qui y mène) : même logique en page, template natif ou du module, `$presendreturnurl` = la page avec son paramètre `element` (le hook `getFormMail` ne corrige `returnurl` que sur cette page ; sur les fiches, le `PHP_SELF?id=` natif est le bon).
- **Envoi natif (inventaires)** : `core/actions_sendmails.inc.php` (`$triggersendname` = `trigger` du registre). Il envoie, gère les pièces jointes, puis déclenche `<OBJET>_SENTBYMAIL` : le trigger natif de l'agenda (branche générique) crée l'événement si `MAIN_AGENDA_ACTIONAUTO_<TRIGGER>` est posé — constante ajoutée par le descripteur, ligne insérée dans `llx_c_action_trigger` par `init()` (idempotent, `INSERT … SELECT … WHERE NOT EXISTS`), libellé `Notify_<TRIGGER>` traduit.
- **Destinataires et pièces jointes** : hook `getFormMail` (contexte `formmail`) — quand `param['models']` est un `mailtype` du registre : (1) `infrasfilesGetRecipients()` de la classe fille complète la liste `withto` / `withtocc` avec les contacts des tiers des lignes du bon (clé = id de contact, résolu nativement par `actions_sendmails` ; une seule requête) — **rien n'est pré-rempli dans le champ libre** (retour utilisateur du 2026-09-09) ; (2) `param['fileinit']` est remplacé par **tous les PDF** du répertoire de l'objet (le template natif n'en attache qu'un, `last_main_doc`, insuffisant en mode « un PDF par tiers / par maison mère ») — attachés au premier affichage (`mode=init`) ou au changement de modèle, si le modèle de mail a `joinfiles` (vrai par défaut et pour nos modèles) ; (3) `param['returnurl']` (bouton Annuler) est corrigé vers la page du module. Les inventaires n'ont pas de tiers : utilisateurs (si `MAIN_MAIL_ENABLED_USER_DEST_SELECT`) ou saisie libre. Vérification d'un envoi réel : `dolibarr.log` en niveau DEBUG trace `CMailFile::CMailfile: filename_list[i]=…` pour chaque pièce jointe.
- **Modèles de mails** : hook `emailElementlist` (contexte `emailtemplates`) ajoute les types `infrasfiles_widthdraw` / `infrasfiles_inventory` à la page Configuration → Emails → Modèles ; `FormMail::fetchAllEMailTemplate()` propose ces types plus les modèles « Tous ». `init()` crée un **modèle par défaut** par document (clés `mailtemplate` + `Label` / `Topic` / `Content` du registre, langue de l'activation, `INSERT … WHERE NOT EXISTS` sur `entity + type_template`) — modifiable ensuite dans l'interface, jamais écrasé.
- **Bordereau** : colonne *Statut* par ligne (libellés natifs `StatusWaiting` / `StatusDebited` ou `StatusCredited` / `StatusRefused` de `LignePrelevement::LibStatut()`), lignes rejetées (`statut = 3`) en rouge, montant rejeté récapitulé sous le total (`InfraSFilesPdfRejected`).
- **Bouton « Appliquer »** du modèle de mail : `document.php` force `$action = 'presend'` quand `modelselected` est posté (comme les fiches natives), sinon le formulaire ne se réaffichait pas ; `cancel` remet `$action` à vide.

### Envoi d'un email par tiers (18.2.0, registre `mailbythirdparty`)
Le natif (`card_presend.tpl.php` + `actions_sendmails.inc.php`) n'envoie qu'à un destinataire. Pour les bons, `document.php` inclut à la place `core/tpl/infrasfiles_presend.tpl.php` et traite l'action `infrasfiles_sendbythirdparty` :
- **Lots** : `InfrasFilesWithdraw::infrasfilesGetMailBatches()` → un lot par destinataire ayant au moins un PDF (via `infrasfilesGetFileAddressees()`) : ses fichiers, ses destinataires proposés (`'thirdparty'` = email du tiers, puis ses contacts actifs avec email, une requête groupée `infrasfilesFetchContacts()` — l'email de la maison mère est lu dans la requête des lignes, `sp.email AS parent_email`), présélection = email du tiers s'il existe, sinon tous ses contacts ; `orphans` = PDF sans destinataire (ancien mode de découpage, fichier déposé à la main), listés et jamais envoyés.
- **Formulaire** : le template réutilise `FormMail::get_form()` natif (expéditeur, modèle de mail, sujet, message, mise en page, Annuler) avec `withto = 0`, `withtofree = 0`, `withfile = 0`, `withtocc = 1` (copie libre commune) et `withtoccuser` (utilisateurs en copie si `MAIN_MAIL_ENABLED_USER_DEST_SELECT`), `param['action'] = 'infrasfiles_sendbythirdparty'` et `param['infrasfiles_bythirdparty'] = 1` (le hook `getFormMail` ne force alors ni destinataires ni pièces jointes). `infrasfiles_mail_thirdparty_row()` construit la ligne « Destinataires par tiers » (case à cocher + « tout cocher », tiers, liste multiple des destinataires + email libre « et/ou », PDF avec lien) et `infrasfiles_mail_inject_row()` l'insère avant la ligne « Copie à » (repli : avant le sujet, puis en tête de table). Le champ caché `infrasfiles_posted` distingue le premier affichage (présélections) d'un réaffichage (valeurs postées conservées : « Appliquer », erreur d'envoi).
- **Envoi** (`infrasfiles_send_by_thirdparty()`, lib `infrasfilesmail.lib.php`) : expéditeur résolu comme le natif (`infrasfiles_mail_from()` : user, company, robot, alias, profils), sujet / message / copie / copie cachée (+ `MAIN_MAIL_AUTOCOPY_INFRASFILES_TO`) communs ; pour chaque tiers coché : destinataires choisis + email libre (tiers sans destinataire → erreur listée, sauté), `Societe::fetch()` posé dans `$object->thirdparty` pour que `getCommonSubstitutionArray()` remplace `__THIRDPARTY_*__` **par tiers**, sujet décodé des entités HTML (ajout InfraS du core natif reproduit), `CMailFile` avec uniquement ses PDF, puis trigger `WIDTHDRAW_SENTBYMAIL` avec `socid` du tiers, `sendtoid` = contacts, `attachedfiles`, `email_*` et `context['actionmsgmore']` (liste des pièces jointes : le trigger natif lit sinon la session) → **un événement agenda par tiers**, rattaché au tiers. `$object->thirdparty` est remis à `null` en sortie (sinon un réaffichage pré-substituerait `__THIRDPARTY_*__` dans l'éditeur). Bilan `InfraSFilesMailSentSummary` + erreurs par tiers ; `document.php` redirige (PRG) dès qu'un envoi a réussi ou qu'il n'y a pas d'erreur, sinon réaffiche le formulaire avec les valeurs postées.
- **Pourquoi ce montage** plutôt qu'un formulaire entièrement réécrit (hook `getFormMail` retournant 1) : modèles de mails, éditeur, substitutions, expéditeurs et mise en page restent natifs ; seule la partie « qui reçoit quoi » est propre au module.
## Suppression en masse des fichiers (18.4.0)
Les deux listes natives n'ont qu'une corbeille par ligne. `infrasfiles_get_mass_delete_script($selector, $mode, $posturl, $moreinputs)` (lib) ajoute en jQuery — même mécanisme que la colonne « Tiers » — une case par ligne de fichier (valeur = chemin relatif du paramètre `file=` du lien, celui que poste la corbeille : `widthdraw/T260901/<nom>.pdf`), le `colspan` des autres lignes +1, une cellule d'en-tête vide dans l'onglet, puis une barre sous le tableau avec « Tout sélectionner » (clé core `SelectAll`) et le bouton « Supprimer la sélection » (actif dès qu'une case est cochée ; **pas de confirmation**, comme la corbeille de la fiche — choix de lucky le 2026-10-07, un `confirm()` navigateur ayant été refusé : soit un vrai `formconfirm` Dolibarr, soit rien). Mode `box` (section de la fiche, hook `printCommonFooter`) : les cases vivent dans le formulaire de génération de `showdocuments()`, le bouton bascule son champ caché `action` de `builddoc` à `infrasfiles_remove_files` avant l'envoi. Mode `tab` (`document.php`) : la liste n'est dans aucun formulaire, le script enveloppe **le bloc table + barre** dans un formulaire POST (jeton, action, `element`, `id`) — piège corrigé le 2026-10-07 : la barre placée hors du formulaire enveloppant rendait le bouton inopérant sur l'onglet (un `submit` hors formulaire ne soumet rien) ; le banc jsdom vérifie désormais `button.closest('form') === checkbox.closest('form')` et le contenu sérialisé du formulaire. Affiché seulement avec le droit d'écriture natif, et jamais en mode renommage de l'onglet (`action=editfile` : la liste native est déjà dans son propre formulaire, le nôtre serait imbriqué).
Côté serveur, `infrasfiles_remove_files($element, $object, $files)` (lib), appelée par `doActions` (fiche) et `document.php` : chaque chemin posté doit satisfaire `infrasfiles_file_belongs_to()` (élément du chemin = élément demandé, directement sous `<dirout>/<REF nettoyée>/`, ni `.` ni `..`, pas de sous-répertoire — testé) et exister, puis `dol_delete_file($chemin, 0, 0, 0, $object)` comme la corbeille (index GED et partages nettoyés). Doublons ignorés, refus et échecs listés par fichier, bilan `InfraSFilesMassDeleteDone`, redirection (PRG) par l'appelant. Le dernier document mémorisé n'a pas à être traité : `infrasfilesLoadDocumentState()` l'efface déjà si son fichier a disparu.
## GED — onglet « Répertoires d'objets » (18.3.0)
`ecm/index_auto.php` a une liste codée en dur, étendue par le hook `addSectionECMAuto` que le core appelle à quatre moments avec des paramètres différents ; `Actionsinfrasfiles::addSectionECMAuto()` y répond depuis le registre, un répertoire par objet activé, nommé `infrasfiles-<dirout>` (`infrasfiles-widthdraw`, `infrasfiles-inventory`) :
| Appel | Paramètres | Réponse |
|---|---|---|
| arbre (`ecm/index_auto.php`, contexte `ecmautocard`) | aucun | liste `position` (300+) / `level` / `module` / `test` (droit natif de lecture) / `label` (clé du registre) / `desc` (clé core `ECMDocsBy`) |
| panneau de droite (`core/ajax/ajaxdirpreview.php`, **inclus par `index_auto.php` dans la même requête**, mode `noajax`, donc même contexte `ecmautocard` ; appelé seul, ce script refuse tout `modulepart` hors `ecm` / `medias` / `website`) | `modulepart` | `module` = nos noms (le core en fait sa liste « automatique ») ; si c'est l'un des nôtres : `directory` = `infrasfiles_get_output_dir($element)` |
| tableau (`FormFile::list_of_autoecmfiles()`) | `modulepart` | `classpath` / `classname` de la classe fille (hérite de `fetch()` par référence et `getNomUrl()`) |
| par fichier | `modulepart` + `fileinfo` | `ref` = premier segment de `relativename` (`<REF>/<fichier>.pdf`) ; `SPECIMEN.pdf` à la racine n'a pas de référence, le core le saute |
Téléchargement et aperçu : le core construit `document.php?modulepart=infrasfiles-widthdraw&file=<REF>/<fichier>` ; `dol_check_secure_access_document()` découpe `module-sousdossier` en `infrasfiles` + `widthdraw/<REF>/<fichier>` (`files.lib.php`, cas générique), ce qui tombe sur `checkSecureAccess` (droit natif de l'objet) sans permission nouvelle. Les bons de prélèvement et de virement partagent `widthdraw/` : un seul répertoire GED (le core liste un dossier entier, pas de filtre par type ; la référence du bon est affichée devant chaque fichier). Écarté : répertoires manuels (`llx_ecm_directories`, sans lien objet) et duplication des fichiers dans les répertoires natifs.
## Hooks et comportement (Hook behavior)

`Actionsinfrasfiles` :

| Hook | Contexte | Rôle |
|------|----------|------|
| `afterLogin` | `login` | Avertissement si la version Dolibarr dépasse la version max supportée |
| `doActions` | `directdebitprevcard`, `inventorycard` | Génération (`builddoc`) et suppression (`remove_file`) depuis la fiche native ; actions du formulaire d'envoi (`presend`, `send`, `infrasfiles_sendbythirdparty`, « Appliquer », « Annuler ») |
| `addMoreActionsButtons` | `directdebitprevcard`, `inventorycard` | Bouton « Envoyer par email » (ouvre le formulaire sur la fiche) |
| `printCommonFooter` | `directdebitprevcard`, `inventorycard` | Section « Fichiers joints » + colonne « Tiers » + badge de l'onglet, ou formulaire d'envoi quand `action = presend` |
| `checkSecureAccess` | `document` | Permission native exigée pour les fichiers `modulepart = infrasfiles` (y compris `infrasfiles-<dirout>` de la GED, découpé par le core) |
| `addSectionECMAuto` | `ecmautocard` | Répertoires automatiques de la GED (un par objet du registre) : arbre, dossier à lister, classe de l'objet, référence déduite du chemin |
| `getFormMail` | `formmail` | URL de retour ; destinataires et pièces jointes des objets du module (écran natif seulement, pas en mode « un email par tiers ») |
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
- **Actions du module qui modifient des données = POST seulement** (`infrasfiles_is_post_request()`, audit du 2026-10-07) : `infrasfiles_remove_files` et `infrasfiles_sendbythirdparty`, dans `doActions` comme dans `document.php`. Le core ne contrôle le jeton d'une action GET que si son nom commence par `del`, `remove`, `set`, `confirm`… (`MAIN_SECURITY_CSRF_WITH_TOKEN` = 2) ; les actions `infrasfiles_*` n'en font pas partie, alors qu'un POST est contrôlé dès le niveau 1. Toute nouvelle action modifiante du module suit la même règle. En GET, l'action est vidée et ignorée.
- Envoi par tiers : un tiers dont la fiche ne se charge plus (supprimé depuis la génération) est sauté (`InfraSFilesMailErrorThirdparty`), sinon ses `__THIRDPARTY_*__` partiraient non remplacés ; les adresses du champ libre sont découpées comme `CMailFile` (virgule, email entre `<>`) et vérifiées par `isValidEmail()` (`infrasfiles_mail_invalid_addresses()`), une adresse invalide bloque l'envoi à ce tiers (`InfraSFilesMailErrorBadEmail`). Messages d'erreur par tiers : la traduction interne passe par `transnoentities()`, la traduction englobante `InfraSFilesMailErrorFor` encode le tout une seule fois (une `trans()` imbriquée encodait deux fois : accents affichés `&eacute;`, corrigé en 18.4.1). `MAIN_MAIL_AUTOCOPY_INFRASFILES_TO` est ajouté en copie cachée de **chaque** email (un par tiers), comme la copie automatique de factures envoyées une à une.
- Formulaire d'envoi sur la fiche : le `div` qui le contient est déplacé par un script **synchrone** imprimé juste après lui — jamais dans `jQuery(document).ready()`, sinon CKEditor (initialisé sur `ready()`, enregistré avant le nôtre) est déplacé après initialisation et son iframe se vide. La section documents n'a pas cette contrainte.
- Envoi par tiers : le formulaire ne doit **jamais** avoir `$object->thirdparty` renseigné au moment de `get_form()` — `getCommonSubstitutionArray()` définirait alors `__THIRDPARTY_*__` et le natif les remplacerait dans l'éditeur avant l'envoi (les variables doivent rester brutes dans le message commun et n'être substituées que par tiers à l'envoi). Les PDF d'un tiers sont attachés depuis le disque, pas depuis la session `listofpaths-<trackid>` du natif.
- Colonne « Tiers » : insertion jQuery, pas de hook natif par ligne ; si Dolibarr change la structure de `showdocuments()` / `list_of_documents()` (première cellule = nom avec lien `file=`), revalider avec le banc jsdom (voir *Tests automatisés*).
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
| `InfrasfilesMailAddressTest.php` | 2 | `infrasfiles_mail_invalid_addresses()` (adresses valides avec ou sans nom, entrées vides ignorées, adresse incomplète, nom sans adresse, point-virgule non reconnu comme séparateur) et `infrasfiles_is_post_request()` (POST, GET, CLI) |
| `InfrasfilesFileBelongsTest.php` | 2 (objet stub) | `infrasfiles_file_belongs_to()` : fichiers de l'objet acceptés (séparateurs Windows normalisés), autre bon / autre élément / racine de l'élément / sous-répertoire refusés, `..`, `.`, chemin absolu, référence avec caractères spéciaux (répertoire = référence nettoyée), objet sans référence |
| `InfrasfilesFileAddresseesTest.php` | 2.5 (objet stub, fichiers factices) | `infrasfilesGetAddressees()` (tiers + maisons mères, suffixes, repli `ID<id>`), `infrasfilesGetFileAddressees()` (noms du module, préfixe / suffixe InfraSPackPlus, PDF de maison mère, codes dont l'un commence par l'autre, ancien PDF global et fichier déposé non rattachés, autre référence), `infrasfiles_get_file_thirdparty_links()` et le script de colonne |
Recette web sans navigateur (2026-10-06, scripts conservés dans le scratchpad de la session, à recréer au besoin) : un banc `render.php` qui exécute une page comme un navigateur connecté (`$_SERVER` posé, `session_start()` **avant** `main.inc.php` avec `dol_login` / `dol_authmode` / `dol_entity` en session, `$_GET` / `$_POST` posés, sortie capturée) ; les scripts jQuery du module sont ensuite exécutés avec `jsdom` (`runScripts: 'outside-only'`, jQuery du core injecté, scripts inline contenant `infrasfiles` évalués, lecture du DOM **après un tick** car `jQuery(document).ready()` est asynchrone). L'envoi est testé en CLI avec `$conf->global->MAIN_DISABLE_ALL_MAILS = 1` en mémoire (construction des mails tracée dans `dolibarr.log` en niveau DEBUG) et le trigger agenda sous transaction annulée.
| `InfrasfilesInventoryZoneTest.php` | 3 (lecture, transaction) | configuration de zone posée en mémoire sur les constantes infrasworkflow : attribut liste (ordre de la liste, tri des lignes), catégories (sous-catégories par ordre de création, lignes cohérentes avec la liste), colonne désactivée (aucune zone) — ignoré sans infrasworkflow ou sans le jeu de test `ZZTEST-INV-02` |
| `InfrasfilesPdfPresenceTest.php` | mode B (spécimens) | bordereau : titre, blocs, RUM, texte libre, filigrane (`pdftotext -raw`), maison mère / échéance / avoir ; feuille de comptage (inventaire typique `typicalInventory()` : un entrepôt, sans lot) : zone en première colonne sans bandeau, zones dans l'ordre, colonnes « Quantité relevée » et « Recomptage » côte à côte, lignes « Compté par » puis « Recompté par », colonne « Stock physique » selon `SHOW_QTY` ; toujours en portrait (`pdfinfo`, spécimen dense et `SHOW_QTY`, les deux modèles) ; modèles `InfraSPlus_Bon` et `InfraSPlus_INV` d'infraspackplus (ignorés si absents) |

Trouvailles issues de la première exécution (corrigées) : `$mysoc` absent hors `main.inc.php` (chargé par `Societe::setMysoc()` dans la classe de base des modèles) ; libellés du spécimen en entités HTML (`transnoentities()`).

**Piège de la double barre — ne PAS normaliser les chemins.** Quand `dolibarr_main_data_root` finit par `/`, Dolibarr construit `$conf-><module>->dir_output` = `<root>/<module>` avec une double barre, et l'index ECM est rapproché du disque par égalité de chaînes brutes `DOL_DATA_ROOT.'/'.filepath` (`completeFileArrayWithDatabaseInfo()`). Une normalisation (`//` → `/`) dans `infrasfiles_get_base_dir()` a provoqué, à chaque affichage de la section Fichiers joints, une tentative de ré-indexation des PDF déjà indexés → `DB_ERROR_RECORD_ALREADY_EXISTS` sur `uk_ecm_files` (incident du 2026-09-09). Règle : `infrasfiles_get_base_dir()` renvoie la valeur brute de `$conf->infrasfiles` (seule une barre finale est retirée) et `checkSecureAccess` compare en brut ; le test `InfrasfilesRegistryTest::testSousRepertoireEtRepertoireDeSortie` le verrouille.
