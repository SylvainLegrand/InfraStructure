# CLAUDE.md — Contexte module infrasworkflow

## Aperçu (Overview)

`infrasworkflow` est un module externe Dolibarr orienté automatisation et contrôle des processus métier :

- gestion centralisée et inter-modules des attributs supplémentaires avec chaînes de propagation,
- génération automatique de factures d'acompte depuis les devis signés,
- contrôle d'ouverture des comptes clients (champs obligatoires),
- contrôle de validation des fiches produits,
- création de commandes fournisseurs depuis les factures fournisseurs,
- gestion des lignes produits dans les contrats (onglet dédié, duplication, réordonnancement, extrafields),
- mécanisme de substitution de pages selon version Dolibarr,
- amélioration de l'inventaire natif (produits sans stock, exclusion des obsolètes, colonne « Zone »).

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand
- Numéro module : `500080`
- Licence : GPL v3+
- Compatibilité Dolibarr : `21.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `21.8.0` (2026-09)
- Dépendance obligatoire : extension PHP `xml`
- Intégration optionnelle : `infraspackplus` (modèles PDF, notes publiques)
- Emplacement : `htdocs/custom/infrasworkflow/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrasworkflow/
├── CLAUDE.md
├── LICENSE
├── README.md
├── config.php
├── admin/
│   ├── about.php
│   ├── changelog.php
│   ├── extrafields.php
│   └── infrasworkflowsetup.php
├── ajax/
│   ├── dragdropupload.php
│   └── getproductcategory.php
├── class/
│   ├── actions_infrasworkflow.class.php
│   └── infrasworkflow_propal.class.php
├── core/
│   ├── lib/
│   │   ├── infrasworkflow.lib.php
│   │   └── infrasworkflowAdmin.lib.php
│   ├── modules/
│   │   └── modinfrasworkflow.class.php
│   ├── tpl/
│   │   ├── actions_extrafields.inc.php        → adminextrafields/actions_v21 à v24
│   │   ├── admin_extrafields_add.tpl.php      → adminextrafields/add_v21 à v24
│   │   ├── admin_extrafields_edit.tpl.php     → adminextrafields/edit_v21 à v24
│   │   ├── admin_extrafields_view.tpl.php     → adminextrafields/view_v21 à v24
│   │   ├── adminextrafields/                  (view/add/edit/actions × v21, v22, v23, v24)
│   │   ├── objectline_create.tpl.php          → linecreates/v21.tpl.php à v24.tpl.php
│   │   ├── linecreates/                       (v21, v22, v22-DolInfraS, v23, v24)
│   └── triggers/
│       └── interface_99_modinfrasworkflow_Infrasworkflow.class.php
├── css/
│   └── infrasworkflow.css.php
├── docs/
│   └── changelog.xml
├── img/
├── langs/
│   ├── en_US/infrasworkflow.lang
│   ├── es_ES/infrasworkflow.lang
│   ├── fr_FR/infrasworkflow.lang
│   └── it_IT/infrasworkflow.lang
├── sql/
│   └── data.sql
├── substitutionpages/
│   ├── dlb210x/
|       ├── contrat/card.php
|       ├── product/inventory/inventory.php  
│   ├── dlb220x/
|       ├── contrat/card.php
|       ├── product/inventory/inventory.php  
│   ├── dlb220x-Easya/
|       ├── contrat/card.php
|       ├── product/inventory/inventory.php  
│   ├── dlb230x/
|       ├── contrat/card.php
|       ├── product/inventory/inventory.php  
│   └── dlb240x/contrat/
|       ├── contrat/card.php
|       ├── product/inventory/inventory.php  
├── tabs/
│   └── infrasworkflow_contrat_products_tab.php
```

## Descripteur module (Module descriptor : `modinfrasworkflow`)

Dans `core/modules/modinfrasworkflow.class.php` :

- **Module parts** :
	- hooks : `main`, `login`, `formfile`, `globalcard`, `thirdpartycomm`, `paymentdao`, `contractcard`, `supplierinvoicelist`, `contratdao`, `contractdao`, `ordercard`, `orderlist`
	- triggers : `1` (activé)
	- CSS : `/infrasworkflow/css/infrasworkflow.css.php`
- **Dépendances** : aucune dépendance module obligatoire
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Tabs** : `contract:+tabProduct` → onglet "Produits du contrat" (condition : `contrat` actif + `INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE == 1`)
- **Permissions** : 6 permissions
	- `paramMenu` (défaut : activé)
	- `paramInfraSWorkflow`
	- `paramBkpRest`
	- `use`
	- `paramExtraFields`
	- `accessmedias` (défaut : activé) — "Accéder aux médias" : navigateur de médias de l'éditeur WYSIWYG

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrasworkflow/sql/')`)
2. Restauration des constantes module (`infrasworkflow_restore_module`)
3. Construction de `$listTypeKey` : mapping de ~56 types d'éléments Dolibarr → codes modules (propal, commande, facture, contrat, fournisseur, projet, ficheinter, expedition, reception, mrp, bom, product, stock, agenda, banque, societe, partnership, user, salaries, expensereport, holiday, hrm, recruitment, ecm, ticket, knowledgemanagement, resource, eventorganization, adherent, don)
4. Enregistrement de 3 constantes automatiques : `INFRASWORKFLOW_LISTTYPEKEY`, `INFRASWORKFLOW_DOL_VERSION`, `INFRASWORKFLOW_MAIN_VERSION`
5. Appel `_init($sql, $options)`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue :

1. Sauvegarde module (`infrasworkflow_bkup_module`)
2. Suppression des constantes `INFRASWORKFLOW_%` de `llx_const` pour l'entité courante
3. Appel `_remove($sql)`

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `actions_infrasworkflow.class.php` pour les hooks de workflows (acomptes, contrôles tiers/produits, substitutions, commandes fournisseurs, normalisation des rangs de contrat),
- `infrasworkflow_propal.class.php` pour la recherche récursive des factures liées aux devis,
- `infrasworkflow.lib.php` pour la logique métier transverse (~40 fonctions),
- `infrasworkflowAdmin.lib.php` pour les fonctions d'administration (915 lignes, 22 fonctions),
- le trigger `interface_99_modinfrasworkflow_Infrasworkflow.class.php` pour les événements automatiques.

## Hooks et comportement (Hook behavior)

La classe `ActionsInfraSWorkflow` intervient sur 12 contextes :

- **`main`** / **`login`** (`updateSession`, `afterLogin`) : substitution de pages — redirige les pages Dolibarr standard vers des versions personnalisées
- **`main`** (`updateSession`) : accès au navigateur de médias de l'éditeur WYSIWYG ("Parcourir le serveur") pour les non-admins — sur les URL `/core/filemanagerdol/`, si `INFRASWORKFLOW_MEDIAS_BROWSER_ALL_USERS` est activée et que l'utilisateur a le droit `accessmedias`, octroi d'un droit `website->write` virtuel (requête courante uniquement, avec `$user->loadRights()` préalable car `main.inc.php` ne charge les droits qu'après ce hook) pour passer le contrôle de `core/filemanagerdol/connectors/php/config.inc.php`
- **`main`** (`printCommonFooter`) : injection d'une zone de drag-and-drop sur la barre de titre du tableau "Fichiers joints" des onglets `*/document.php` (tous types d'objets) si `INFRASWORKFLOW_DOCUMENTS_DRAGDROP` activé. Sur un produit/service, si `INFRASWORKFLOW_DOCUMENTS_DRAGDROP_PRODUCT_NO_MASK` est aussi activé, l'URL AJAX du drop est réécrite vers `ajax/dragdropupload.php` (endpoint dédié qui force `FileUpload::$options['saving_doc_mask']=''` pour conserver le nom original du fichier, et qui indexe chaque fichier déposé dans `llx_ecm_files` avec son objet source via une classe `FileUploadEcmIndexed extends FileUpload` — voir piège ci-dessous)

	⚠️ **Piège — la classe native `FileUpload` n'indexe jamais les fichiers dans `llx_ecm_files`** : contrairement à l'envoi par le formulaire classique (`sendit` → `dol_add_file_process()` → `addFileIntoDatabaseIndex(..., $object)`), le drag-and-drop natif Dolibarr (`core/ajax/fileupload.php`) déplace le fichier sans créer de ligne d'index. La ligne est créée plus tard, à l'affichage de l'onglet, par la réconciliation `completeFileArrayWithDatabaseInfo()` — mais **sans** `src_object_type`/`src_object_id` (`gen_or_uploaded='unknown'`). Conséquence : le fichier reste invisible pour toute fonctionnalité filtrant sur cet index, notamment les extrafields `sellist` du type `ecm_files:filename:rowid::((src_object_type:=:'product') AND (src_object_id:=:'$ID$'))` (ex. champ "Image principale" `art_image` du module onepagebasket), même après activation du partage public (qui ne remplit que la colonne `share`). L'endpoint du module corrige ce manque depuis la v21.5.3 en surchargeant `handleFileUpload()` pour appeler `addFileIntoDatabaseIndex()` avec l'objet après chaque upload réussi. Limite : le drag-and-drop **sans** l'option `NO_MASK` (autres objets, ou produits si la sous-option est désactivée) passe par l'endpoint core natif et reste non indexé avec l'objet source
- **`globalcard`** / **`thirdpartycomm`** (`addMoreActionsButtons`) : injection de boutons d'actions :
	- bouton de génération du 2e acompte sur devis signés
	- boutons de contrôle d'ouverture de compte client (restrictions prospect/client)
	- bouton de création de commande fournisseur sur facture fournisseur
	- bouton d'envoi d'email pour contrats provisoires
- **`formfile`** (`formConfirm`) : interception du dialogue de clôture "signé" du devis pour ajouter les champs de génération d'acompte (date, conditions, validation) avec affichage/masquage JS dynamique
- **`paymentdao`** (`createPayment`) : régénération du PDF facture lors du classement "payé"
- **`contractcard`** (`afterFetchLines`) : normalisation automatique des rangs des lignes produits du contrat (détection doublons, NULL/0, séquences non-consécutives) si `INFRASWORKFLOW_AUTO_NORMALIZE_CONTRACT_RANKS` activé
- **`globalcard`** (`formAddObjectLine`) : rendu du formulaire d'ajout de ligne dans l'onglet "Produits du contrat" (voir section *Onglet Produits du contrat* pour le détail du mécanisme et un piège de chemin de template à connaître)
- **`contratdao`** (`getTooltipContent`) / **`contractdao`** (`getNomUrl`) : recalcul du montant du contrat affiché dans l'infobulle sur les seules lignes de service (voir section *Montant du contrat dans l'infobulle*)
- **`ordercard`** (`formObjectOptions`) : affichage du type de tiers (dictionnaire `llx_c_typent`) dans le bloc "Autres attributs" de la création de commande, si `INFRASWORKFLOW_ORDER_SHOW_THIRDPARTY_TYPE` activé — champ texte désactivé `#infrasworkflow_thirdparty_type`, toujours affiché : « Non défini » (`Undefined`) tant que le formulaire n'a pas de tiers, sinon le libellé résolu par `infrasworkflow_getThirdpartyTypeLabel($socid)` (`infrasworkflow.lib.php` : `Societe::fetch()` → `typent_id` = `llx_societe.fk_typent`, puis `FormCompany::typent_array(0)` sur `llx_c_typent`, code traduit). Pas d'AJAX : le remplissage après le choix du client dans la liste repose sur le rechargement de page natif (`RELOAD_PAGE_ON_CUSTOMER_CHANGE`, POST `changecompany=1` + `socid`) ; si `RELOAD_PAGE_ON_CUSTOMER_CHANGE_DISABLED` est posé, le champ reste « Non défini » jusqu'à la création du brouillon

	⚠️ **Piège — `socid` vaut 0 à l'ouverture du formulaire depuis le menu** : le tiers n'est présent dans la requête (`?socid=`) que pour une création depuis la fiche tiers ou un document d'origine, ou après le rechargement de page qui suit le choix du client dans la liste. La première version du hook sortait sur `if ($socid <= 0) return 0;` et la ligne n'apparaissait donc pas sur ce formulaire. Constaté le 2026-09-03 : l'option paraissait inopérante alors que le seul test avec un tiers sélectionné avait eu lieu 16 s avant son activation, et les deux tests suivants portaient sur le formulaire sans tiers. La ligne est désormais toujours affichée (« Non défini » sans tiers)
