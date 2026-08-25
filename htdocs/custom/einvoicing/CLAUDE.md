# CLAUDE.md — Contexte module einvoicing

## Aperçu (Overview)

`einvoicing` est un module externe Dolibarr de **facturation électronique française** (réforme e-invoicing) :

- connexion API sécurisée aux **Plateformes Agréées / PDP** (SuperPDP, Esalink Hubtimize),
- génération de factures électroniques **Factur-X** (PDF/A-3 + XML embarqué) et **CII** (XML seul), profils MINIMUM à EXTENDED-CTC-FR,
- import des factures fournisseurs reçues (création automatique tiers/produits, mappage manuel),
- gestion du **cycle de vie** des e-factures (statuts normalisés 200–213, messages CDAR),
- annuaire de routage par tiers (SIREN/SIRET + suffixe), vérification de joignabilité (annuaire AFNOR XP Z12-013),
- verrouillage des factures transmises (interdiction de modification/suppression/dévalidation).

Informations module (issues du code et du changelog local) :

- Éditeur : Dolibarr Association (mainteneur principal : Mohamed Daoud / DoliCloud)
- Numéro module : `95020`
- Licence : GPL v3+ (documentation GFDL)
- Compatibilité Dolibarr : `17.0` minimum, pas de maximum déclaré (branches de code jusqu'à 24.0)
- Compatibilité PHP : `7.2` minimum
- Version locale : `1.0.3` (fichier `VERSION`) — le `ChangeLog.md` documente déjà une section `1.0.4` en cours
- Dépendances obligatoires : `modFacture`, `modFournisseur`
- Dépôt amont : `https://github.com/Dolibarr/dolibarr-community-modules/tree/main/einvoicing`
- Emplacement : `htdocs/custom/einvoicing/`
- Ancien nom : `pdpconnectfr` (migration via `sql/update_allversion.sql` / `sql/migration_unstable_dev.sql`)

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## ⚠️ Module tiers — tags InfraS obligatoires

Ce module n'est **pas** un module InfraS (le répertoire ne commence pas par `infras`). Toute modification ou ajout de code dans un fichier existant du module doit être balisé :

- ligne modifiée → `// InfraS change` en fin de ligne
- ligne ajoutée → `// InfraS add` en fin de ligne
- bloc modifié → `// InfraS change begin` / `// InfraS change end`
- bloc ajouté → `// InfraS add begin` / `// InfraS add end`

Le module étant maintenu par la communauté Dolibarr (mises à jour depuis le dépôt GitHub), ces tags sont indispensables pour rebaser les modifications locales lors d'une montée de version du module.

## Structure (Summary)

```text
htdocs/custom/einvoicing/
├── CLAUDE.md
├── COPYING / COPYRIGHT / README.md / ChangeLog.md / VERSION / index.yaml
├── composer.disabled.json / composer.disabled.lock   # Composer volontairement désactivé (vendoring)
├── admin/
│   ├── about.php
│   ├── setup.php               # Onglet « PASettings » : choix plateforme + credentials + OAuth
│   ├── setup_options.php       # Onglet « Options » : constantes EINVOICING_*
│   └── setup_devtools.php      # Onglet « DevTools » (si EINVOICING_ALLOW_DEVTOOLS)
├── ajax/
│   ├── checkdirectory.php              # Joignabilité destinataire (annuaire PA)
│   ├── checkinvoicestatus.php          # Polling statut facture client
│   ├── checksupplierinvoicestatus.php  # Polling validation statut facture fournisseur
│   └── document.php                    # Non branché (aucun appelant)
├── class/
│   ├── einvoicing.class.php            # Classe de service centrale (statuts, contrôles, persistance)
│   ├── document.class.php              # Document (CommonObject) : flux échangés + cron cronSyncFlows()
│   ├── call.class.php                  # Call (CommonObject) : journal des appels API
│   ├── actions_einvoicing.class.php    # Hooks
│   ├── helpers/
│   │   ├── PriceHelper.class.php           # calcul_price_total() avec précision d'arrondi forcée
│   │   └── SupplierInvoiceHelper.class.php # Cohérence facture fournisseur ↔ e-facture
│   ├── protocols/
│   │   ├── AbstractProtocol.class.php      # Classe abstraite (7 méthodes abstraites)
│   │   ├── CommonProtocol.class.php        # TRAIT (mapping + logique d'import)
│   │   ├── CIIProtocol.class.php           # XML CII (génération buildXML + import)
│   │   ├── FacturXProtocol.class.php       # PDF Factur-X (extends CIIProtocol)
│   │   ├── ProtocolManager.class.php       # Fabrique (liste en dur : CII, FACTURX ; UBL désactivé)
│   │   └── ExampleHelpers.php              # Exemples zugferd (validation KOSiT non branchée)
│   ├── providers/
│   │   ├── AbstractPDPProvider.class.php   # Classe abstraite (11 méthodes abstraites, tokens, logCall, annuaire)
│   │   ├── PDPProviderManager.class.php    # Fabrique + hook addPDPProviders
│   │   ├── SuperPDPProvider.class.php      # SuperPDP (OAuth 2.1 : 3 grants)
│   │   ├── EsalinkPDPProvider.class.php    # Esalink Hubtimize (client_credentials + api-key)
│   │   └── TestPDPProvider.class.php       # Implémentation de référence (n'appelle rien)
│   └── utils/
│       ├── CtcFrPdfMerger.class.php        # Embarquement XML dans PDF pour URN CTC-FR inconnu de zugferd
│       ├── XmlPatcher.class.php            # Patch DOM du XML (références d'acompte) — sans Composer
│       ├── CdarHandler.class.php           # Messages CDAR (cycle de vie XP Z12-012 annexe B)
│       └── En16931Validator.class.php      # Validation locale EN 16931 (PHP pur, ni XSD ni Schematron)
├── compat/
│   ├── commonhookactions.class.php     # Polyfill CommonHookActions (Dolibarr < 19)
│   ├── files.lib.php                   # Polyfill dolChmod() (Dolibarr < 18)
│   └── profid.lib.php                  # Polyfill isValidSiren()/isValidSiret() (Dolibarr < 20)
├── core/
│   ├── modules/modEInvoicing.class.php
│   └── triggers/interface_98_modEInvoicing_EInvoicingTriggers.class.php
├── doc/
│   ├── ADD-A-PDP-PROVIDER.md           # Ajouter un provider PDP externe (hook)
│   └── SUPERPDP-AUTHORIZATION-CODE.md  # Runbook OAuth 2.1 Authorization Code
├── lib/
│   ├── einvoicing.lib.php              # Fonctions transverses + polyfills
│   ├── einvoicing_document.lib.php     # Onglets fiche Document
│   ├── einvoicing_call.lib.php         # Onglets fiche Call
│   ├── einvoicing_vendorref.lib.php    # Gestion des références fournisseurs mappées
│   └── buildinvoicelines.inc.php       # Template : mapping Facture Dolibarr → $invoiceData/$linesData
├── langs/                              # da_DK, en_US, fr_CA, fr_FR, hr_HR, ro_RO
├── public/proxy_oauthcallback.php      # Proxy OAuth opérateur (NOLOGIN)
├── scripts/
│   ├── testconnect.php                 # Test CLI du grant client_credentials
│   └── regenerate_einvoicing_fixtures.php
├── sql/
│   ├── llx_einvoicing_document.sql / .key.sql
│   ├── llx_einvoicing_call.sql / .key.sql
│   ├── llx_einvoicing_extlinks.sql     # (pas de .key.sql → aucun index)
│   ├── llx_einvoicing_routing.sql      # (pas de .key.sql → aucun index)
│   ├── llx_einvoicing_lifecycle_msg.sql # (pas de .key.sql → aucun index)
│   ├── dolibarr_allversions.sql / update_v1.0.0.sql / update_v1.0.2.sql / update_v1.0.3.sql
│   ├── update_allversion.sql           # Migration pdpconnectfr → einvoicing
│   └── migration_unstable_dev.sql      # À lancer MANUELLEMENT (volontairement hors _load_tables)
├── vendor/                             # horstoeko/zugferd v1.0.123 + dépendances (MIT/LGPL)
├── einvoicingindex.php                 # Accueil (contenu métier commenté)
├── document_list.php                   # Page centrale : liste des flux + écran de synchronisation
├── document_card.php / document_agenda.php / document_contact.php / document_document.php / document_note.php
├── call_list.php / call_card.php      # Journal des appels API
├── product_mapping.php                 # Mappage manuel produits d'un flux → produits Dolibarr
└── vendorref_list.php                  # Références fournisseurs mappées
```

## Descripteur module (Module descriptor : `modEInvoicing`)

Dans `core/modules/modEInvoicing.class.php` :

- **Module parts** :
	- `triggers => 1`, hooks : **`all`** + `invoicecard` (le contexte `all` fait tourner les hooks sur toutes les pages)
	- pas de CSS, JS, models, substitutions, menus, thème
- **Dépendances** : `modFacture`, `modFournisseur`
- **Dictionnaires / Boxes / Tabs** : aucun
- **Cron** : 1 tâche `EInvoicingDocumentSync` → `Document::cronSyncFlows()`, toutes les heures, **désactivée à l'installation** (`status => 0`)
- **Permissions** : 3 permissions — `read` (9502001), `write` (9502002), `delete` (9502003)
- **Constantes posées à l'activation** : `EINVOICING_EINVOICE_IN_REAL_TIME=1`, `EINVOICING_FLOWS_SYNC_CALL_LIMIT=1`, `EINVOICING_SYNC_MARGIN_TIME_HOURS=12`, `EINVOICING_FLOWS_SYNC_CALL_SIZE=100`
- **Version** : lue depuis le fichier `VERSION` ; `url_last_version` pointe le raw GitHub du dépôt community-modules
- **Masquage** : `$this->hidden = getDolGlobalInt('MODULE_EINVOICING_DISABLED')`

### Initialisation (Lifecycle : `init()`)

1. `_load_tables('/einvoicing/sql/')` (charge `llx_*.sql`, `.key.sql`, `update_*.sql`, `dolibarr_allversions.sql`)
2. Création de **9 extrafields Chorus** `d4d_*` sur `facture` (5) et `commande` (4), tous conditionnés par `getDolGlobalInt("EINVOICING_USE_CHORUS")` — TODO amont : migrer vers `llx_einvoicing_extlinks`
3. SQL de rattrapage : normalisation de la condition `enabled` des extrafields, purge de la formule calculée obsolète de `d4d_chorus_id` (conflit module openDSI)
4. Pose `EINVOICING_LIVE = 1` uniquement au premier passage (si `EINVOICING_PDP` vide)

### Désactivation (Lifecycle : `remove()`)

Standard (`_remove()` sans SQL spécifique) : les constantes et données sont conservées.

### Menus (tous sous `mainmenu=billing`, menu Facturation)

| Titre | URL | Permission |
|-------|-----|------------|
| `EInvoiceManagement` | `/einvoicing/einvoicingindex.php` | `einvoicing/read` |
| `EInvoiceSynchronization` | `/einvoicing/document_list.php` | `einvoicing/write` |
| `MapEInvoiceProducts` | `/einvoicing/product_mapping.php` | `einvoicing/write` |
| `MappedVendorRefs` | `/einvoicing/vendorref_list.php` | `einvoicing/read` |
| `pdpFeedback` | `/einvoicing/call_list.php` | `einvoicing/read` |

## Fonctionnement principal (Core behavior)

Architecture en trois couches indépendantes :

1. **Protocoles** (`class/protocols/`) — format des documents : génération XML CII / PDF Factur-X sortants, parsing des flux entrants. Sélection par la constante `EINVOICING_PROTOCOL` via `ProtocolManager`.
2. **Providers PDP** (`class/providers/`) — transport : authentification OAuth, envoi (`sendInvoice`), synchronisation (`syncFlows`/`syncFlow`), statuts (`sendStatusMessage`), annuaire. Sélection par la constante `EINVOICING_PDP` via `PDPProviderManager`.
3. **Classe de service `EInvoicing`** (`class/einvoicing.class.php`) — référentiel des statuts et motifs, contrôles de conformité (SIREN/SIRET/TVA), blocs HTML injectés dans les fiches, et toute la persistance des tables `extlinks` / `routing` / `lifecycle_msg`. Ce n'est **pas** un `CommonObject`.

### Flux sortant (facture client → PA)

```
Validation/génération PDF de la facture (hook afterPDFCreation, ou boutons manuels)
    ↓ si EINVOICING_EINVOICE_IN_REAL_TIME et facture non-brouillon
checkRequiredinformations() (SIREN, TVA, config société/tiers/facture)
    + checkRecipientRoutableForSend() (annuaire, selon EINVOICING_REQUIRE_ROUTABLE_RECIPIENT)
    ↓
$protocol->generateInvoice() → <ref>_facturx.pdf (FACTURX) ou <ref>_cii.xml (CII)
    (mapping construit par lib/buildinvoicelines.inc.php, validation locale selon EINVOICING_BR_CHECK)
    ↓ si EINVOICING_AP_PRECHECK == 'auto'
$provider->validateEInvoiceFile() (pré-contrôle distant par la PA)
    ↓ si EINVOICING_AUTO_SEND_ON_GENERATION (et facture jamais transmise)
$provider->sendInvoice() → flow_id enregistré dans llx_einvoicing_extlinks → verrou de transmission
    ↓ ensuite
Polling AJAX (checkinvoicestatus.php) + cron cronSyncFlows() → statuts de cycle de vie (200–213)
```

### Flux entrant (PA → facture fournisseur)

`Document::cronSyncFlows()` (cron horaire) ou bouton « Synchroniser » de `document_list.php` → `$provider->syncFlows()` → pour chaque flux : `syncFlow()` selon `flow_type` (`SupplierInvoice`, `CustomerInvoiceLC`, `SupplierInvoiceLC`…) → `$protocol->createSupplierInvoiceFromSource()` (synchro/création tiers, produits, lignes, remises, acomptes) → facture fournisseur brouillon + XML archivé (`xml_data`).

### Statuts

- **Statuts internes Dolibarr** (constantes `EInvoicing::STATUS_*`) : 0 UNKNOWN, 5 NOT_GENERATED, 10 GENERATED, 15 AWAITING_VALIDATION, 20 AWAITING_ACK, 25 ERROR, 99 IGNORE (98 IGNORE_2 non utilisé).
- **Statuts normalisés PDP/PA (cycle de vie)** : 200 DEPOSITED → 213 REJECTED (dont 205 APPROVED, 207 DISPUTED, 210 REFUSED, 211 PAYMENT_SENT, 212 PAID). `STATUS_REQUIRING_REASONS = [210, 207, 206, 208]` (46 motifs dans `REASONS`).
- Les statuts sortants (factures fournisseurs) sont transmis en **CDAR** (`CdarHandler`) et historisés dans `llx_einvoicing_lifecycle_msg`.

## Hooks et comportement (Hook behavior)

La classe `ActionsEInvoicing` (contextes `all` + `invoicecard`) implémente :

| Hook | Rôle |
|------|------|
| `afterPDFCreation` | Cœur du flux automatique : génération e-facture + pré-contrôle + envoi (cf. flux sortant) |
| `afterODTCreation` | Délègue à `afterPDFCreation` (les modèles ODT n'émettent pas ce hook) |
| `addMoreActionsButtons` | Facture client : dropdown `GenerateEinvoice` / `RegenerateEinvoice` / `PrecheckEinvoice` / `sendToPDP` ; neutralise en JS le bouton « Modifier » si facture verrouillée. Facture fournisseur : dropdown des statuts envoyables |
| `doActions` | Facture : `seteinvoicestatus`, `setoverriderouting`, `send_to_pdp`, `generate_einvoice`, `precheck_einvoice`. Facture fournisseur : `confirm_sendStatusMessage`. Tiers : `pdp_addrouting`, `pdp_deleterouting`, `pdp_setdefaultrouting`. Le tout sous transaction |
| `formConfirm` | Confirmation d'envoi de statut (facture fournisseur) avec sélecteur de motif |
| `formObjectOptions` | Injecte les blocs e-facture sur les fiches facture / facture fournisseur / produit / tiers |
| `completeArrayFields`, `printFieldListSelect/From/Where/Option/Title/Value` | Colonnes et filtres e-facture dans les listes factures/tiers (`einvoicegenerated`, `pdp_syncstatus`, `routing_id`…) ; exclut du contexte `accountancysupplierlist` les factures abandonnées `close_code = 'pdp_refused'` |
| `isEditable` | Facture transmise → `result = -100` : blocage de l'édition |
| `replaceThirdparty` | Fusion de tiers : réaffecte `llx_einvoicing_routing.fk_soc` |

Points d'extension **exposés** par le module :

- hook `addPDPProviders` (contexte `einvoicingproviders`) : déclaration d'un provider PDP par un module tiers (cf. `doc/ADD-A-PDP-PROVIDER.md`)
- hook `afterEinvoiceCreation` (contexte `einvoicegeneration`) : exécuté après génération de l'e-facture

## Trigger (Trigger behavior)

`InterfaceEInvoicingTriggers` (`core/triggers/interface_98_modEInvoicing_EInvoicingTriggers.class.php`) :

| Événement | Action |
|-----------|--------|
| `COMPANY_CREATE` / `COMPANY_MODIFY` | Enregistre `routing_id` (type `thirdparty`) et `routing_product_id` (type `product`) depuis le POST de la fiche tiers |
| `BILL_CREATE` / `BILL_VALIDATE` | Calcule/applique le statut e-facture initial (`needEInvoiceManagement()`) |
| `BILL_UNVALIDATE` / `BILL_DELETE` | **Blocage** si la facture a été transmise (`isTransmittedLockActive()`) |
| `BILL_MODIFY` | **Blocage** si un champ verrouillé change (ref, dates, totaux, tiers, conditions/mode de règlement) |
| `PAYMENT_CUSTOMER_CREATE` | Envoi du statut 212 « Encaissée » par encaissement (TVA sur les encaissements, paiements partiels couverts) |
| `BILL_SUPPLIER_VALIDATE` | Contrôle doublon/cohérence (si option) + clôture de la facture remplacée |
| `BILL_SUPPLIER_PAYED` | Envoi du statut 211 « Paiement transmis » (si `EINVOICING_SEND_PAYMENT_SENT_STATUS`) |
| `BILL_SUPPLIER_DELETE` / `DOCUMENT_DELETE` | **Blocage** de la suppression d'une facture fournisseur issue d'une e-facture (ou de son fichier) |

## Données / SQL (Data model)

5 tables :

| Table | Rôle |
|-------|------|
| `llx_einvoicing_document` | Flux échangés avec la plateforme : `flow_id` (UUID), `call_id`, `flow_type`/`flow_direction`/`flow_syntax`/`flow_profile`, `ack_*`, `cdar_*`, `fk_element_id`/`fk_element_type`, `provider`, `xml_data` (MEDIUMTEXT, XML sans PDF), `document_body`, `response_for_debug` |
| `llx_einvoicing_call` | Journal des appels API : `call_id` (séquence `Call-%06d`, unique par entité), `call_type`, `method`, `endpoint`, `request_body`/`response` (données sensibles caviardées), `batchlimit`/`totalflow`/`skippedflow`/`successflow`, `status` (0=Failed, 1=Success) |
| `llx_einvoicing_extlinks` | Lien objet Dolibarr ↔ e-facturation : `element_id`+`element_type` (`facture`, `invoice_supplier`, `product`, `societe`), `provider`, `flow_id`, `syncstatus` (code statut), `syncref`, `synccomment`, `ap_precheck_status`/`ap_precheck_result`, `override_routing_id` (BT-49 par facture) |
| `llx_einvoicing_routing` | Annuaire de routage par tiers : `fk_soc`, `routing_type` (`thirdparty`/`product`), `routing_id` (SIREN ou `SIREN_suffixe`), `source` (`manual`/`automatic`/`synchronisation`), `active`, `is_default` |
| `llx_einvoicing_lifecycle_msg` | Historique des statuts de cycle de vie : `element_id`/`element_type`, `flow_id`, `direction` (`IN`/`OUT`), `lc_status` (200–213), `lc_validation_status` (`Ok`/`PENDING`/`ERROR` pour les messages sortants), `lc_reason_code` |

Fichiers de migration notables :

- `dolibarr_allversions.sql` — rejoué à chaque mise à jour Dolibarr (renommages de permissions, colonnes ajoutées) ;
- `update_allversion.sql` — migration `pdpconnectfr → einvoicing` (RENAME TABLE ×5, constantes, droits, menus) ;
- `migration_unstable_dev.sql` — même contenu, **à lancer manuellement** (nommé hors convention `update_*` pour ne pas être rejoué par `_load_tables()` sur installation neuve) ;
- ⚠️ `extlinks`, `routing` et `lifecycle_msg` n'ont **aucun index** (pas de fichier `.key.sql`).

## Pages principales (Main pages)

| Page | Rôle |
|------|------|
| `document_list.php` | **Page centrale** : liste des flux + écran de synchronisation avec la PA (actions `sync`/`confirm_sync` → `syncFlows()`, contrôles `InvalidSyncLimit`/`InvalidSyncFromDate`) ; champ virtuel `recap` agrégeant `ack_*`/`cdar_*` |
| `document_card.php` + onglets (`_agenda`, `_contact`, `_document`, `_note`) | Fiche d'un flux (générée ModuleBuilder ; la barre `tabsAction` est désactivée par un `if (... && 1 == 0)`) |
| `call_list.php` / `call_card.php` | Journal des appels API (requête/réponse, résultat de traitement) |
| `product_mapping.php` | Mappage manuel des produits d'un flux entrant vers les produits Dolibarr (filtre sur statut d'achat `tobuy`) ; lit le document `Converted` (l'`Original` peut être en UBL non parsable) |
| `vendorref_list.php` | Références fournisseurs mappées : réassignement/renommage/suppression avec détection de conflit sur la clé unique `(ref_fourn, fk_soc, quantity, entity)` |
| `einvoicingindex.php` | Accueil décoratif (contenu métier commenté) |
| `public/proxy_oauthcallback.php` | Proxy OAuth pour le mode opérateur/marque grise (`NOLOGIN`, garde `EINVOICING_SUPERPDP_VIAPARTNER == 'proxy'`) |
| `admin/setup.php` | Choix de la plateforme, credentials, génération/suppression de token, healthcheck, factures d'exemple, retour OAuth (`code`+`state` traité **avant** la construction du formulaire pour ne pas régénérer le `state`) |
| `admin/setup_options.php` | Toutes les options fonctionnelles (cf. constantes ci-dessous) |
| `admin/setup_devtools.php` | Outils développeur (si `EINVOICING_ALLOW_DEVTOOLS`) : générateur de factures d'exemple, liens validateurs, aide proxy OAuth |

## Fonctions utilitaires (Library functions)

### `lib/einvoicing.lib.php`

| Fonction | Description |
|----------|-------------|
| `einvoicingAdminPrepareHead()` | Onglets admin : PASettings, Options, About, DevTools |
| `pdpShowWarning($einvoicing)` | Bandeau d'avertissement si la configuration société est invalide (mode `EINVOICING_LIVE` seulement) |
| `idprof($thirdparty)` / `thirdpartyidprof($object)` | Identifiant professionnel selon le pays (FR : SIREN, repli 9 premiers caractères du SIRET ; BE/DE : règles dédiées) |
| `einvoicingVatOnDebits()` | Vrai si le vendeur a opté pour la TVA sur les débits (`EINVOICING_VAT_POINT_DATE_CODE` sinon `TAX_MODE_SELL_*`) |
| `einvoicingVatPointDateCode($hasProduct, $hasService, $isDeposit)` | Code BT-8 (acompte → `72` toujours ; débits → `5` ; sinon `72` si TVA à l'encaissement ; `29` jamais dérivé, BR-FR-MAP-29) |
| `einvoicingVatDueOnCollection()` | Vrai si la TVA est due à l'encaissement (déclenche le statut 212) |
| `removeAllSpaces()` / `getMultidirOutputCompat()` | Utilitaires chaînes / répertoires multi-entité |
| Polyfills | `GETPOSTFLOAT()`, `getDolGlobalFloat()`, `dolPrintHTML()`, `Societe::findNearest()`, etc. — tous sous `function_exists()` |

### `lib/buildinvoicelines.inc.php`

**Template inclus** (pas une fonction) par `CIIProtocol::generateXML()` / `FacturXProtocol::generateXML()`. Construit `$invoiceData` (~120 clés : parties vendeur/acheteur, totaux, BT-8, mentions PMT/PMD/AAB, facture source pour avoirs/remplacements, factures de situation BT-25/BT-26, IBAN/BIC…) et `$linesData` (+ `$taxBreakdown`, `$globalDiscounts`) depuis l'objet `Facture`. Lève une `Exception` en cas de SIREN/SIRET/TVA invalide (`BADPROFID`, `BADVALUEFORSIRENORSIRET`, `BADVATNUMBER`).

### `lib/einvoicing_vendorref.lib.php`

`einvoicingFindConflictingVendorRef()` (détection de collision — `update_buyprice()` supprime silencieusement la ligne détenant la clé unique) et `einvoicingRemapVendorRef()` (renommage = update ; déplacement = delete + insert).

## Constantes de configuration (Key settings)

Sélection des ~66 constantes réellement lues (liste complète : grep `getDolGlobal` dans le module) :

| Constante | Description |
|-----------|-------------|
| `EINVOICING_PDP` | **Plateforme active** : `ESALINK`, `SUPERPDP`, `SUPERPDPViaPartner`, `TESTPDP` (+ providers par hook) |
| `EINVOICING_PROTOCOL` | Protocole de génération : `CII` (défaut) ou `FACTURX` |
| `EINVOICING_LIVE` | Production vs test/sandbox ; commute les credentials vers la variante `_PROD` |
| `EINVOICING_XML_PROFILE` | Profil du XML généré : `MINIMUM`, `BASICWL`, `BASIC`, `EN16931`, `EXTENDED`, `EXTENDEDFR` |
| `EINVOICING_EINVOICE_IN_REAL_TIME` | Génération automatique de l'e-facture à la validation/génération PDF |
| `EINVOICING_AUTO_SEND_ON_GENERATION` | Transmission automatique à la PA juste après génération |
| `EINVOICING_EINVOICE_CANCEL_IF_EINVOICE_FAILS` | Annule l'action si la génération de l'e-facture échoue |
| `EINVOICING_BR_CHECK` | Validation locale EN 16931 : `nocheck` / `warning_only` / `blocking` |
| `EINVOICING_AP_PRECHECK` | Pré-validation distante par la PA : `nocheck` / `manuel` / `auto` (si le provider a un validateur) |
| `EINVOICING_PRECHECK_DIRECTORY` | Vérification de joignabilité dans l'annuaire (affichage fiche facture) |
| `EINVOICING_REQUIRE_ROUTABLE_RECIPIENT` | `0`/`1`/`2` — exige un destinataire joignable avant envoi (`2` = bloque aussi « non concluant ») |
| `EINVOICING_SKIP_B2C` | Pas d'e-facture pour les particuliers |
| `EINVOICING_VAT_POINT_DATE_CODE` | Régime TVA BT-8 : `auto` / `5` / `29` / `72` (remplace `EINVOICING_VAT_ON_DEBITS`, supprimée en 1.1.0) |
| `EINVOICING_SEND_PAYMENT_SENT_STATUS` | Statut 211 automatique au paiement d'une facture fournisseur |
| `EINVOICING_PMT` / `EINVOICING_PMD` / `EINVOICING_AAB` | Mentions : frais de recouvrement / pénalités de retard / absence d'escompte |
| `EINVOICING_DISABLE_SYNC_AP_TO_DOLI` / `EINVOICING_DISABLE_SYNC_DOLI_TO_AP` | Désactivation par sens de synchronisation (⚠️ logique **inversée** : l'UI dit « Activer », la constante stocke « Désactiver ») |
| `EINVOICING_PRODUCTS_AUTO_GENERATION` / `EINVOICING_IMPORT_AS_FREE_LINES` | Import : création auto des produits, ou lignes libres |
| `EINVOICING_THIRDPARTIES_AUTO_GENERATION` / `EINVOICING_THIRDPARTIES_COMPLETE_INFO` | Import : création/complétion auto des tiers |
| `EINVOICING_FLOWS_SYNC_CALL_SIZE` / `EINVOICING_SYNC_MARGIN_TIME_HOURS` / `EINVOICING_MAX_SYNC_FLOWS` | Réglages de synchronisation (taille de lot, recul horaire, plafond) |
| `EINVOICING_ALLOW_RESEND_TRANSMITTED` / `EINVOICING_ALLOW_REGEN_TRANSMITTED` | Dev : lève le verrou de renvoi / de régénération d'une facture transmise |
| `EINVOICING_USE_CHORUS` | Active les extrafields Chorus `d4d_*` (fonctionnalité annoncée non finalisée) |
| `EINVOICING_DEBUG_MODE` | Stocke le détail complet des appels API |
| `EINVOICING_ALLOW_DEVTOOLS` | Cachée — active l'onglet DevTools et le provider TESTPDP |
| `EINVOICING_SUPERPDP_VIAPARTNER` / `_ONLY` / `_OAUTH_URL` | Cachées — mode opérateur/marque grise SuperPDP (valeur `proxy` = mode proxy OAuth) |
| `EINVOICING_PREFER_ORIGINAL` | Cachée — préfère le document `Original` au `Converted` à l'import |
| `EINVOICING_SUPPLIER_INVOICE_CHECK_CONSISTENCY_ON_VALIDATION` | Contrôle de cohérence à la validation — l'en-tête de `SupplierInvoiceHelper` la déclare « seriously bugged. Do not use it. » |

**Credentials par provider** : préfixe = `dol_prefix` du provider (`EINVOICING_SUPERPDP_`, `EINVOICING_ESALINK_`, `EINVOICING_TESTPDP_`…), suffixe `_PROD` si `EINVOICING_LIVE` — ex. `EINVOICING_ESALINK_USERNAME[_PROD]`, `_PASSWORD[_PROD]`, `_API_KEY[_PROD]`, `EINVOICING_SUPERPDP_CLIENT_ID[_PROD]`, `_CLIENT_SECRET[_PROD]`, `<PREFIX>ROUTING_ID`.

## Conventions de développement (Development conventions)

- **Module tiers** : tags `// InfraS add` / `// InfraS change` obligatoires sur toute modification (cf. section dédiée en tête).
- Le module suit les conventions Dolibarr core (ModuleBuilder), **pas** les conventions InfraS (pas d'indentation 1 tab du corps, pas de changelog XML `docs/changelog.xml`, fichiers `.lang` sans alignement des `=`).
- Compatibilité PHP 7.2+ et Dolibarr 17+ : ne pas utiliser de fonctions core récentes sans polyfill (`compat/`, polyfills de `einvoicing.lib.php`) ni de syntaxe PHP > 7.2.
- Les appels API passent toujours par `callApi()` du provider (journalisation `logCall()` avec caviardage des secrets, connexion BDD indépendante pour survivre aux rollbacks — issue #291).
- Entrées utilisateur via `GETPOST*`, SQL sécurisé (`$db->escape()`, cast `(int)`), multi-entité via `entity`.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / hooks / extrafields :

1. Désactiver puis réactiver le module (rejoue `_load_tables` + extrafields + `dolibarr_allversions.sql`)
2. Vérifier les 5 tables `llx_einvoicing_*`
3. Vérifier les extrafields Chorus `d4d_*` (si `EINVOICING_USE_CHORUS`)
4. Vérifier `admin/setup.php` (provider sélectionné, token, healthcheck)
5. Tester une génération d'e-facture (bouton « Générer l'e-facture » sur une facture validée), puis un cycle complet en sandbox (`EINVOICING_LIVE=0`) : génération → pré-contrôle → envoi → synchronisation
6. Vérifier la tâche cron `EInvoicingDocumentSync` (désactivée par défaut : l'activer explicitement en production)

## Points d'attention (Watchpoints)

- **Verrou de transmission** : dès qu'une facture a un `flow_id` dans `extlinks`, elle est verrouillée (édition, suppression, dévalidation, renvoi) — dérogations dev via `EINVOICING_ALLOW_RESEND_TRANSMITTED` / `EINVOICING_ALLOW_REGEN_TRANSMITTED`, hors production uniquement.
- Le hook est déclaré sur le contexte **`all`** : `ActionsEInvoicing` s'exécute sur toutes les pages — attention aux impacts de performance et aux effets de bord lors de modifications.
- Les fichiers e-facture générés sont nommés `<ref>_facturx.pdf` / `<ref>_cii.xml` dans le répertoire de documents de la facture (`getEInvoiceFilePath()`).
- `syncFlows()` diffère fortement entre providers : SuperPDP pagine **par curseur** `updatedAfter` (l'API ignore `offset`, plafonne à 100), Esalink par comptage total avec arrêt à la première erreur.
- Tokens OAuth stockés dans `llx_oauth_token` (`service = <dol_prefix>_PROD|_TEST`, par entité), avec repli sur constantes pour Dolibarr < 23. Access token SuperPDP : 30 min ; refresh token : 1 an avec rotation.
- La distinction sandbox/production SuperPDP ne passe **pas** par l'URL (identiques) mais par les credentials `_PROD` et le `scheme` d'entreprise (`sandbox` vs `fr_siren`).
- Les endpoints AJAX sont en `NOCSRFCHECK` (le `token` transmis n'est pas vérifié) ; la protection réelle est `hasRight('einvoicing', ...)` + `restrictedArea()` sur l'objet métier. `restrictedArea()` est par ailleurs **commenté** dans les pages `document_*`/`call_*`.
- 3 tables sans index (`extlinks`, `routing`, `lifecycle_msg`) : surveiller les performances sur gros volumes.
- La logique des interrupteurs `EINVOICING_DISABLE_SYNC_*` est **inversée** par rapport à l'UI (« Activer » affiché, « Désactiver » stocké).
- `migration_unstable_dev.sql` ne doit être lancé **qu'une fois, manuellement**, uniquement pour migrer une ancienne installation `pdpconnectfr`.
- La numérotation `getNextNumRef()` de `Document`/`Call` référence `core/modules/einvoicing/` qui **n'existe pas** — ne pas s'appuyer dessus (les `call_id` utilisent `Call::getNextCallId()`).
- `EINVOICING_SUPPLIER_INVOICE_CHECK_CONSISTENCY_ON_VALIDATION` est documentée dans le code comme buggée — ne pas l'activer.
- `public/proxy_oauthcallback.php` : la `redirect_uri` n'est **pas validée contre une liste blanche** (TODO amont non implémentés) — vigilance si le mode proxy est activé.

## Dernières mises à jour (Recent updates)

Voir `ChangeLog.md` (format Markdown, pas de `docs/changelog.xml` InfraS). Faits notables :

- **1.0.3** : profils XML configurables (`EINVOICING_XML_PROFILE`), statut 212 avec ventilation TVA (blocs MEN, BR-FR-CDV-14/16), statut 211, contrôle SIREN sur toutes les parties, BT-8, abandon automatique des factures fournisseurs refusées, contrôle du montant réclamé (#506).
- **1.0.4** (en cours) : retour du support Dolibarr 17, hook `addPDPProviders` pour providers externes, `EINVOICING_VAT_POINT_DATE_CODE`, TVA NPR exonérée `VATEX-FR-CGI295`, écran « Références fournisseurs mappées », résolution en 4 niveaux de l'adresse fournisseur (CDAR MDT-73).

## Notes techniques (Technical notes)

### Protocoles — architecture et ajout

```
AbstractProtocol (abstract)
   └── CIIProtocol            + use CommonProtocol (trait)
            └── FacturXProtocol
CommonProtocol   = TRAIT (malgré le nom de fichier .class.php)
ProtocolManager  = fabrique (liste en dur : CII actif, FACTURX actif, UBL désactivé)
```

- `FacturXProtocol::generateInvoice()` : résolution du PDF porteur en 3 priorités (chemin du hook avec conversion ODT→PDF ; PDF le plus récent ; régénération), puis embarquement du XML par TCPDF/FPDI (Dolibarr < 24) ou `CtcFrPdfMerger` (≥ 24).
- Le mapping **sortant** vit dans `lib/buildinvoicelines.inc.php` ; le mapping **entrant** est déclaratif dans le trait (`$invoiceTemplate` ~70 clés, `$lineTemplate` ~40 clés).
- Import fournisseur (`CIIProtocol::createSupplierInvoiceFromSource()`) : 2 transactions (synchro tiers commitée seule, puis import atomique) ; résolution du tiers en 3 étapes (identifiants structurés → n° TVA → `findNearest()`).
- **Ajouter un protocole** : créer `<NOM>Protocol.class.php`, étendre `CIIProtocol` ou `AbstractProtocol` + `use CommonProtocol`, implémenter les 7 méthodes abstraites **plus `generateInvoice()`** (appelée partout mais absente du contrat abstrait), puis enregistrer dans `$protocolsList` **et** le `switch` de `ProtocolManager::getProtocol()`, et ajouter une branche à `detectProtocolFromContent()`.

### Providers PDP — architecture et ajout

```
AbstractPDPProvider (abstract, 11 méthodes abstraites)
├── SuperPDPProvider   (OAuth 2.1 : client_credentials, authorization_code, refresh_token)
├── EsalinkPDPProvider (client_credentials + en-tête hubtimize-api-key ; sandbox ppd.hubtimize.fr)
└── TestPDPProvider    (référence, n'appelle rien ; visible si EINVOICING_ALLOW_DEVTOOLS)
PDPProviderManager = fabrique + hook addPDPProviders (contexte einvoicingproviders)
```

Procédure d'ajout : `doc/ADD-A-PDP-PROVIDER.md`. Points vérifiés dans le code (divergences doc ↔ code) :

- `initFormSetup()` est **obligatoire** (fatal sinon, `admin/setup.php` l'appelle sans `method_exists()`), de même que `deleteAccessToken()` si le formulaire affiche le lien de suppression (TestPDP, la « référence », ne l'implémente pas → fatal au clic) ;
- `dol_prefix` pilote toute la convention de nommage (constantes + actions `set<PREFIX>TOKEN`, `call<PREFIX>HEALTHCHECK`…) ;
- `checkHealth()` doit renvoyer un `status_code` comparé à `== 200` par `setup.php` (les providers actuels renvoient un booléen — ne fonctionne que par coercition) ;
- `sendInvoice()` a plusieurs formes de retour selon le provider (flowId, `false`, `0`, `array{res,message}`).

### CDAR (`CdarHandler`)

Messages `rsm:CrossDomainAcknowledgementAndResponse` (UN/CEFACT, XP Z12-012 annexe B) portant les statuts de cycle de vie sortants. `generateCdarFile()` : résolution de l'adresse électronique du fournisseur (MDT-73) en 4 niveaux (routing Dolibarr → BT-34 de l'e-facture reçue → annuaire PDP → SIREN) ; statut 212 refusé si les blocs MEN (ventilation TVA au prorata TTC) ne sont pas calculables ; fichier temporaire `cdar_{code}_{16 hex}.xml` (anti-collision #226).

### Validation locale EN 16931 (`En16931Validator`)

Validateur PHP pur (XPath sur DOM) — **ni XSD ni Schematron** : BR-16/27, BR-*-05, BR-CO-10..17, BG-22, BR-FR-10/32 (SIREN 9 chiffres sur toutes les parties), tolérance `0.011`. Ce n'est **pas** un remplacement du Schematron officiel, toujours appliqué côté PA ; piloté par `EINVOICING_BR_CHECK`.

### Bibliothèques vendorées (`vendor/`)

Cœur : **`horstoeko/zugferd` v1.0.123** (MIT — création/lecture ZUGFeRD/Factur-X, XSD officiels) + `setasign/fpdf`/`fpdi`, `smalot/pdfparser`, `jms/serializer`, `symfony/*` 5.4. Composer désactivé (`composer.disabled.json`, plateforme figée PHP 7.3) : **ne pas lancer `composer install`**, les dépendances sont vendorées.

### Anomalies connues (upstream)

Recensées lors de l'analyse (2026-08), à connaître avant d'intervenir — la liste complète est dans l'historique du dépôt amont :

- `VERSION` (1.0.3) en retard sur `ChangeLog.md` (1.0.4) ;
- `Call::$fields['status']['arrayofkeyval']` contredit les constantes `STATUS_*` ;
- `PDPProviderManager::__construct()` n'affecte jamais `$this->db` (utilisé par `addProvidersFromHooks()`) ;
- `CIIProtocol` passe `$outputlangs` en 4ᵉ argument à `buildXML()` qui n'en déclare que 3 (langue ignorée) ;
- `EsalinkPDPProvider::callApi()` sans lectures défensives `??`/`isset()` (correctif SuperPDP non rétroporté, fatal PHP 8 possible) ;
- `document_card.php` : `tabsAction` désactivée par `if (... && 1 == 0)` ; `call_card.php` : lien vers `call_agenda.php` inexistant ; `ajax/document.php` non branché ;
- chemin externe Factur-X (`EINVOICING_USE_EXTERNAL_FACTURX_BUILDER`, déprécié) : produit toujours le profil EXTENDED ;
- `document_list.php` et `getLastSyncDate()` filtrent sur `entity = $conf->entity` (pas de `getEntity()`).
