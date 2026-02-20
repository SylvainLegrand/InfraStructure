# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**InfraSPackPlus** is a Dolibarr extension that provides comprehensive PDF document customization and enhanced document generation across all major Dolibarr object types. Key features include custom PDF models for 15+ document types (proposals, orders, invoices, contracts, shipments, receptions, interventions, products, projects, expense reports, MRP, BOM, stock, users, members), multi-address management per third party, custom font support, signature areas with electronic signing (jSignature), supplementary mentions and public notes dictionaries, CGV/CGA/CGI attachment, watermarks, product images in documents, column layout control, special headers/footers, page substitution, and Easya compatibility.

- **Module ID:** 550000
- **Dolibarr Compatibility:** 18 - 23 (branches with `_18` through `_22` version-specific templates, plus Easya variants)
- **PHP Compatibility:** 7.4 - 8.4
- **License:** GPL v3+
- **Dependencies:** Requires `modECM` (Document Management). Optional integration with `milestone`, `subtotal`, `ouvrage`, `customlink`, `infraspackplus` (self-config).
- **Author:** InfraS - Sylvain Legrand

## Development Commands

No local test runner. Version is managed in `docs/changelog.xml` (XML format, read by module descriptor via `infraspackplus_getLocalVersionMinDoli()`). The module auto-disables if Dolibarr version is below the minimum required.

## Architecture

### Core Classes

| File | Purpose |
|------|---------|
| `core/modules/modinfraspackplus.class.php` | Module descriptor (ID 550000) - 12 permissions, 12 menu entries, 2 dictionaries (mentions, notes), hooks, triggers, font sync on init, address migration, backup/restore |
| `class/actions_infraspackplus.class.php` | Main hook handler (1749 lines) - page substitution, PDF generation hooks, build doc options UI, logo emitter management on third-party cards, semi-auto PDF update, object line display customization |
| `class/address.class.php` | Address CRUD class for `llx_infraspackplus_societe_address` - multiple sender addresses per third party |
| `core/triggers/interface_90_modinfraspackplus_Infraspackplustrigger.class.php` | Trigger on `COMPANY_CREATE` (logo emitter assignment) and `COMPANY_DELETE` (address cleanup) |
| `core/lib/infraspackplus.lib.php` | Core business functions (1993 lines) - special headers/footers, DB field checks, module compatibility tests, CGV file management, logo emitter, address display, page substitution, default parameters, semi-auto update, entity copy |
| `core/lib/infraspackplus.pdf.lib.php` | PDF rendering functions (5050+ lines) - all PDF building blocks: values/config, format/instance, colors, watermarks, logos, addresses, linked objects, VAT mentions, free text, notes, signatures, QR codes, page footer, subtotals, line rendering, extrafields in PDF |
| `core/lib/infraspackplusAdmin.lib.php` | Admin functions (1125+ lines) - tab builder, PHP extension checks, version/changelog management, address migration, backup/restore, template management, admin UI helpers (inputs, colgroups, titles, buttons) |

### Hook Integration

The `ActionsInfraspackplus` class hooks into multiple Dolibarr contexts:

- **`main`** / **`login`** (`updateSession`, `afterLogin`): Page substitution - redirects standard Dolibarr pages to custom versions when configured. Also performs version compatibility check and DB configuration validation on login.
- **`formfile`** (`formBuilddocOptions`): Injects comprehensive PDF generation options panel on document cards (propal, commande, facture, contrat, fichinter, shipping, reception, delivery, supplier_proposal, order_supplier, product, mo, bom, project, expensereport). Options include: logo selection, sender/recipient address selection, supplementary mentions, public notes, footer choice, delivery address, sub-contractor address, CGV/CGA/CGI, column visibility, discount display, signature areas, file attachments, and customer electronic signing (jSignature).
- **`pdfgeneration`** (`beforePDFCreation`): Sets session flag `InfraSPackPlus_model`, retrieves saved per-document/per-user/default parameters, passes logo/addresses/mentions/notes/footer/delivery address/sub-contractor to PDF via `$this->results`.
- **`pdfgeneration`** (`afterPDFCreation`): Clears session flag.
- **`thirdpartycard`** / **`globalcard`** (`formObjectOptions`): Adds logo emitter selection field on third-party create/edit/view cards (when `INFRASPLUS_PDF_SET_LOGO_EMET_TIERS` is enabled).
- **`thirdpartycard`** / **`globalcard`** (`doActions`): Saves logo emitter choice on third-party update. Also handles semi-auto PDF regeneration on notes/extrafields/fields changes (when `INFRASPLUS_PDF_SEMIAUTOUPDATE` is enabled) for all document types.
- **Note contexts** (`propalnote`, `ordernote`, `invoicenote`, `contractnote`, etc.): Semi-auto PDF update when notes are modified.
- **`formfile`** (`printObjectLine`): Customizes object line display to show discount column with custom templates when `INFRASPLUS_PDF_SHOW_DISCOUNT_OPT` is enabled. Uses version-routed templates from `substitutionpages/`.