- **`orderlist`** (`doMassActions` → `massCreateBills`) : réimplantation de l'action de masse native "Générer les factures" (`confirm_createbills`) si `INFRASWORKFLOW_MASS_CREATEBILLS` activé — voir section *Facturation en masse des commandes*
- **`supplierinvoicelist`** (`addMoreMassActions`, `doPreMassActions`, `doMassActions`) : ajout de l'action de masse "Classer payées" sur la liste des factures fournisseurs si `INFRASWORKFLOW_SUPPLIER_INVOICE_MASS_PAID` activé (factures validées et non payées uniquement)

## Trigger et comportement (Trigger behavior)

Le trigger `InterfaceInfrasworkflow` filtre les éléments `propal`, `facture`, `societe`, `product`, `contrat`, `contratdet`, `order_supplier`, `inventory` :

| Événement | Action |
|-----------|--------|
| `PROPAL_CLOSE_SIGNED` | Contrôle compte client + génération automatique 1er acompte |
| `BILL_CREATE` (standard) | Liaison acomptes → facture finale + copie notes publiques du devis |
| `BILL_CREATE` (acompte) | Copie notes publiques du devis (si infraspackplus activé) |
| `BILL_CREATE` (avoir) | Copie de la note publique de la facture d'origine vers l'avoir, plus la sélection des mentions InfraSPackPlus si elle est mémorisée par document (`INFRASWORKFLOW_CREDIT_NOTE_TRANSFER_NOTES`) |
| `BILL_VALIDATE` (standard) | Workflow de validation facture (`infrasworkflow_factureValidation`) |
| `BILL_PAYED` | Régénération du PDF facture au classement "payé" |
| `COMPANY_CREATE` / `COMPANY_MODIFY` | Contrôle compte client — type prospect/client selon complétude des champs |
| `PRODUCT_CREATE` / `PRODUCT_MODIFY` | Contrôle validation produit — mise hors vente si champs obligatoires manquants |
| `CONTRACT_VALIDATE` | Activation automatique de tous les services du contrat |
| `OBJECT_LINK_INSERT` (contrat + facture) | Copie des extrafields de la facture vers le contrat à la 1ère liaison |
| `LINECONTRACT_INSERT` | Force `product_type = 0` sur une ligne de contrat pointant un produit du catalogue |
| `INVENTORY_VALIDATED` | Amélioration de l'initialisation de l'inventaire : purge des produits obsolètes / hors vente et hors achat, ajout des produits sans stock à quantité attendue 0 — voir *Amélioration de l'inventaire natif* |

## Données / SQL (Data model)

Pas de tables personnalisées. Le module stocke sa configuration dans `llx_const` et opère sur les tables Dolibarr existantes (`llx_extrafields`, `llx_*_extrafields`, etc.).

Données initiales : `sql/data.sql` avec ~48 constantes dans `llx_const`.

## Constantes de configuration (Key settings)

### Général

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_DOCUMENTS_DRAGDROP` | `0` | Activer une zone de glisser-déposer sur la barre de titre du tableau "Fichiers joints" des onglets `*/document.php` (tous types d'objets) |
| `INFRASWORKFLOW_DOCUMENTS_DRAGDROP_PRODUCT_NO_MASK` | `0` | Sous-option de `INFRASWORKFLOW_DOCUMENTS_DRAGDROP`. Lors d'un drag-and-drop sur un produit ou service, conserver le nom original du fichier (désactive le préfixage automatique `REF-` côté `FileUpload::$options['saving_doc_mask']`). L'endpoint dédié indexe aussi chaque fichier déposé dans `llx_ecm_files` avec `src_object_type`/`src_object_id` (ce que la classe native `FileUpload` ne fait jamais) |

### Gestion des médias

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_MEDIAS_BROWSER_ALL_USERS` | `0` | Autoriser les utilisateurs non-admin disposant du droit `accessmedias` à utiliser le navigateur de médias de l'éditeur WYSIWYG (section "Gestion des médias" de la page de configuration) |

### Workflow acomptes

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_EXF_DEPOSIT` | `acpt1` | Code extrafield montant 1er acompte (devis) |
| `INFRASWORKFLOW_EXF_SECOND_DEPOSIT` | `acpt2` | Code extrafield montant 2e acompte (devis) |
| `INFRASWORKFLOW_CREATE_FIRST_AUTO_DEPOSIT` | vide | Créer automatiquement le 1er acompte à la signature |
| `INFRASWORKFLOW_VALIDATE_FIRST_AUTO_DEPOSIT` | vide | Valider automatiquement l'acompte généré |
| `INFRASWORKFLOW_LINK_DEPOSITS_TO_FINAL_INVOICE` | vide | Lier automatiquement les acomptes à la facture finale |
| `INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT` | vide | Extrafields à exclure du transfert devis → acompte |
| `INFRASWORKFLOW_TRANSFER_PUBLIC_NOTES` | `0` | Copier les notes publiques du devis vers la facture |

### Workflow factures

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_INVOICE_VALIDATION` | `0` | Activer le workflow de validation facture |
| `INFRASWORKFLOW_INVOICES_CLASSIFY_BILLED_PROPALS` | `0` | Classer les devis comme facturés à la validation |
| `INFRASWORKFLOW_INVOICE_REGENARATION_ON_CLASSIFY_PAID` | `0` | Régénérer le PDF au classement "payé" |
| `INFRASWORKFLOW_USE_DOCUMENT_MODEL_INFRASPLUS_FR` | vide | Modèle PDF (nécessite infraspackplus ≥ 15.7.0) |
| `INFRASWORKFLOW_CREATE_ORDER_SUPPLIER` | `0` | Créer commande fournisseur depuis facture fournisseur |
| `INFRASWORKFLOW_CREDIT_NOTE_TRANSFER_NOTES` | `0` | Copier la note publique de la facture vers l'avoir associé : le texte du champ `note_public` (sans dépendance à infraspackplus), plus la sélection des mentions « note publique » InfraSPackPlus si `INFRASPLUS_PDF_OPTION_listnotep == 'doc'`. Voir *Transfert des notes vers l'avoir* pour les trois pièges de rattachement, de nommage et de portée |
| `INFRASWORKFLOW_ORDER_SHOW_THIRDPARTY_TYPE` | `0` | Afficher le type de tiers (dictionnaire `llx_c_typent`, via `llx_societe.fk_typent`) sur la création de commande — champ désactivé toujours présent, « Non défini » tant que le tiers n'est pas connu |
| `INFRASWORKFLOW_MASS_CREATEBILLS` | `0` | Remplacer l'action de masse native "Générer les factures" (liste des commandes) par une version améliorée — voir *Facturation en masse des commandes* |
| `INFRASWORKFLOW_MASS_CREATEBILLS_COPY_NOTES` | `0` | Sous-option de `INFRASWORKFLOW_MASS_CREATEBILLS`. Copier les notes publique/privée de la commande vers la facture (uniquement en mode 1 commande = 1 facture) |
| `INFRASWORKFLOW_MASS_CREATEBILLS_SET_AUTHOR` | `0` | Sous-option de `INFRASWORKFLOW_MASS_CREATEBILLS`. Forcer l'utilisateur ayant lancé l'action de masse comme créateur (`fk_user_author`) des factures générées |
| `INFRASWORKFLOW_MASS_CREATEBILLS_DEDUP_REF` | `0` | Sous-option de `INFRASWORKFLOW_MASS_CREATEBILLS`. Ne pas dupliquer la référence de la commande d'origine entre l'en-tête de la facture (`ref_client`) et sa note, en mode 1 commande = 1 facture |

### Contrôle tiers / clients

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_MERGE_CLEAN_DUPLICATE_SUPPLIER_PRICES` | `0` | Supprimer les prix fournisseurs en doublon du tiers absorbé avant une fusion de tiers (évite l'échec de `Societe::mergeCompany()` sur la clé unique de `llx_product_fournisseur_price`) |
| `INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT` | `0` | Activer le contrôle d'ouverture de compte |
| `INFRASWORKFLOW_CONTROL_CLIENT_CODE_FIELDS` | `0` | Exiger le code client |
| `INFRASWORKFLOW_CONTROL_TVA_ASSUJ_FIELDS` | `0` | Exiger l'assujettissement TVA |
| `INFRASWORKFLOW_CONTROL_TVA_FIELDS` | `0` | Exiger le numéro de TVA |
| `INFRASWORKFLOW_CONTROL_CODE_COMPTA_FIELDS` | `0` | Exiger le code comptable |
| `INFRASWORKFLOW_CONTROL_MODE_REGLEMENT_FIELDS` | `0` | Exiger le mode de règlement |
| `INFRASWORKFLOW_CONTROL_COND_REGLEMENT_FIELDS` | `0` | Exiger les conditions de règlement |
| `INFRASWORKFLOW_CONTROL_DATE_SIGN_FIELDS` | `0` | Exiger la date de signature |
| `INFRASWORKFLOW_CONTROL_SIRET_FIELDS` | `0` | Exiger le SIRET |
| `INFRASWORKFLOW_CONTROL_MAIL_FIELDS` | `0` | Exiger l'email |
| `INFRASWORKFLOW_CONTROL_COUNTRY_FIELDS` | `0` | Exiger le pays |
| `INFRASWORKFLOW_CONTROL_PARENT_FIELDS` | `0` | Exiger la maison mère |
| `INFRASWORKFLOW_CONTROL_CONTACT_FIELDS` | `0` | Exiger un contact |
| `INFRASWORKFLOW_DISABLE_PROSPECTSCUSTOMERS` | vide | Désactiver le type Prospect/Client |

### Contrôle produits

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_PRODUCT_VALIDATION_CONTROL` | vide | Activer le contrôle de validation produit |
| `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_FIELDS` | vide | Exiger le code comptable vente |
| `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_INTRA_FIELDS` | vide | Exiger le code comptable vente intra-communautaire |
| `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_EXPORT_FIELDS` | vide | Exiger le code comptable vente export |
| `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_FIELDS` | vide | Exiger le code comptable achat |
| `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_INTRA_FIELDS` | vide | Exiger le code comptable achat intra-communautaire |
| `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_EXPORT_FIELDS` | vide | Exiger le code comptable achat import |
| `INFRASWORKFLOW_CONTROL_CUSTOM_CODE_FIELDS` | vide | Exiger le code douanier / Code SH |
| `INFRASWORKFLOW_CONTROL_WEIGHT_FIELDS` | vide | Exiger le poids |
| `INFRASWORKFLOW_CONTROL_COUNTRY_ID_FIELDS` | vide | Exiger le pays d'origine |
| `INFRASWORKFLOW_CONTROL_DESIRED_STOCK_FIELDS` | vide | Exiger le stock désiré |
| `INFRASWORKFLOW_CONTROL_DEFAULT_WAREHOUSE_FIELDS` | vide | Exiger l'entrepôt par défaut |

### Options contrats

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE` | `0` | Copier les lignes produits de la source vers le contrat |
| `INFRASWORKFLOW_CONTRACT_FROM_VALIDATED_PROPAL` | `0` | Autoriser la création d'un contrat depuis un devis dès le statut validé (sans attendre la signature) |
| `INFRASWORKFLOW_CONTRACT_EMAIL_PROV` | `0` | Autoriser l'envoi d'email pour les contrats provisoires |
| `INFRASWORKFLOW_CONTRACT_SERVICE_AUTO` | `0` | Activer automatiquement les services à la validation |
| `INFRASWORKFLOW_DEFAULT_COMMERCIAL_SIGNATURE` | vide | Signataire par défaut des contrats |
| `INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT` | vide | Codes extrafields à copier de la facture vers le contrat |
| `INFRASWORKFLOW_AUTO_NORMALIZE_CONTRACT_RANKS` | vide | Normaliser automatiquement les rangs des lignes produits |
| `INFRASWORKFLOW_CONTRACT_LINE_EXTRAFIELDS_SELECTION` | vide | Extrafields de lignes à afficher dans l'onglet Produits |

### Gestion des inventaires

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_DISPLAY_SORTED_EMPTY_STOCK` | `0` | À l'initialisation d'un inventaire, ajouter une ligne (qty attendue 0) pour chaque produit répondant aux filtres mais sans stock enregistré dans l'entrepôt |
| `INFRASWORKFLOW_HIDE_ITEMS_TAGGED_OBSOLETE` | `0` | À l'initialisation d'un inventaire, exclure les produits hors vente **et** hors achat, ainsi que ceux de la catégorie « Obsolète » |
| `INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY` | vide | Id de la catégorie produit « Obsolète » (ses sous-catégories sont incluses) ; vide = seul le critère vente/achat s'applique |
| `INFRASWORKFLOW_DISPLAY_ZONE_COLUMN` | `0` | Afficher la colonne « Zone » sur les lignes de saisie de l'inventaire (active la page de substitution `product/inventory/inventory.php`) |
| `INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD` | vide | Code de l'extrafield produit contenant la zone. **Exclusif** avec `INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY` |
| `INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY` | vide | Id de la catégorie parente dont les sous-catégories sont les zones de localisation. **Exclusif** avec `INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD`, et prioritaire sur lui si les deux sont renseignés |

### Gestion extrafields

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_EXTRAFIELDS_TRASHMODE` | `0` | Mode corbeille (désactiver avant supprimer) |
| `INFRASWORKFLOW_CLONE_EXTRAFIELDS` | `1` | Activer le clonage d'extrafields |
| `INFRASWORKFLOW_EXF_LIST_TYPE` | `1` | Type d'affichage liste extrafields |
| `INFRASWORKFLOW_TEST_EXF_V20PLUS` | `0` | Tester la compatibilité v20+ |

### Substitution de pages

| Constante | Défaut | Rôle |
|-----------|--------|------|
| `INFRASWORKFLOW_PS_ACTIVE_CONTRAT_CARD` | `1` | Activer la substitution de la fiche contrat |
| `INFRASWORKFLOW_PS_ACTIVE_PRODUCT_INVENTORY_INVENTORY` | `0` | Activer la substitution de la page de saisie d'inventaire (synchronisée automatiquement avec `INFRASWORKFLOW_DISPLAY_ZONE_COLUMN` par la page d'administration) |

### Constantes automatiques (runtime)

| Constante | Rôle |
|-----------|------|
| `INFRASWORKFLOW_LISTTYPEKEY` | Mapping des ~56 types d'éléments vers leurs modules |
| `INFRASWORKFLOW_DOL_VERSION` | Version Dolibarr détectée |
| `INFRASWORKFLOW_MAIN_VERSION` | Version du module |

## Pages d'administration (Admin pages)

| Fichier | Onglet | Rôle |
|---------|--------|------|
| `admin/infrasworkflowsetup.php` | Paramètres | Configuration des workflows : acomptes, contrôles tiers, contrôles produits, options contrats, commande fournisseur, régénération facture, transferts extrafields, propagation native Dolibarr, substitution de pages, sauvegarde/restauration |
| `admin/extrafields.php` | ExtraFields | Gestion centralisée inter-modules des extrafields avec propagation, audit, TRASHMODE, clonage |
| `admin/about.php` | À propos | README.md rendu en HTML, informations support |
| `admin/changelog.php` | Changelog | Historique des versions depuis le XML, vérification de mise à jour |

### Sections de paramétrage (`infrasworkflowsetup.php`)

1. **Sauvegarde / Restauration** (niveau 2 uniquement)
2. **Général** (option drag-and-drop documents joints + sous-option produit/service sans préfixage, en tête de tableau)
3. **Gestion des acomptes** (nécessite `facture` + `propal`)
4. **Gestion des factures**
5. **Gestion des tiers** (nécessite `societe`)
6. **Gestion des produits** (nécessite `product`)
7. **Gestion des contrats** (nécessite `contrat`)
8. **Gestion des inventaires** (nécessite `stock`)
9. **Gestion des extrafields**
10. **Propagation des extrafields** (constantes natives Dolibarr)

## Système de gestion des extrafields (Extrafields management)

Le module fournit un système centralisé d'administration inter-modules des attributs supplémentaires remplaçant la gestion native par module de Dolibarr.

### Chaînes de propagation

Les extrafields peuvent être propagés entre types d'éléments liés. Le mapping `INFRASWORKFLOW_LISTTYPEKEY` couvre ~56 types d'éléments à travers ~30 modules Dolibarr.

### Opérations CRUD avec propagation

- **Ajout** (`confirm_add`) : créer un extrafield sur un type principal et optionnellement propager vers les types liés via des cases à cocher
- **Clonage** (`confirm_clone`) : dupliquer un extrafield vers d'autres types avec adaptation automatique nom/libellé
- **Mise à jour** (`confirm_update`) : modifier les propriétés et propager aux types liés
- **Suppression** (`confirm_delete`) : avec support TRASHMODE (0 = suppression directe, 1 = désactiver d'abord puis supprimer)

### Templates versionnés (core/tpl/)

Les templates sont routés par version via des fichiers wrapper qui détectent `DOL_VERSION` et incluent le fichier spécifique à la version :

- `actions_extrafields.inc.php` → `adminextrafields/actions_v21` à `actions_v24`
- `admin_extrafields_view.tpl.php` → `adminextrafields/view_v21` à `view_v24`
- `admin_extrafields_add.tpl.php` → `adminextrafields/add_v21` à `add_v24`
- `admin_extrafields_edit.tpl.php` → `adminextrafields/edit_v21` à `edit_v24`
- `objectline_create.tpl.php` → `linecreates/v21.tpl.php` à `v24.tpl.php`

## Système de substitution de pages (Page substitution)

Le module peut remplacer des pages Dolibarr standard par des versions personnalisées stockées dans `substitutionpages/` :

| Répertoire | Version Dolibarr | Pages |
|-----------|-----------------|-------|
| `dlb210x/` | 21.0.x | `contrat/card.php` |
| `dlb220x/` | 22.0.x | `contrat/card.php`, `product/inventory/inventory.php` |
| `dlb220x-Easya/` | 22.0.x (Easya) | `contrat/card.php` |
| `dlb230x/` | 23.0.x | `contrat/card.php` |
| `dlb240x/` | 24.0.x | `contrat/card.php` |

La substitution est configurée via les constantes `INFRASWORKFLOW_PS_ACTIVE_*` et routée via `infrasworkflow_get_substitution_url()`. Les hooks `updateSession` / `afterLogin` interceptent les chargements de pages et redirigent quand une substitution est configurée et active.

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / hooks / templates / triggers :

1. Désactiver puis réactiver le module
2. Vérifier les constantes module (`INFRASWORKFLOW_*`)
3. Vérifier le fonctionnement des hooks (substitution pages, boutons d'action, formulaires de confirmation)
4. Vérifier les triggers (acomptes, contrôles tiers/produits, services contrat)
5. Vérifier l'onglet "Produits du contrat" (ajout, édition, duplication, réordonnancement)
6. Vérifier l'administration des extrafields (CRUD, propagation, audit, clonage)
7. Tester un cas de génération d'acompte réel (signature devis → facture acompte)

⚠️ **Piège — un contexte de hook ajouté au descripteur reste inerte sans réactivation** :
`HookManager::initHooks()` ne consulte pas le descripteur mais `$conf->modules_parts['hooks']`,
construit depuis la constante `llx_const.MAIN_MODULE_INFRASWORKFLOW_HOOKS`, écrite uniquement à
l'**activation** du module. Un contexte déclaré dans `module_parts['hooks']` mais absent de cette
constante ne provoque aucune erreur : le module n'est simplement jamais appelé sur les pages
concernées, et la fonctionnalité paraît « ne rien faire » alors que son code est correct. Constaté
le 2026-09-03 sur dolinfras : `ordercard` et `orderlist` (ajoutés en 21.6.0 pour l'affichage du type
de tiers et la facturation en masse) manquaient dans la constante, les deux options étaient donc
sans effet. Contrôle rapide :

```sql
SELECT value FROM llx_const WHERE name = 'MAIN_MODULE_INFRASWORKFLOW_HOOKS';
```

## Tests automatisés (Automated tests)

`test/phpunit/` couvre les options récentes (skill `/infras-module-selftest`, PHPUnit partagé de
`/opt/infras/infrasmagicktools/phpunit/`). Exécution :

```bash
bash /etc/claude-code/skills/infras-module-selftest/scripts/run-tests.sh /mnt/web/dolinfras/htdocs infrasworkflow
```

Tout s'exécute dans une transaction annulée (`$db->begin()` / `$db->rollback()`) : aucune donnée ne
subsiste, les tiers de test sont préfixés `ZZTEST-SELFTEST-` pour rester identifiables si un rollback
échouait.

⚠️ **Piège — `massCreateBills()` termine par `header()` + `exit`** : appelée directement depuis
PHPUnit, cette sortie tue le process de test avant toute assertion (et saute le `rollback`). Les
scénarios de facturation en masse sont donc joués par `helpers/masscreatebills_scenario.php` dans un
process séparé, qui collecte l'état de la base dans un `register_shutdown_function()` (exécuté par
`exit`, la connexion DB étant encore ouverte), écrit un JSON, puis annule la transaction. Le test
PHPUnit se contente d'asserter sur ce JSON. À réutiliser pour tout hook qui redirige.

⚠️ **Limite — génération de PDF non vérifiable en CLI** : le répertoire des documents est en
`www-data:www-data`, l'utilisateur qui lance PHPUnit ne peut pas y écrire. Les tests vérifient que
l'étape `core/actions_builddoc.inc.php` est bien atteinte (messages Dolibarr relevés dans
`$_SESSION['dol_events']`) ; la présence réelle du PDF doit être contrôlée depuis l'interface web.

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrasworkflow_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module applique des substitutions de pages selon version Dolibarr (répertoire `substitutionpages/`)
- Le module auto-désactive si la version Dolibarr est inférieure au minimum requis
- Les constantes `INFRASWORKFLOW_*` sont nombreuses (~48 en data.sql + ~8 runtime) ; éviter les changements massifs sans test fonctionnel
- L'intégration `infraspackplus` est optionnelle mais active des fonctionnalités supplémentaires (modèle PDF, notes publiques)

## Dernières mises à jour (Recent updates)

Voir `docs/changelog.xml` pour l'historique complet des versions.

## Notes techniques (Technical notes)

### Workflow de génération des acomptes (Deposit invoice workflow)

Le module gère la génération automatique et manuelle de factures d'acompte à montant fixe depuis les devis signés.

**1er acompte — Mode automatique** (`INFRASWORKFLOW_CREATE_FIRST_AUTO_DEPOSIT` activé) :
```
Devis signé (PROPAL_CLOSE_SIGNED)
    ↓