### Trigger System

The `InterfaceInfraspackplustrigger` trigger handles only `societe` element:

| Trigger | Action |
|---------|--------|
| `COMPANY_CREATE` | Assigns logo emitter to the new third party via `infraspackplus_setLogoEmet()` when `INFRASPLUS_PDF_SET_LOGO_EMET_TIERS` is enabled |
| `COMPANY_DELETE` | Deletes all associated addresses from `llx_infraspackplus_societe_address` |

### PDF Document Models

The module provides **37 PDF model files** across 19 document categories:

| Category | Models | Description |
|----------|--------|-------------|
| `propale` | `InfraSPlus_D`, `InfraSPlus_DP`, `InfraSPlus_DST` | Proposals, proposals with pictures, sub-contractor proposals |
| `commande` | `InfraSPlus_C`, `InfraSPlus_BLC`, `InfraSPlus_CBC`, `InfraSPlus_CBL`, `InfraSPlus_CP`, `InfraSPlus_OF`, `InfraSPlus_OM` | Orders, order+delivery, customer BC, order+BL, order with pictures, order form, manufacturing order |
| `facture` | `InfraSPlus_F`, `InfraSPlus_FL`, `InfraSPlus_FR`, `InfraSPlus_FT` | Invoices, landscape, receipt, ticket |
| `contract` | `InfraSPlus_CT`, `InfraSPlus_CTS` | Contracts, contract summary |
| `expedition` | `InfraSPlus_BL`, `InfraSPlus_BLX`, `InfraSPlus_ET` | Shipments, extended shipment, shipping label |
| `delivery` | `InfraSPlus_BR` | Delivery receipt |
| `reception` | `InfraSPlus_RE` | Reception |
| `fichinter` | `InfraSPlus_FI` | Interventions |
| `product` | `InfraSPlus_P`, `InfraSPlus_P2`, `InfraSPlus_PBC` | Product sheets, variant 2, barcode |
| `project` | `InfraSPlus_PJ`, `InfraSPlus_PJ_Dossier` | Projects, project dossier |
| `expensereport` | `InfraSPlus_NDF` | Expense reports |
| `mrp` | `InfraSPlus_MRP` | Manufacturing orders |
| `bom` | `InfraSPlus_Bom` | Bill of materials |
| `stock` | `InfraSPlus_ST` | Stock documents |
| `supplier_invoice` | `InfraSPlus_FF` | Supplier invoices |
| `supplier_order` | `InfraSPlus_CF`, `InfraSPlus_CFBL` | Supplier orders, supplier order+BL |
| `supplier_proposal` | `InfraSPlus_DF` | Supplier proposals |
| `user` | `InfraSPlus_UST`, `InfraSPlus_User_Contrat` | User stickers/badges, user contracts |
| `societe` | `InfraSPlus_account_statut` | Third-party account status |
| `member` | `pdf_infrasplus` | Member cards |
| `specialhead` / `specialfoot` | `interne.pdf.head.php`, `interne.pdf.foot.php` | Custom PDF header/footer templates |

### Address Management

Multiple sender addresses per third party, stored in `llx_infraspackplus_societe_address`. Managed via:
- **`class/address.class.php`**: CRUD operations (create, fetch, update, delete, verify)
- **`comm/address.php`**: UI page for managing addresses
- **`infraspackplus_show_addresses()`**: Renders address list in third-party card
- PDF generation allows selecting a specific sender address per document

### Page Substitution System

Replaces standard Dolibarr pages with custom versions for version-specific compatibility:

| Directory | Dolibarr Version | Variant |
|-----------|-----------------|---------|
| `substitutionpages/dlb180x/` | 18.0.x | Standard |
| `substitutionpages/dlb180x-Easya/` | 18.0.x | Easya |
| `substitutionpages/dlb190x/` | 19.0.x | Standard |
| `substitutionpages/dlb200x/` | 20.0.x | Standard |
| `substitutionpages/dlb210x/` | 21.0.x | Standard |
| `substitutionpages/dlb220x/` | 22.0.x | Standard |
| `substitutionpages/dlb220x-Easya/` | 22.0.x | Easya |

Configured via `INFRASPACKPLUS_PS_ACTIVE_*` constants and routed by `infraspackplus_get_substitution_url()`. The `updateSession` / `afterLogin` hooks intercept page loads and redirect when a substitution is active.

### Object Line Templates

Version-routed templates for customizing object line display (discount column):

| Template | Versions |
|----------|----------|
| `core/tpl/objectline_view.tpl.php` | Wrapper file |
| `core/tpl/objectline_view_18.tpl.php` | Dolibarr 18 |
| `core/tpl/objectline_view_18-Easya.tpl.php` | Dolibarr 18 Easya |
| `core/tpl/objectline_view_19.tpl.php` | Dolibarr 19 |
| `core/tpl/objectline_view_20.tpl.php` | Dolibarr 20 |
| `core/tpl/objectline_view_21.tpl.php` | Dolibarr 21 |
| `core/tpl/objectline_view_22.tpl.php` | Dolibarr 22+ |
| `core/tpl/objectline_view_22-Easya.tpl.php` | Dolibarr 22+ Easya |

### Backport System

The `backport/` directory contains core Dolibarr file patches for older versions:
- `backport/v20/` - Patches for Dolibarr v20 compatibility
- `backport/v21/` - Patches for Dolibarr v21 compatibility

### Bundled Fonts

Custom TCPDF font definitions in `fonts/` for PDF rendering (~35 font families):
Arial, Calibri, Cambria, Caviar Dreams, Century Gothic, Corbel, Lucida Bright, Perpetua, Tahoma, Trebuchet MS, and more.

Fonts are synced to the Dolibarr data directory on module activation (`init()` copies from `fonts/` to `DOL_DATA_ROOT/fonts/`).

### Bundled Libraries

| Library | Directory | Purpose |
|---------|-----------|---------|
| jSignature | `includes/jsignature/` | Client-side electronic signature capture |
| Milestone | `includes/milestone/` | Integration with Milestone module |
| Subtotal | `includes/subtotal/` | Integration with Subtotal/ATM module |
| TCPDF (tecnickcom) | `includes/tecnickcom/` | PDF generation library (custom build) |

### Database

#### Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `llx_infraspackplus_societe_address` | Multiple sender addresses per third party | `rowid`, `entity`, `fk_soc`, `label`, `name`, `address`, `zip`, `town`, `fk_pays`, `phone`, `fax`, `email`, `url`, `note` |
| `llx_c_infraspackplus_mention` | Supplementary mentions dictionary | `rowid`, `code`, `entity`, `pos`, `libelle`, `active` |
| `llx_c_infraspackplus_note` | Public notes dictionary | `rowid`, `code`, `entity`, `pos`, `libelle`, `active` |

Additionally, the module adds a `logo_emet` column to `llx_societe` (via `sql/llx_societe-logo_emet.sql`) for per-third-party logo emitter assignment.

#### SQL Files