Trigger → infrasworkflow_firstAccountAuto()
    ↓
Vérifie : extrafield montant 1er acompte > 0 et ≤ total_ttc
    ↓
    Si montant > total_ttc → réouverture du devis + warning
    ↓
infrasworkflow_createdepositfromorigin()
    ↓
Création facture acompte (type=3) avec répartition multi-TVA
    ↓
Optionnel : validation automatique (INFRASWORKFLOW_VALIDATE_FIRST_AUTO_DEPOSIT)
```

**1er acompte — Mode manuel** (dialogue de confirmation enrichi) :
```
L'utilisateur clique « Classer signée » sur le devis
    ↓
formConfirm() enrichit le dialogue natif :
    → Checkbox « Générer acompte », date, conditions de paiement, validation auto
    → JavaScript : affichage conditionnel des champs (.showonlyifsigned, .showonlyifgeneratefirstdeposit)
    ↓
doActions() traite confirm_closeas :
    → infrasworkflow_createdepositfromorigin() avec le montant fixe
    → closeProposal() + redirection vers la facture d'acompte
```

**2ème acompte** (bouton d'action sur devis signé) :
```
Condition : exactement 1 dépôt existant + extrafield 2ème acompte > 0
    ↓
addMoreActionsButtons() affiche le bouton « Générer 2ème acompte »
    ↓
formConfirm() affiche : date, conditions de paiement, validation auto
    ↓
doActions() calcule : restant_dû = total_ttc - 1er_acompte
    → Vérifie 2ème acompte ≤ restant_dû
    → infrasworkflow_createdepositfromorigin()
```

### Moteur de création d'acompte (`infrasworkflow_createdepositfromorigin`)

La fonction centrale de génération d'acompte à montant fixe :

1. **Validations** : date, conditions de paiement, montant > 0 et ≤ total_ttc
2. **Copie des propriétés** : socid, projet, mode_reglement, cond_reglement, etc. depuis l'objet source
3. **Copie des extrafields** : tous sauf ceux listés dans `INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT`
4. **Répartition multi-TVA** (si `MAIN_DEPOSIT_MULTI_TVA`) :
   - Regroupe les lignes source par taux de TVA
   - Calcule la part proportionnelle du montant fixe par taux
   - Correction d'arrondi si différence ≥ 0.01€ redistribuée sur le dernier groupe
5. **TVA unique** : une seule ligne avec le taux de la première ligne source
6. **Descriptions détaillées** : chaque ligne inclut la liste des produits source correspondants
7. **Hook `createFrom`** : point d'extensibilité pour les autres modules
8. **Validation optionnelle** : validation automatique de la facture d'acompte

### Liaison acomptes → facture finale (`infrasworkflow_linkDepositsToFinalInvoice`)

```
BILL_CREATE (facture standard depuis un devis)
    ↓
infrasworkflow_linkDepositsToFinalInvoice()
    ↓
Infrasworkflow_InvoiceArrayList($propal_id, recursive=false)
    → Mode NON-récursif pour éviter les acomptes d'objets intermédiaires
    ↓
Pour chaque facture d'acompte trouvée :
    → Recherche dans societe_remise_except (remises à déduire)
    → INSERT dans la facture finale
    → Dédoublonnage via tableau $discounts_added
    ↓
Transaction begin()/commit()/rollback()
    ↓
Régénération du PDF après liaison
```

### Contrôle d'ouverture de compte client (Customer account control)

Le mécanisme empêche la création de commandes et factures pour les tiers non qualifiés :

**12 champs contrôlables** (chacun avec sa constante on/off) :

| Champ | Constante | Propriété vérifiée |
|-------|-----------|-------------------|
| Code client | `INFRASWORKFLOW_CONTROL_CLIENT_CODE_FIELDS` | `code_client` |
| Assujettissement TVA | `INFRASWORKFLOW_CONTROL_TVA_ASSUJ_FIELDS` | `tva_assuj` |
| Numéro TVA | `INFRASWORKFLOW_CONTROL_TVA_FIELDS` | `tva_intra` |
| Code comptable | `INFRASWORKFLOW_CONTROL_CODE_COMPTA_FIELDS` | `code_compta_client` |
| Mode de règlement | `INFRASWORKFLOW_CONTROL_MODE_REGLEMENT_FIELDS` | `mode_reglement_id` |
| Conditions de règlement | `INFRASWORKFLOW_CONTROL_COND_REGLEMENT_FIELDS` | `cond_reglement_id` |
| Date de signature | `INFRASWORKFLOW_CONTROL_DATE_SIGN_FIELDS` | `date_creation` |
| SIRET | `INFRASWORKFLOW_CONTROL_SIRET_FIELDS` | `idprof2` |
| Email | `INFRASWORKFLOW_CONTROL_MAIL_FIELDS` | `email` |
| Pays | `INFRASWORKFLOW_CONTROL_COUNTRY_FIELDS` | `country_id` |
| Maison mère | `INFRASWORKFLOW_CONTROL_PARENT_FIELDS` | `parent` |
| Contact | `INFRASWORKFLOW_CONTROL_CONTACT_FIELDS` | `phone` |

**Flux de contrôle** :
```
Événement sur un tiers (COMPANY_CREATE / COMPANY_MODIFY)
    ↓
infrasworkflow_thirdpartyAccountControl($thirdparty)
    → Vérifie chaque champ activé
    ↓
    Si tous les champs OK → ne change rien (le tiers peut être passé Client)
    Si un champ manquant :
        → infrasworkflow_getThirdpartyLinkedPropal() : devis signés existants ?
        → Si oui : set type = ProspectClient(3)
        → Sinon : set type = Prospect(2)
    ↓
Impact : les hooks addMoreActionsButtons() masquent les boutons
    de création commande/facture pour les Prospect et ProspectClient
```

### Contrôle de validation des produits (Product validation control)

**11 champs contrôlables** pour valider une fiche produit à la vente :

| Champ | Constante |
|-------|-----------|
| Code comptable vente | `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_FIELDS` |
| Code comptable vente intra | `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_INTRA_FIELDS` |
| Code comptable vente export | `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_EXPORT_FIELDS` |
| Code comptable achat | `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_FIELDS` |
| Code comptable achat intra | `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_INTRA_FIELDS` |
| Code comptable achat import | `INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_EXPORT_FIELDS` |
| Code douanier / Code SH | `INFRASWORKFLOW_CONTROL_CUSTOM_CODE_FIELDS` |
| Poids | `INFRASWORKFLOW_CONTROL_WEIGHT_FIELDS` |
| Pays d'origine | `INFRASWORKFLOW_CONTROL_COUNTRY_ID_FIELDS` |
| Stock désiré | `INFRASWORKFLOW_CONTROL_DESIRED_STOCK_FIELDS` |
| Entrepôt par défaut | `INFRASWORKFLOW_CONTROL_DEFAULT_WAREHOUSE_FIELDS` |

**Flux** :
```
PRODUCT_CREATE / PRODUCT_MODIFY
    ↓
infrasworkflow_productValidationControl($product)
    → Vérifie chaque champ activé
    ↓
    Si un champ manquant → infrasworkflow_setProductAsOffSale()
        → SQL UPDATE product SET tosell = 0
```

### Création de commande fournisseur depuis facture fournisseur

```
Facture fournisseur en mode brouillon, aucun objet lié
    ↓
addMoreActionsButtons() affiche le bouton « Créer commande fournisseur »
    ↓
doActions() traite l'action :
    1. Crée un CommandeFournisseur, copie toutes les lignes
    2. Valide la commande (statut 1)
    3. Approuve la commande (statut 2)
    4. Classe la facture « facturée »
    5. Crée le lien commande ↔ facture fournisseur
    ↓