| File | Purpose |
|------|---------|
| `sql/llx_infraspackplus_societe_address.sql` + `.key.sql` | Address table schema and indexes |
| `sql/llx_c_infraspackplus_mention.sql` + `.key.sql` | Mentions dictionary schema |
| `sql/llx_c_infraspackplus_note.sql` + `.key.sql` | Notes dictionary schema |
| `sql/llx_societe-logo_emet.sql` | Adds `logo_emet` column to `llx_societe` |
| `sql/data.sql` | Initial constants (~300 `INFRASPLUS_*` constants) and dictionary seed data |
| `sql/updates.sql` | Schema migration updates |
| `sql/clean_from_infraspack.sql` | Migration from legacy `infraspack` module |

### Admin Pages

| File | Tab | Purpose |
|------|-----|---------|
| `admin/infrasplussetup.php` | Dolibarr Settings | Core Dolibarr PDF-related settings override |
| `admin/generalpdf.php` | General PDF | Global PDF layout: font, colors, columns, spacing, frames, lines, watermarks, signatures |
| `admin/images.php` | Images | Product images in PDF, watermark images, footer images, logo management |
| `admin/adresses.php` | Addresses | Sender/recipient address configuration, delivery address, sub-contractor, address format |
| `admin/extrafields.php` | ExtraFields | Extrafields display in PDF documents (per document type) |
| `admin/mentions.php` | Mentions | Supplementary mentions management, VAT auto-mention, free text configuration |
| `admin/notes.php` | Notes | Public notes management, notes as cover page, sales rep in notes |
| `admin/dictionaries.php` | Dictionaries | Custom dictionaries (mentions + notes) management |
| `admin/generation.php` | Generation | Document models per type, CGV/CGA/CGI setup, special files, multi-file merging |
| `admin/about.php` | About | README.md rendered as HTML, support info |
| `admin/changelog.php` | Changelog | Version history from XML, update check |

### Key Library Functions

#### infraspackplus.lib.php

| Function | Purpose |
|----------|---------|
| `infraspackplus_fetchAllSpecialHeads()` | Fetches all custom PDF header templates |
| `infraspackplus_fetchAllSpecialFooters()` | Fetches all custom PDF footer templates |
| `infraspackplus_test_new_fields()` | Validates DB configuration for required fields |
| `infraspackplus_test_tables()` | Checks if required tables exist |
| `infraspackplus_test_module()` | Tests and patches external module compatibility |
| `infraspackplus_isLineFromExternalModule()` | Detects lines from external modules (Ouvrage, Subtotal) |
| `infraspackplus_get_CGfiles()` | Retrieves CGV/CGA/CGI PDF files per entity |
| `infraspackplus_getLogoEmet()` / `infraspackplus_setLogoEmet()` | Get/set logo emitter for a third party |
| `select_infraspackplus_dict()` | Renders HTML select from custom dictionary |
| `infraspackplus_show_addresses()` | Renders address list for third-party card |
| `infraspackplus_is_substitution_page()` / `infraspackplus_get_substitution_url()` | Page substitution detection and URL resolution |
| `infraspackplus_defaultParam()` | Retrieves saved PDF generation parameters (per-document, per-user, or default) |
| `infraspackplus_semiauto_update()` | Semi-automatic PDF regeneration on document changes |
| `infraspackplus_copy_entity()` | Copies all module constants from one entity to another |
| `infraspackplus_search_extf()` | Searches and optionally creates extrafields |
| `infraspackplus_search_dict()` | Searches and optionally creates dictionary entries |
| `infraspackplus_Add_TCPDF_Font()` | Registers a custom font with TCPDF |

#### infraspackplus.pdf.lib.php (key functions)