Redirection vers la commande fournisseur créée
```

### Transfert des notes vers l'avoir (Credit note notes transfer)

`infrasworkflow_cloneNotefromInvoice()` reporte sur un avoir les notes publiques de sa facture
d'origine, en deux temps indépendants. Déclenchée par le trigger `BILL_CREATE` sur un objet de type
`Facture::TYPE_CREDIT_NOTE`, sous la seule condition `INFRASWORKFLOW_CREDIT_NOTE_TRANSFER_NOTES` :

1. **Texte de la note publique** (`llx_facture.note_public`) — recopié de la facture source vers
   l'avoir par `setValueFrom()`, sans dépendance à infraspackplus, et **uniquement si l'avoir n'en
   porte pas déjà une** : une note saisie sur le formulaire de création n'est jamais écrasée, et le
   cas des avoirs de situation — où `Facture::createFromCurrent()` a déjà recopié la note — est
   naturellement neutre. Le dernier argument de `setValueFrom()` est vidé pour ne pas horodater
   `fk_user_modif` sur un objet encore en cours de création.
2. **Sélection des mentions « note publique » InfraSPackPlus** — recopie de la clé `listnotep` de la
   constante `INFRASPLUS_PDF_PARAMS_facture_DOC_<id>` (les autres clés déjà posées sur l'avoir,
   `listfreet` par exemple, sont préservées par fusion). Ne s'exécute que si
   `isModEnabled('infraspackplus')` **et** `INFRASPLUS_PDF_OPTION_listnotep == 'doc'`.

⚠️ **Piège 1 — un avoir n'est PAS lié à sa facture dans `llx_element_element`** : le rattachement est
porté par la seule colonne `llx_facture.fk_facture_source`. `Facture::createFromCurrent()` (le chemin
utilisé par le bouton de création d'avoir) n'ajoute un lien `facture`→`facture` que dans la branche
`TYPE_SITUATION`. Chercher la facture source via `fetchObjectLinked()` / `linkedObjectsIds['facture']`
ne trouve donc jamais rien pour un avoir — contrairement au transfert devis→facture
(`infrasworkflow_cloneNotePfromPropal()`), où le lien `propal`→`facture` existe bien. La fonction lit
`fk_facture_source` en priorité et ne retombe sur les objets liés qu'à défaut (factures de situation).
Défaut corrigé en 21.6.0.

⚠️ **Piège 2 — il n'existe pas de famille de notes `CREDITNOTE_*`** : les familles InfraSPackPlus sont
`PROPOSAL_PUBLIC_NOTE*`, `ORDER_PUBLIC_NOTE*`, `INVOICE_PUBLIC_NOTE*`… (cf. `infraspackplus/admin/notes.php`).
Un avoir **est** un objet `facture` : ses notes sont donc les mêmes `INVOICE_PUBLIC_NOTE*` que la
facture source, et il n'y a aucun préfixe à convertir. La conversion `INVOICE_` → `CREDITNOTE_`
produisait des noms de constantes inexistants (0 ligne `CREDITNOTE%` en base), donc une liste filtrée
toujours vide. Les codes sont désormais repris tels quels, en ne gardant que ceux dont la constante
existe encore. Défaut corrigé en 21.6.0.

⚠️ **Piège 3 — la sélection des mentions InfraSPackPlus n'est transférable que mémorisée « par
document »** : chaque paramètre d'impression InfraSPackPlus a une portée de mémorisation propre
(`INFRASPLUS_PDF_OPTION_<param>` = `user`, `doc`, `type`, `cust` ou `none`), qui détermine dans
quelle constante `INFRASPLUS_PDF_PARAMS_<element>_<PORTÉE>` la valeur est écrite **et relue** —
`infraspackplus_defaultParam()` ignore une clé lue dans un bloc dont la portée ne correspond pas.
Avec `INFRASPLUS_PDF_OPTION_listnotep = 'type'` (portée par défaut sur plusieurs instances), la
sélection de notes est globale au type « facture » : elle vit dans
`INFRASPLUS_PDF_PARAMS_facture_TYPE`, la constante `_DOC_<id>` de la facture source ne contient
aucune clé `listnotep`, et une valeur qu'on y écrirait pour l'avoir ne serait jamais relue. Le
transfert n'a donc de sens qu'en portée `doc`, d'où le test `== 'doc'` — mais ce test ne conditionne
que ce second temps. La première version de la fonction en faisait la garde **entière** : sur une instance en portée
`type` l'option paraissait sans effet, y compris pour le texte de la note publique qui, lui, ne
dépend pas d'InfraSPackPlus. Corrigé en 21.6.0 (avant publication).

### Facturation en masse des commandes (Mass invoicing improvements)

L'action de masse native "Générer les factures" de la liste des commandes (`commande/list.php`, `massaction=confirm_createbills`) ne propose aucun point d'extension : elle construit les factures dans une boucle fermée, sans appel à `executeHooks()`. Pour l'améliorer, le module intercepte l'action **avant** que le code natif ne s'exécute et la réimplémente entièrement dans `massCreateBills()` — plutôt que d'ajouter une nouvelle action de masse, il remplace le comportement de l'action existante :

```
Utilisateur : sélection de commandes + « Générer les factures » (option date, une facture par tiers, validation)
    ↓
doMassActions() (hook orderlist) :
    → Si INFRASWORKFLOW_MASS_CREATEBILLS activé et massaction == confirm_createbills :
        → $massaction et $action sont remis à '' (empêche list.php de aussi exécuter son propre bloc natif)
        → massCreateBills() prend la main, retourne 1 (code natif remplacé)
    → Sinon : $massaction/$action ne sont PAS touchés, le code natif s'exécute normalement
massCreateBills() : reproduit fidèlement la boucle native (1 facture par commande, ou 1 facture par tiers
si "une facture par tiers" est coché, lignes, remises, multidevise, génération de document si validation
demandée) et ajoute 3 comportements optionnels :
    → INFRASWORKFLOW_MASS_CREATEBILLS_COPY_NOTES  : copie note_public/note_private de la commande vers la
      facture (seulement en mode 1 commande = 1 facture, une note combinée n'aurait pas de sens sinon)
    → INFRASWORKFLOW_MASS_CREATEBILLS_SET_AUTHOR  : force fk_user_author à l'utilisateur ayant déclenché
      l'action de masse
    → INFRASWORKFLOW_MASS_CREATEBILLS_DEDUP_REF   : en mode 1 commande = 1 facture, n'ajoute plus la
      référence de la commande (+ référence client) dans la note de la facture — le code natif l'ajoute
      systématiquement alors qu'elle est déjà portée par le champ ref_client de la facture (affiché dans
      son en-tête), la même information apparaissait donc deux fois
```

⚠️ **Piège corrigé en 21.6.0 — `doMassActions` désactivait des fonctionnalités indépendantes** : la version initiale remettait `$massaction`/`$action` à `''` **sans condition**, en tout début de fonction. Comme ce sont les mêmes variables globales utilisées par la page appelante (`orderlist` et `supplierinvoicelist` partagent la même méthode), cela cassait en permanence : l'action de masse "Classer payées" des factures fournisseurs (le test `$action != 'setpaidsupplier'` qui suit ne pouvait plus jamais être vrai, puisque `$action` venait d'être vidé juste avant), et — sur la liste des commandes — la génération native de factures dès que `INFRASWORKFLOW_MASS_CREATEBILLS` était désactivé. La réinitialisation ne doit avoir lieu que dans la branche qui prend effectivement en charge `massCreateBills()`.

⚠️ **Redirection de fin d'action** : le code natif reconstruit une très longue chaîne de requête (~30 variables `$search_*` de la page) pour revenir sur la liste filtrée. Ces variables ne sont pas accessibles depuis le hook (formulaire soumis en POST vers l'URL nue, filtres transmis en champs cachés et non en query string). `massCreateBills()` redirige donc vers le `Referer` HTTP si son hôte correspond à celui de la requête courante (protection anti-open-redirect), sinon vers la liste des commandes sans filtre.

### Système de gestion centralisée des extrafields (Centralized extrafields management)

Le fichier `infrasworkflow.lib.php` contient le moteur central, structuré en fonctions spécialisées :

**Fonction CRUD principale** :
- `infrasworkflow_manage_extf($set, $tempName, $constKey, $langKey, $listElem, $listParams)` — gestion unifiée des opérations : check(0), update/create(1), create(2), disable(-1), delete(-2) avec propagation multi-éléments

**Chaînes de propagation** :
- `infrasworkflow_getPropagationChains($elementtype, $include)` — construit les chaînes de propagation bidirectionnelles :
  - **Vente** : propal → commande → facture → contrat
  - **Achat** : supplier_proposal → commande_fournisseur → facture_fourn
  - **Spécial societe** : filtre selon les options de propagation Dolibarr activées (`THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_*`)
  - Distingue documents et lignes de documents

**Construction des formulaires** :
- `infrasworkflow_buildCheckBoxes()` — génère les checkbox de propagation pour les objets liés
- `infrasworkflow_appendCheckboxesToForm()` — injecte les checkbox dans les formulaires de confirmation avec JavaScript « tout cocher/décocher » et gestion des états `indeterminate`

**Validation** :
- `infrasworkflow_check_extf_name($name)` — validation nom : lowercase/alphanumérique, longueur ≥ 3, mots réservés SQL (liste étendue Dolibarr v19+)
- `infrasworkflow_checkValues()` — validation complète des paramètres : type requis, taille varchar ≤ 255, int ≤ 10, stars 1-10, paramètres select/radio/checkbox

**Audit et nettoyage** :
- `infrasworkflow_audit_extrafields($do_clean, $clean_type)` — audit complet en 4 catégories :
  1. **Orphelins** : présents dans `llx_extrafields` mais pas dans la table `_extrafields` du module, ou inversement
  2. **Modules externes désactivés** : extrafields de modules custom non activés
  3. **Modules externes supprimés** : extrafields de modules custom dont le dossier n'existe plus
  4. **Modules internes désactivés** : affichage seulement (pas de nettoyage)
- Nettoyage conditionnel via `ExtraFields::delete()` par catégorie

**Mode corbeille** (`INFRASWORKFLOW_EXTRAFIELDS_TRASHMODE`) :
- `0` : suppression directe
- `1` : l'extrafield est d'abord désactivé, puis supprimé au second appel

**Utilitaires** :
- `infrasworkflow_gettype2label()` — mapping des ~56 types d'éléments vers leur libellé
- `infrasworkflow_getallextrafields()` / `infrasworkflow_countallextrafields()` — énumération et comptage
- `infrasworkflow_get_wecanchangeinto()` — matrice de conversion de types (varchar→phone/mail/url, double↔price, text↔html, etc.)
- `infrasworkflow_getModuleFromElementType()` — mapping elementtype → module (~30 correspondances)
- `infrasworkflow_getModuleType()` — détection type module : core ou external (custom/)

### Onglet Produits du contrat (Contract products tab)

Le fichier `tabs/infrasworkflow_contrat_products_tab.php` (789 lignes) implémente un onglet dédié à la gestion des lignes produits dans les contrats :

**Actions supportées** :

| Action | Description |
|--------|-------------|
| `addline` | Ajout de ligne (produit catalogue ou ligne libre) avec extrafields, forçage `product_type=0` via SQL |
| `updateline` | Mise à jour avec description WYSIWYG, prix, quantité, TVA, remise, extrafields filtrés |
| `confirm_cloneline` | Duplication de ligne avec extrafields, rang = `MAX(rang)+1` |
| `deleteline` | Suppression avec dialogue de confirmation |
| `up` / `down` | Réordonnancement via méthodes natives `line_up()`/`line_down()` |

**Affichage** :
- **Tri** : `usort()` par rang (NULL→999999), puis par rowid
- **Filtrage** : uniquement `product_type === 0` (produits)
- **Mode vue** : colonnes Produit, TVA, Prix HT, Qté, Remise, Total HT, Actions (edit/clone/delete/up/down), Extrafields filtrés
- **Mode édition** : formulaire inline avec sélecteur TVA, éditeur WYSIWYG pour description, extrafields
- **Extrafields filtrés** via `INFRASWORKFLOW_CONTRACT_LINE_EXTRAFIELDS_SELECTION` et `shouldDisplayContractLineExtrafield()`
- **Formulaire ajout** : sélection produit catalogue ou ligne libre, inclut le template `core/tpl/objectline_create.tpl.php` (dispatcher vers `linecreates/v{major}.tpl.php`)

**Rendu du formulaire d'ajout — deux chemins possibles** :
1. Le hook `ActionsInfraSWorkflow::formAddObjectLine()` (contexte `globalcard`/`contractcard`) s'active en priorité si `$GLOBALS['infrasworkflow_contrat_tab_active']` est positionné par le tab, si `infrasproject` n'est pas activé, et si un template versionné existe (`infrasworkflow_pickLineTpl('create')`). Il délègue alors à la méthode native `CommonObject::formAddObjectLine(1, $seller, $buyer, $defaulttpldir)`.
2. Si le hook renvoie `0`, le tab exécute lui-même `include dol_buildpath('/infrasworkflow/core/tpl/objectline_create.tpl.php')` en fallback.

⚠️ **Piège** : `CommonObject::formAddObjectLine()` résout le chemin par défaut (celui passé en 4ᵉ paramètre) par simple concaténation `DOL_DOCUMENT_ROOT.$defaulttpldir.'/objectline_create.tpl.php'` — **sans** passer par `dol_buildpath()` et donc **sans** détection automatique du préfixe `/custom/`. Le `$defaulttpldir` transmis par le hook doit donc être `/custom/infrasworkflow/core/tpl` (et non `/infrasworkflow/core/tpl`), sous peine d'`@include` silencieux sur un chemin inexistant : aucune erreur PHP, le hook renvoie tout de même `1` (il ne vérifie pas le résultat de l'include), et le fallback du tab n'est donc jamais déclenché → formulaire d'ajout de ligne totalement vide, sans trace dans les logs. (Bug corrigé en v21.3.1, voir `docs/changelog.xml`.)

### Normalisation des rangs de contrat (Contract rank normalization)

Le hook `afterFetchLines` dans `ActionsInfraSWorkflow` (contexte `contractcard`) délègue à deux fonctions de `infrasworkflow.lib.php` :

```php
// Détection des anomalies de rangs
infrasworkflow_needRankNormalization($object) :
    → Rangs NULL ou 0
    → Doublons de rang
    → Séquences non-consécutives

// Correction automatique
infrasworkflow_normalizeContractProductRanks($contractId) :
    → Transaction begin()/commit()
    → SQL UPDATE séquentiel (1, 2, 3...)
    → Tri par rang existant puis rowid
```

Activé par la constante `INFRASWORKFLOW_AUTO_NORMALIZE_CONTRACT_RANKS`.

### Montant du contrat dans l'infobulle (Contract tooltip amount)
Quand des lignes produits sont attachées à un contrat (`INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE`),
**leur prix ne fait pas partie du montant du contrat** : la fiche et le PDF les excluent déjà. L'infobulle
affichée au survol de la référence d'un contrat, elle, les additionnait — `Contrat::fetch_lines()` somme
toutes les lignes de `llx_contratdet` sans filtrer `product_type`, et `getTooltipContentArray()` réutilise
`$this->total_ht / total_tva / total_ttc`.
Le module recalcule ces trois montants sur les seules lignes de service via
`infrasworkflow_getContractServicesTotals($object)` (`infrasworkflow.lib.php`), qui somme
`$object->lines` si elles sont chargées, sinon interroge directement `llx_contratdet`. L'objet contrat
n'est jamais modifié durablement.
**Critère d'exclusion** — strictement celui des modèles PDF `pdf_InfraSPlus_CT` / `_CTS`, pour que
l'infobulle, le PDF et la fiche donnent toujours le même montant :
```php
if (isModEnabled('infrasworkflow') && getDolGlobalInt('INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE')
    && $object->lines[$i]->product_type == Product::TYPE_PRODUCT) {
    continue;   // ligne exclue du contrat
}
```
En SQL, l'équivalent exact est `COALESCE(p.fk_product_type, 0) <> 0` après jointure sur `llx_product`.
**Deux chemins selon `MAIN_ENABLE_AJAX_TOOLTIP`** :
| Mode | Chaîne d'appel | Hook utilisé |
|------|----------------|--------------|
| AJAX (défaut) | `getNomUrl()` → `classforajaxtooltip` → `/core/ajax/ajaxtooltip.php` → `CommonObject::getTooltipContent()` | `getTooltipContent`, contexte **`contratdao`** — le tableau de données est passé **par référence**, il suffit d'y réécrire `amountht`, `vatamount`, `amounttc` |
| non-AJAX | `getNomUrl()` → `getTooltipContentArray()` en direct (aucun hook exposé) | `getNomUrl`, contexte **`contractdao`** — le libellé est déjà dans l'attribut `title` du lien : il est regénéré en substituant temporairement les totaux, puis remplacé par `preg_replace_callback` |
Les deux méthodes sortent immédiatement si l'objet n'est pas un contrat ou si
`INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE` est désactivé, et `getNomUrl()` ne fait rien tant que le
mode AJAX est actif : les deux chemins ne s'exécutent jamais ensemble.
⚠️ **Piège — deux noms de contexte différents** : `CommonObject::getTooltipContent()` construit son
contexte depuis `$this->element`, ce qui donne **`contratdao`** pour un contrat, alors que
`Contrat::getNomUrl()` initialise en dur **`contractdao`**. Les deux doivent être déclarés dans
`module_parts['hooks']`, sous peine de voir un seul des deux chemins fonctionner.
⚠️ **Piège — `type` vs `product_type` sur une ligne de contrat** : sur un `ContratLigne` chargé par
`Contrat::fetch_lines()`, `$line->type` porte le type de la **ligne de contrat** (`contratdet.product_type`)
tandis que `$line->product_type` porte le type du **produit du catalogue** (`product.fk_product_type`,
`NULL` sur une ligne libre).
C'est le type **catalogue** qui fait foi, et non celui de la ligne : `contratdet.product_type` a pour
valeur par défaut `1` et `Contrat::addline()` **n'écrit même pas cette colonne**. Un produit du catalogue
ajouté à un contrat est donc très souvent stocké comme ligne « service ». Constat sur acfincendie
(entité 2, 2026-07) : 188 lignes réparties sur 22 contrats portaient un produit du catalogue avec
`contratdet.product_type = 1` ; elles ont été requalifiées et le trigger ci-dessous empêche la récidive.
Se fier au type de la ligne ferait recompter ces produits dans le montant.
⚠️ **Conséquence sur les lignes libres** : une ligne libre n'a pas de type catalogue (`NULL`) et la
comparaison du PDF est lâche, donc `NULL == 0` et **les lignes libres sont exclues du montant**, quel que
soit leur `contratdet.product_type`. Le `COALESCE(..., 0) <> 0` du repli SQL reproduit ce comportement.
⚠️ **Un contrat peut avoir plusieurs PDF** (`_SSI`, `_PI`, `_AR`…), un par famille de prestation, chacun
ne totalisant que sa part. Pour comparer l'infobulle au PDF, il faut sommer les `TOTAL HT` de tous les
fichiers du contrat, pas en lire un seul.
#### Verrou de typage des lignes produits (`LINECONTRACT_INSERT`)
Le critère ci-dessus repose sur le type **catalogue**, mais deux autres affichages se fient au type de la
**ligne** (`contratdet.product_type`) : la fiche contrat substituée (`if ($object->lines[...]->product_type == 0) continue;`)
et l'onglet « Produits des Contrats » (filtre `product_type === 0`). Une ligne mal typée disparaît donc de
l'onglet Produits et s'affiche parmi les services de la fiche.
Origine du défaut : `llx_contratdet.product_type` a pour valeur par défaut `1` et `Contrat::addline()`
n'écrit jamais cette colonne. Les chemins propres au module corrigeaient déjà après insertion (onglet
Produits, création de contrat depuis une source), mais pas les autres (formulaire natif de la fiche
contrat, API, imports).
Le trigger `LINECONTRACT_INSERT` ferme la brèche pour tous les chemins :
```php
infrasworkflow_forceContractProductLineType($lineid) :
    → SELECT sur contratdet JOIN product
    → si product.fk_product_type = 0 et contratdet.product_type <> 0
    → UPDATE contratdet SET product_type = 0   (SQL direct : aucun trigger relancé)
```
⚠️ **Deux émetteurs pour ce même trigger** : `Contrat::addline()` le déclenche en passant le **contrat**
(l'id de la ligne créée est alors dans `$object->context['line_id']`), tandis que `ContratLigne::insert()`
le déclenche en passant la **ligne** elle-même (`$object->id`). Le trigger gère les deux, et `contratdet`
a dû être ajouté à la liste des `$object->element` acceptés en tête de `runTrigger()`.
### Amélioration de l'inventaire natif (Native inventory improvements)

Trois options de la section « Gestion des inventaires » (module `stock` actif), toutes désactivées par défaut.

**1 & 2 — Initialisation de l'inventaire (trigger `INVENTORY_VALIDATED`)**

`Inventory::validate()` (`product/inventory/class/inventory.class.php`) n'expose aucun hook : il purge
`llx_inventorydet`, génère les lignes en balayant **`llx_product_stock`** (un produit sans ligne de stock
dans l'entrepôt n'apparaît donc jamais), puis appelle `setStatut(..., 'INVENTORY_VALIDATED')` — le
trigger s'exécute **dans la transaction de `validate()`**, après la génération native, avec l'objet
`Inventory` chargé (`fk_warehouse`, `fk_product`, `categories_product`). Retourner `< 0` fait échouer
`setStatut()` → rollback complet, l'inventaire reste en brouillon avec le message d'erreur.

```
Bouton « Valider (Démarrer) »  →  core/actions_addupdatedelete.inc.php (confirm_validate)
    ↓
Inventory::validate() : DELETE lignes, INSERT depuis llx_product_stock, setStatut()
    ↓ trigger INVENTORY_VALIDATED (même transaction)
infrasworkflow_inventoryPurgeHiddenLines($inventory)
    → DELETE llx_inventorydet WHERE fk_product IN (produits masqués)
      critère : infrasworkflow_inventoryHiddenProductsCondition() =
      (tosell = 0 AND tobuy = 0) OR catégorie Obsolète + sous-catégories
      (infrasworkflow_getCategoryWithChildrenIds(), récursif sur Categorie::get_filles())
    ↓
infrasworkflow_inventoryAddEmptyStockLines($inventory, $user, GETPOSTINT('include_sub_warehouse'))
    → SELECT llx_product avec les mêmes filtres que validate() (entity, type produit sauf
      STOCK_SUPPORTS_SERVICES, fk_product, categories_product, kits exclus si PRODUIT_SOUSPRODUITS)
      + produits masqués exclus + produits à lots exclus (isModEnabled('productbatch'))
    → entrepôts cibles : fk_warehouse (+ Inventory::getChildWarehouse() si sous-entrepôts demandés),
      sinon fk_default_warehouse du produit (produit ignoré s'il n'en a pas)
    → InventoryLine::create() pour chaque (entrepôt, produit) sans ligne existante, qty_stock = 0