| Function | Purpose |
|----------|---------|
| `pdf_InfraSPlus_getInstance()` | Creates/configures TCPDF instance with InfraSPlus settings |
| `pdf_InfraSPlus_getValues()` | Loads all PDF configuration values into template |
| `pdf_InfraSPlus_logo()` | Renders logo in PDF header |
| `pdf_InfraSPlus_getAddresses()` | Builds sender/recipient/delivery/sub-contractor address blocks |
| `pdf_InfraSPlus_build_address()` | Formats a single address block with professional IDs |
| `pdf_InfraSPlus_writeAddresses()` / `pdf_InfraSPlus_writeFrame()` | Renders address frames in PDF |
| `pdf_InfraSPlus_VAT_auto()` | Auto-detects VAT mention based on buyer/seller/transaction type |
| `pdf_InfraSPlus_free_text()` | Renders supplementary mentions/free text area |
| `pdf_InfraSPlus_Notes()` | Renders public notes section (optionally as cover page) |
| `pdf_InfraSPlus_writelinedesc()` | Renders line description with images, links, extrafields |
| `pdf_InfraSPlus_pagefoot()` | Renders page footer with optional image |
| `pdf_InfraSPlus_CGV()` | Appends CGV/CGA/CGI PDF pages |
| `pdf_InfraSPlus_files()` | Merges attached files into PDF |
| `pdf_InfraSPlus_watermark()` | Renders text/image watermark |
| `pdf_InfraSPlus_Client_Sign()` | Embeds client electronic signature |
| `pdf_InfraSPlus_buildZATCAQRString()` | Builds ZATCA QR code for Saudi invoices |
| `pdf_InfraSPlus_buildSwitzerlandQRString()` | Builds Swiss QR-bill payment string |
| `pdf_InfraSPlus_buildBelgiumQRString()` | Builds Belgian payment QR code |
| `pdf_InfraSPlus_add_e_signature()` | Adds electronic signature to PDF |
| `pdf_InfraSPlus_subtotal_recap()` | Renders subtotal recap section (ATM Subtotal integration) |

#### infraspackplusAdmin.lib.php

| Function | Purpose |
|----------|---------|
| `infraspackplus_admin_prepare_head()` | Builds admin page tabs array |
| `infraspackplus_no_topmenu()` | Checks if InfraS menu exists in tools top menu |
| `infraspackplus_test_php_ext()` | Validates PHP XML extension availability |
| `infraspackplus_getLocalVersionMinDoli()` | Reads version and min Dolibarr version from changelog.xml |
| `infraspackplus_migration_societe_address()` | Migrates addresses from legacy infraspack module |
| `infraspackplus_bkup_module()` | Backups module constants to SQL file |
| `infraspackplus_restore_module()` | Restores module constants from backup SQL |
| `infraspackplus_Change_Template()` | Manages PDF model activation/deactivation |
| `infraspackplus_getChangeLog()` | Renders changelog HTML from XML data |
| `infraspackplus_getSupportInformation()` | Renders support/contact information |
| `infraspackplus_print_input()` | Renders admin form inputs (on_off, select, text, color, etc.) |
| `infraspackplus_load_title()` | Admin page section title renderer |

### Key Configuration Constants

The module defines ~300 constants prefixed with `INFRASPLUS_PDF_*`. Major categories:

#### Layout & Appearance

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPLUS_PDF_FONT` | PDF font family | `centurygothic` |
| `INFRASPLUS_PDF_BACKGROUND_COLOR` | Background/accent color (R,G,B) | `109,70,140` |
| `INFRASPLUS_PDF_TEXT_COLOR` | Text color (R,G,B) | `0,0,0` |
| `INFRASPLUS_PDF_TEXT_COLOR_AUTO` | Auto-contrast text color based on background | `1` |
| `INFRASPLUS_PDF_ROUNDED_REC` | Rounded rectangle radius | `0.001` |
| `INFRASPLUS_PDF_HEIGHT_HEAD_SEP` | Header/content separator height | `60` |
| `INFRASPLUS_PDF_FOLD_MARK` | Show fold marks on page | `0` |
| `INFRASPLUS_PDF_HIDE_PAGE_NUM` | Hide page numbers | `0` |

#### Columns & Table Layout

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPLUS_PDF_LARGCOL_REF` | Reference column width | `20` |
| `INFRASPLUS_PDF_LARGCOL_QTY` | Quantity column width | `10` |
| `INFRASPLUS_PDF_LARGCOL_UP` | Unit price column width | `22` |
| `INFRASPLUS_PDF_LARGCOL_TOTAL` | Total HT column width | `24` |
| `INFRASPLUS_PDF_LARGCOL_TVA` | VAT column width | `10` |
| `INFRASPLUS_PDF_LARGCOL_DISC` | Discount column width | `14` |
| `INFRASPLUS_PDF_NUMCOL_*` | Column ordering (REF, DESC, QTY, UNIT, UP, TVA, DISC, UPD, PROGRESS, TOTAL, TOTAL_TTC) | various |
| `INFRASPLUS_PDF_WITH_REF_COLUMN` | Show reference column | `0` |
| `INFRASPLUS_PDF_WITH_TTC_COLUMN` | Show TTC column | `0` |
| `INFRASPLUS_PDF_WITH_NUM_COLUMN` | Show line number column | `0` |