```

⚠️ **Piège — le choix « inclure les sous-entrepôts » n'est pas sur l'objet** : c'est un paramètre de
`validate()` issu de la case à cocher du dialogue de confirmation. Le trigger le relit avec
`GETPOSTINT('include_sub_warehouse')` (même requête HTTP). Un appel par API ou script sans ce paramètre
n'ajoute des lignes que pour l'entrepôt principal.

Note — seul `Inventory::validate()` lève `INVENTORY_VALIDATED` (`setDraft()`, `setRecorded()` et
`setCanceled()` lèvent respectivement `INVENTORY_DRAFT`, `INVENTORY_RECORDED` et `INVENTORY_CANCELED`) :
le trigger n'a pas à vérifier le statut précédent.

**3 — Colonne « Zone » (page de substitution `product/inventory/inventory.php`)**

Le tableau des lignes de `inventory.php` n'a aucun hook d'affichage (seuls `doActions`, `formConfirm`,
`addMoreActionsButtons`). La colonne est donc rendue par une copie de la page core dans
`substitutionpages/dlb220x/product/inventory/inventory.php` (toutes les modifications y sont marquées
`// InfraS add` / `// InfraS change` pour faciliter la resynchronisation avec un core plus récent).
Constante d'activation dérivée par `infrasworkflow_get_const_name_from_substitution_path()` :
`INFRASWORKFLOW_PS_ACTIVE_PRODUCT_INVENTORY_INVENTORY`, tenue synchrone avec l'option
`INFRASWORKFLOW_DISPLAY_ZONE_COLUMN` par la page d'administration (action `set_` + resynchronisation à
chaque affichage). Sans fichier pour la branche Dolibarr courante, la page native est servie.

`infrasworkflow_inventoryZoneSqlParts()` renvoie les fragments SQL (`select`, `join`, `sortfield`) et la
source retenue : catégorie (`LEFT JOIN` sur une sous-requête
`MIN(c.label) ... WHERE c.fk_parent = <parent> GROUP BY cp.fk_product` — un produit rattaché à plusieurs
zones n'affiche que la première par ordre alphabétique, sans dupliquer la ligne) ou, à défaut, extrafield
produit (`LEFT JOIN llx_product_extrafields pe`, valeur affichée par `ExtraFields::showOutputField()`
donc libellés des listes).
Le code d'extrafield est validé contre les définitions chargées (`attributes['product']['label']`) avant
d'être injecté dans le SQL.

**Les deux sources sont exclusives** : on renseigne l'attribut supplémentaire **ou** la catégorie parente,
jamais les deux (les libellés de la page d'administration l'indiquent). Si les deux arrivent renseignées
à l'enregistrement, seule la catégorie est conservée : l'attribut supplémentaire est vidé et
l'avertissement `InfraSWorkflowInventoryZoneExclusive` est affiché — d'où la priorité de la catégorie
dans `infrasworkflow_inventoryZoneSqlParts()`. Arbitrage volontaire côté enregistrement uniquement, sans
garde JavaScript sur le formulaire.

⚠️ **Piège — le choix vide d'un `selectarray()` vaut `-1`, pas `''`** : la première version de
l'enregistrement testait `GETPOST('INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', 'aZ09') !== ''` pour donner
la priorité à l'attribut supplémentaire, condition toujours vraie puisque la liste renvoie `-1` quand
rien n'est sélectionné : la catégorie parente était donc remise à `-1` à chaque enregistrement et ne
pouvait jamais être configurée. Corrigé en 21.8.1 (normalisation de `-1` en chaîne vide).

⚠️ **Piège — quatre blocs du tableau à tenir alignés** : le tableau `#tablelines` de `inventory.php` est
construit par quatre blocs distincts, et la colonne « Zone » doit être ajoutée dans chacun, sous la même
condition `!empty($infras_zone['source'])` : l'en-tête (`liste_titre`), la **ligne d'ajout d'une ligne
d'inventaire** (champ de saisie, voir ci-dessous), les lignes de saisie (`oddeven`) et la ligne de total
(`colspan` incrémenté, affichée seulement avec `INVENTORY_MANAGE_REAL_PMP`).
L'oubli de la ligne d'ajout décale toutes ses saisies d'une colonne vers la gauche — le champ
« Lot/Série » se retrouve sous l'en-tête « Zone » — sans aucune anomalie ailleurs dans le tableau.

**Saisie de la zone à l'ajout d'une ligne d'inventaire** : la ligne d'ajout porte un champ « Zone »
(`infrasworkflow_inventoryZoneInput()`), appliqué **au produit** par
`infrasworkflow_inventorySetProductZone()` juste après la création de la ligne — `llx_inventorydet` n'a
pas de colonne de zone, la zone est une propriété du produit. Une saisie vide ne modifie rien, et un
message confirme l'écriture (`InfraSWorkflowInventoryZoneProductUpdated`). Selon la source configurée :

| Source | Champ affiché | Écriture sur le produit |
|--------|---------------|-------------------------|
| extrafield | `ExtraFields::showInputField()` du champ produit, préfixe de nom `infraszone_` (donc POST `infraszone_options_<code>`) | `Product::updateExtraField($code, null, $user)` |
| catégorie | liste des sous-catégories directes de la catégorie parente (`infrasworkflow_inventoryZoneCategories()`), POST `infraszone_category` | `Categorie::add_type()` sur la zone choisie **et** `del_type()` sur les autres sous-catégories de la même parente |

⚠️ **Une seule zone par produit** : la colonne n'affiche qu'une zone (`MIN(c.label)`), la saisie par
catégorie **remplace** donc la zone existante au lieu de s'y ajouter. Une catégorie qui n'est pas une
sous-catégorie de la parente configurée est ignorée (valeur POST non fiable).

⚠️ **Écriture de l'extrafield sans trigger** : `updateExtraField()` est appelée avec `$trigger = null`
(le trigger n'est appelé que si le paramètre est non vide, cf. `CommonObject::updateExtraField()`).
Passer `PRODUCT_MODIFY` déclencherait le contrôle de validation produit du module
(`INFRASWORKFLOW_PRODUCT_VALIDATION_CONTROL`), qui peut **mettre le produit hors vente** — effet de bord
inacceptable pour une simple saisie d'inventaire.

⚠️ **Piège — la jointure doit être présente dans les deux requêtes de lignes** : l'action
`updateinventorylines` (enregistrement des quantités) relit les lignes avec **le même `$sortfield`** que
l'affichage ; trier par zone sans la jointure dans cette requête casserait l'enregistrement.

⚠️ **Piège — liens vers la page core et token CSRF** : `infrasworkflow_getSubstitutionRedirectUrl()`
retire le `token` de la query string lors de la redirection. Un lien `deleteline` pointant vers la page
core serait redirigé sans token et refusé par `main.inc.php` dès `MAIN_SECURITY_CSRF_WITH_TOKEN >= 2`.
La page de substitution pointe donc ses liens d'action et `$backtopage` vers `$_SERVER['PHP_SELF']`.

### Classe `infrasworkflowPropal` (Recursive invoice search)

Fichier `class/infrasworkflow_propal.class.php` — étend `Propal` pour la recherche de factures liées.

**`Infrasworkflow_InvoiceArrayList($id, $recursive = true)`** :
- **Mode récursif** (`$recursive=true`) : parcourt les objets liés (commandes, etc.) pour trouver les factures en profondeur
- **Mode non-récursif** (`$recursive=false`) : uniquement les factures directement liées au devis
- Dédoublonnage via `array_unique()`
- SQL final : `SELECT rowid, ref, total_ht, datef, fk_statut, paye, type FROM facture WHERE rowid IN (...)`

**Point critique** : le mode non-récursif est utilisé par `infrasworkflow_linkDepositsToFinalInvoice()` pour éviter de lier des acomptes d'objets intermédiaires.

### Substitution de pages (Page substitution mechanism)

Le module utilise la **substitution de pages** pour la fiche contrat (`contrat/card.php`) et la saisie d'inventaire (`product/inventory/inventory.php`, branche `dlb220x` uniquement pour l'instant) :

**Branches maintenues** : `dlb210x`, `dlb220x`, `dlb220x-Easya`, `dlb230x`, `dlb240x` (5 branches dont 1 variante Easya)

**Constantes d'activation** : `INFRASWORKFLOW_PS_ACTIVE_CONTRAT_CARD`, `INFRASWORKFLOW_PS_ACTIVE_PRODUCT_INVENTORY_INVENTORY`

**Flux de redirection** (depuis la version 18.14.1) :

**Depuis la version 18.14.1**, le flux de redirection utilise `infrasworkflow_getSubstitutionRedirectUrl()` pour centraliser la logique :

```
L'utilisateur accède à /contrat/card.php
    ↓
Le hook updateSession() ou afterLogin() s'exécute
    ↓
infrasworkflow_getSubstitutionRedirectUrl() :
    → infrasworkflow_is_substitution_page() vérifie via strpos() si on est
      déjà sur une page substituée (prévention de boucle)
    → infrasworkflow_get_substitution_url() génère l'URL substituée :
      • Vérifie la constante INFRASWORKFLOW_PS_ACTIVE_CONTRAT_CARD
      • Construit le chemin : /infrasworkflow/substitutionpages/dlb{major}0x{-Easya}/contrat/card.php
      • Vérifie l'existence physique du fichier via dol_buildpath()
    → Filtre les paramètres GET : exclusion du token CSRF (page-specific)
    → Retourne l'URL complète avec query string filtrée
    ↓
Redirection header('Location: ...') → exit
```

**Amélioration de sécurité** : la fonction exclut automatiquement les paramètres POST (pouvant contenir des credentials) et le token CSRF qui est spécifique à la page d'origine.

### Flux des hooks (Hook workflow)

La classe `ActionsInfraSWorkflow` intervient sur les contextes `main`, `login`, `formfile`, `globalcard`, `thirdpartycomm`, `paymentdao`, `contractcard`, `supplierinvoicelist`, `contratdao`, `contractdao` selon ce flux :

```
L'utilisateur accède à une page Dolibarr
    ↓
updateSession() / afterLogin() :
    → Substitution de pages (contrat/card.php)
    → Vérification version max Dolibarr supportée
      via explode('.', DOL_VERSION)[0] vs explode('.', maxVersion)[0]
    ↓
addMoreActionsButtons() : injecte les boutons conditionnels
    → « Générer 2ème acompte » (devis signé + conditions)
    → Contrôle compte client (masque boutons commande/facture pour Prospect)
    → « Créer commande fournisseur » (facture fournisseur)
    → « Envoyer email » (contrat provisoire)
    (retourne 1 pour remplacer les boutons standards quand nécessaire)
    ↓
formConfirm() : enrichit les dialogues de confirmation
    → Dialogue « Classer signée » du devis : ajout champs acompte
    → Dialogue « Générer 2ème acompte » : date, conditions, validation auto
    → JavaScript pour affichage/masquage dynamique des champs
    ↓
doActions() : traite les soumissions de formulaires
    → confirm_closeas : génération 1er acompte manuel
    → confirm_generate_second_deposit : génération 2ème acompte
    → Création commande fournisseur depuis facture fournisseur
    → Contrôle compte client pour commande/facture
    → Création contrat depuis source avec copie des lignes
    ↓
createPayment() : hook paymentdao
    → Régénération PDF facture au classement « payé »
```

### Trigger et événements (Trigger events)

Le trigger `InterfaceInfrasworkflow` filtre sur les éléments `propal`, `facture`, `societe`, `product`, `contrat`, `contratdet`, `order_supplier`, `inventory` :

| Événement | Condition | Action |
|-----------|-----------|--------|
| `PROPAL_CLOSE_SIGNED` | Toujours | 1. Contrôle compte client → set Prospect/ProspectClient si KO |
| | | 2. Génération 1er acompte auto (`infrasworkflow_firstAccountAuto`) |
| `BILL_CREATE` (standard) | Toujours | 1. Liaison acomptes → facture finale (`infrasworkflow_linkDepositsToFinalInvoice`) |
| | | 2. Copie notes publiques du devis (si infraspackplus activé) |
| `BILL_CREATE` (acompte) | infraspackplus actif | Copie notes publiques du devis (`infrasworkflow_cloneNotePfromPropal`) |
| `BILL_VALIDATE` (standard) | `INFRASWORKFLOW_INVOICE_VALIDATION` | Vérifie si totalement payée → `setPaid()`, classifie devis « facturés » |
| `BILL_PAYED` | `INFRASWORKFLOW_INVOICE_REGENARATION_ON_CLASSIFY_PAID` | Régénération du PDF facture |
| `COMPANY_CREATE/MODIFY` | `INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT` | Contrôle champs obligatoires → set type Prospect/ProspectClient |
| `PRODUCT_CREATE/MODIFY` | `INFRASWORKFLOW_PRODUCT_VALIDATION_CONTROL` | Contrôle champs obligatoires → mise hors vente si KO |
| `CONTRACT_VALIDATE` | `INFRASWORKFLOW_CONTRACT_SERVICE_AUTO` | Activation de tous les services (`$object->activateAll()`) |
| `OBJECT_LINK_INSERT` | Contrat + facture standard | Copie extrafields facture → contrat (1ère liaison uniquement) |
| `LINECONTRACT_INSERT` | `INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE` | Retype la ligne en produit si elle pointe un produit du catalogue (`infrasworkflow_forceContractProductLineType()`) |
| `INVENTORY_VALIDATED` | `INFRASWORKFLOW_HIDE_ITEMS_TAGGED_OBSOLETE` ou `INFRASWORKFLOW_DISPLAY_SORTED_EMPTY_STOCK` | Purge des lignes des produits masqués puis ajout des produits sans stock (`infrasworkflow_inventoryPurgeHiddenLines()`, `infrasworkflow_inventoryAddEmptyStockLines()`), dans la transaction de `Inventory::validate()` |
**Point de vigilance (depuis v21.4.2)** : `runTrigger()` est appelé par Dolibarr pour **tous** les événements métier, pas seulement les éléments listés ci-dessus — le test `in_array($object->element, ['propal', 'facture', 'societe', 'product', 'contrat', 'order_supplier'])` lisait la propriété sans vérifier son existence, provoquant un avertissement PHP « Undefined property » sur des objets qui n'exposent pas `element` (ex. `TPropaleHist`, historique de devis). Un test `empty($object->element) ||` protège désormais ce filtre.

### Intégration inter-modules (Module integrations)

| Module | Type | Intégration |
|--------|------|------------|
| **infraspackplus** | Optionnelle | Modèle PDF `InfraSPlus_FR`, transfert notes publiques devis→facture, régénération PDF semi-auto (`infraspackplus_semiauto_update()`) |
| **Dolibarr natif** | Core | Hooks standards, triggers, constantes de propagation (`THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_INVOICE\|ORDER\|SUPPLIER_ORDER`, `PRODUCT_LOAD_EXTRAFIELD_INTO_OBJECTLINES`) |

### Templates versionnés (Versioned templates)

Les templates sont routés par version Dolibarr via des fichiers wrapper qui détectent `DOL_VERSION` et incluent le fichier spécifique :

| Template wrapper | Sous-dossier | Fichiers versionnés | Rôle |
|-----------------|--------------|---------------------|------|
| `actions_extrafields.inc.php` | `adminextrafields/` | `actions_v21` à `actions_v24` | Actions CRUD extrafields |
| `admin_extrafields_view.tpl.php` | `adminextrafields/` | `view_v21` à `view_v24` | Vue liste des extrafields |
| `admin_extrafields_add.tpl.php` | `adminextrafields/` | `add_v21` à `add_v24` | Formulaire ajout extrafield |
| `admin_extrafields_edit.tpl.php` | `adminextrafields/` | `edit_v21` à `edit_v24` | Formulaire édition extrafield |
| `objectline_create.tpl.php` | `linecreates/` | `v21.tpl.php` à `v24.tpl.php` | Formulaire d'ajout de ligne contrat |

Tous les dispatchers utilisent `(int) DOL_VERSION` (version majeure entière) ; le fallback est la version la plus ancienne supportée (v21).

### Sauvegarde et restauration (Backup and restore)

**`infrasworkflow_bkup_module()`** :
- Génère un dump SQL des constantes `INFRASWORKFLOW_%` depuis `llx_const`
- Format : `INSERT INTO llx_const ... ON DUPLICATE KEY UPDATE`
- Remplacement d'entité : `$conf->entity` → `__ENTITY__` pour portabilité
- Fichier : `DOL_DATA_ROOT/infrasworkflow/sql/update.{entity}` + archive datée

**`infrasworkflow_restore_module()`** :
- Exécute le dump SQL via `run_sql()`
- Remplace `__ENTITY__` par la valeur courante de `$conf->entity`

Support PostgreSQL via `ON CONFLICT` dans `infrasworkflow_bkup_table()`.

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="21.8.0" MonthVersion="2026-09">
        <change type='fix'>Fixed bug description.</change>
        <change type='chg'>Changed feature description.</change>
        <change type='add'>Added feature description.</change>
    </Version>
    <InfraS Downloaded="20260903"/>
    <Dolibarr minVersion="21.0.0" maxVersion="24.x.x"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

- Types de changement : `add` (ajout, vert), `chg` (modification, bleu), `fix` (correction, rouge/caution)
- Ordre des entrées par version : **fix → chg → add**
- L'attribut `Downloaded` est mis à jour automatiquement lors du téléchargement de la version distante
- Versions ordonnées chronologiquement (la dernière est la plus récente)
- Parsé par `infrasworkflow_getChangelogFile()` / `infrasworkflow_getLocalVersionMinDoli()`

La fonction `infrasworkflow_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "21.8.0",          // Version courante
    1 => "21.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (-1 = KO, 0 = OK)
    3 => <SimpleXMLElement>, // Liste des versions (ou message d'erreur)
    4 => "24.x.x",           // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

### Cycle de vie du module (Module lifecycle)

**`init()`** effectue dans l'ordre :
1. Chargement des tables SQL (`_load_tables('/infrasworkflow/sql/')`) — exécute `data.sql`
2. Restauration des paramètres sauvegardés (`infrasworkflow_restore_module`)
3. Construction et persistance de `INFRASWORKFLOW_LISTTYPEKEY` : mapping de ~60 elementtypes vers leur module via `http_build_query()`
4. Enregistrement de `INFRASWORKFLOW_DOL_VERSION` et `INFRASWORKFLOW_MAIN_VERSION`
5. Appel `_init($sql, $options)` standard

**`remove()`** effectue :
1. Sauvegarde des paramètres (`infrasworkflow_bkup_module`)
2. Suppression des constantes `INFRASWORKFLOW_%` de `llx_const` pour l'entité courante
3. Appel `_remove($sql)` standard

### Régénération PDF (PDF regeneration)

`infrasworkflow_pdfinvoicegeneration($invoice, $action)` :
- Si **infraspackplus** activé et `INFRASPLUS_PDF_SEMIAUTOUPDATE` configuré : utilise `infraspackplus_semiauto_update()` pour une régénération avancée (avec options InfraSPlus)
- Sinon : utilise `generateDocument()` natif Dolibarr avec le modèle configuré dans `FACTURE_ADDON_PDF`

### Ajout du support d'une nouvelle version Dolibarr (Adding support for new Dolibarr versions)

Pour supporter une nouvelle version majeure de Dolibarr (ex. 24.x) :

1. Créer les répertoires de substitution : `substitutionpages/dlb240x/contrat/` (et `product/inventory/` pour la colonne Zone)
2. Copier le contenu du dossier de la version précédente : `cp -r dlb230x/* dlb240x/`
3. Si la distribution Easya est ciblée : créer aussi `dlb240x-Easya/contrat/`
4. Créer les templates versionnés :
   - `core/tpl/adminextrafields/actions_v24.inc.php`
   - `core/tpl/adminextrafields/view_v24.tpl.php`, `add_v24.tpl.php`, `edit_v24.tpl.php`
   - `core/tpl/linecreates/v24.tpl.php`
5. Mettre à jour les 5 dispatchers (`admin_extrafields_view.tpl.php`, `admin_extrafields_add.tpl.php`, `admin_extrafields_edit.tpl.php`, `actions_extrafields.inc.php`, `objectline_create.tpl.php`) : ajouter `if ($major >= 24)` en première branche et décaler les branches inférieures en `elseif`
6. Vérifier et adapter les évolutions des pages core Dolibarr (`contrat/card.php`)
7. Mettre à jour `docs/changelog.xml` :
   ```xml
   <Version Number="X.Y.Z" MonthVersion="YYYY-MM">
       <change type='add'>Compatibilité avec Dolibarr v24</change>
   </Version>
   <Dolibarr minVersion="21.0.0" maxVersion="24.x.x"/>
   ```
8. Tester la substitution de page contrat et le fonctionnement de l'onglet Produits

### Cas d'usage courants (Common use cases)

#### Cas 1 : Signature de devis avec acompte automatique

1. Créer un devis avec des lignes produits/services
2. Renseigner le montant d'acompte via l'extrafield configuré (ex. `acpt1`)
3. Classer le devis comme « signé »
4. Le trigger génère automatiquement une facture d'acompte à montant fixe
5. Si `INFRASWORKFLOW_VALIDATE_FIRST_AUTO_DEPOSIT` est activé, la facture est validée automatiquement

#### Cas 2 : Contrôle d'ouverture de compte client

1. Un tiers est créé de type Prospect
2. L'utilisateur tente de créer une commande → les boutons sont masqués
3. L'utilisateur complète tous les champs obligatoires configurés
4. Une modification du tiers déclenche `COMPANY_MODIFY` → le tiers est promu Client
5. Les boutons de création commande/facture sont désormais accessibles

#### Cas 3 : Gestion des extrafields avec propagation

1. Accéder à l'onglet ExtraFields de l'administration du module
2. Créer un extrafield sur `propaldet` (lignes de devis)
3. Cocher les cases de propagation : `commandedet`, `facturedet`
4. L'extrafield est créé simultanément sur les lignes de commande et de facture
5. Utiliser le bouton Scan pour auditer les anomalies éventuelles

#### Cas 4 : Création de contrat avec produits depuis un devis

1. Créer un devis avec des lignes produits
2. Créer un contrat depuis ce devis (bouton « Créer contrat »)
3. Le hook `doActions` intercepte la création et copie les lignes produits
4. L'onglet « Produits du contrat » affiche les lignes copiées
5. Les lignes sont éditables, duplicables et réordonnables