#### Signatures

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE` | Show signature area on proposals | `1` |
| `INFRASPLUS_PDF_COMMANDE_SHOW_SIGNATURE` | Show signature area on orders | `1` |
| `INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE` | Show signature area on interventions | `1` |
| `INFRASPLUS_PDF_EXPEDITION_SHOW_SIGNATURE` | Show signature area on shipments | `0` |
| `INFRASPLUS_PDF_GET_CUSTOMER_SIGNING` | Enable electronic customer signing (jSignature) | `0` |
| `INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR` | Signature pen color (R,G,B) | `0,0,0` |
| `INFRASPLUS_PDF_SIGNATURE_EMET` | Emitter signature image path | empty |
| `INFRASPLUS_PDF_HT_SIGN_AREA` | Signature area height | `8` |

#### Addresses

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON` | Show delivery address on PDF | `0` |
| `INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION` | Show reception address on PDF | `0` |
| `INFRASPLUS_PDF_USE_CUSTOM_COUNTRY_ADDR` | Use custom country address format | `0` |
| `INFRASPLUS_PDF_SET_LOGO_EMET_TIERS` | Enable per-third-party logo emitter | `0` |
| `INFRASPLUS_PDF_SHOW_EMET_DETAILS` | Show emitter details (phone, fax, email, web) | `0` |
| `INFRASPLUS_PDF_SHOW_NUM_CLI` | Show customer number on PDF | `0` |

#### Product Images

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPLUS_PDF_WITH_PICTURE` | Show product images in PDF | `0` |
| `INFRASPLUS_PDF_PICTURE_WIDTH` | Image width in mm | `16` |
| `INFRASPLUS_PDF_PICTURE_HEIGHT` | Image height in mm | `32` |
| `INFRASPLUS_PDF_PICTURE_IN_REF` | Place image in reference column | `0` |
| `INFRASPLUS_PDF_PICTURE_UNDER` | Place image below description | `0` |

#### Watermarks

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPLUS_PDF_ENABLE_TEST_WATERMARK` | Watermark text for draft documents | empty |
| `INFRASPLUS_PDF_IMAGE_WATERMARK` | Watermark image path | empty |
| `INFRASPLUS_PDF_I_WATERMARK_OPACITY` | Image watermark opacity | `1` |
| `INFRASPLUS_PDF_T_WATERMARK_OPACITY` | Text watermark opacity | `10` |
| `INFRASPLUS_PDF_FACTURE_PAID_WATERMARK` | Watermark for paid invoices | empty |
| `INFRASPLUS_PDF_PROPAL_PROV_WATERMARK` | Watermark for draft proposals | `0` |

#### Semi-Auto Update

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPLUS_PDF_SEMIAUTOUPDATE` | Enable PDF auto-regeneration on changes | `0` |
| `INFRASPLUS_PDF_UPDATE_ON_NOTES_CHANGE` | Regenerate PDF when notes change | `0` |
| `INFRASPLUS_PDF_UPDATE_ON_EXF_CHANGE` | Regenerate PDF when extrafields change | `0` |
| `INFRASPLUS_PDF_UPDATE_ON_FIELDS_CHANGE` | Regenerate PDF when fields change (Dolibarr hook) | `0` |

#### CGV/CGA/CGI

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPLUS_PDF_CGV` | CGV document path | empty |
| `INFRASPLUS_PDF_CGA` | CGA document path | empty |
| `INFRASPLUS_PDF_CGI` | CGI document path | empty |
| `INFRASPLUS_PDF_CGV_BY_DEF_FOR_PROPOSALS` | Attach CGV by default on proposals | empty |
| `INFRASPLUS_PDF_CGV_BY_DEF_FOR_ORDERS` | Attach CGV by default on orders | empty |
| `INFRASPLUS_PDF_CGV_BY_DEF_FOR_INVOICES` | Attach CGV by default on invoices | empty |
| `INFRASPLUS_PDF_CGV_BY_DEF_FOR_CONTRACTS` | Attach CGV by default on contracts | empty |

#### Page Substitution

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASPACKPLUS_PS_ACTIVE_ADMIN_DICT` | Activate admin dictionary page substitution | `1` |
| `INFRASPACKPLUS_PS_ACTIVE_SOCIETE_CONTACT` | Activate third-party contact page substitution | `0` |

### Permissions

| Permission Key | Description |
|---------------|-------------|
| `paramMenu` | View the settings menu (default: enabled) |
| `paramDolibarr` | Modify Dolibarr PDF settings |
| `paramInfraSPlus` | Modify InfraSPlus general settings |
| `paramImages` | Modify images settings |
| `paramAdresses` | Modify addresses settings |
| `paramExtraFields` | Modify extrafields settings |
| `paramMentions` | Modify mentions settings |
| `paramNotes` | Modify notes settings |
| `paramDict` | Modify dictionaries |
| `paramGeneration` | Modify generation/models settings |
| `paramBkpRest` | Backup / Restore module parameters |
| `paramLastOpt` | Access last generation options (advanced per-document options visible in build doc panel) |
| `paramCGV` | Modify CGV/CGA/CGI settings |

### CSS

- `css/infraspackplus.css.php` - Dynamic CSS (loaded via `module_parts`) for admin layouts, build doc options panel, signature areas, fold/unfold controls

### Translations

Four language directories: `en_US`, `es_ES`, `fr_FR`, `it_IT`. Single file `infraspackplus.lang` per locale. Loaded via:
```php
$langs->load('infraspackplus@infraspackplus');
```

### Version Management

Version is read from `docs/changelog.xml` (not a VERSION file). The XML contains:
- Version entries with number and date
- Dolibarr min/max compatibility
- PHP min/max compatibility
- Per-version changelog entries (type: add, fix, chg)

The module auto-disables if Dolibarr version is below the minimum required. A version check against the publisher's website can be triggered from the changelog admin page.

### PDF Generation Flow

1. User clicks "Generate" on a document card
2. `formBuilddocOptions` hook injects options panel with all generation parameters
3. `beforePDFCreation` hook reads saved/posted parameters, initializes InfraSPlus TCPDF instance, passes data via `$this->results`
4. PDF model class (e.g., `pdf_InfraSPlus_D`) renders document using `infraspackplus.pdf.lib.php` functions
5. `afterPDFCreation` hook clears session flag
6. If CGV/CGA/CGI or file attachments are configured, they are appended to the PDF
7. If semi-auto update is enabled, PDF is regenerated on notes/extrafields/fields changes

### Parameter Persistence System

PDF generation options can be saved at three levels (priority order):
1. **Per-document** (`doc`): Saved with the specific document instance
2. **Per-user** (`user`): User-specific default preferences
3. **Per-customer** (`cust`): Customer-specific defaults
4. **Module default** (`none`): Global module configuration

Each option's persistence level is configured via `INFRASPLUS_PDF_OPTION_*` constants (e.g., `INFRASPLUS_PDF_OPTION_logo = 'user'`).

### External Module Compatibility

The module detects and integrates with:
- **ATM Subtotal** (`subtotal`): Subtitle/subtotal line handling in PDFs, recap sections
- **Ouvrage** (`ouvrage`): Composite product line handling in PDFs
- **CustomLink** (`customlink`): Sub-contractor address resolution via linked third parties
- **Milestone** (`milestone`): Compatibility patches applied on document generation
- **InfraSWorkflow** (`infrasworkflow`): Optional integration for enhanced workflows