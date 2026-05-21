# CHANGELOG UPTOSIGN FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 2.4.3 -- 2026-05-20

 fix interventions thanks to sylvain (infras)
 fix php 8+ warnings thanks to sylvain (infras)
 fix multi docs to sign thanks to sylvain (infras)
 add dol23 compatibility
 update langs & translations
 fix bugs thanks to phpstan
 update syslogs messages to make it easyer to follow
 better multidir output tests
 update mini version of php : 7.4
 new code to avoid duplicate seal process (F5/ other)

## 2.4.2 -- 20260226

- fix date_sign into database with real date

## 2.4.1 -- 20260212

- cron to keep signed files as archives into dolibarr
- code cleanup & fix thanks to tests

## 2.4.0 -- 20260212

NEW: UptoSignList synthetic tracking table (uptosignlist_docs.php)
  - Each row = one member with signing procedure status, date, and signed document download
  - Summary counters: not sent, waiting, signed, fetched, error
  - Mass action "Sync" to fetch signed files in bulk
NEW: Bidirectional FK between uptosign and uptosignlist
  - fk_uptosignlist on llx_uptosign (procedure -> list)
  - fk_uptosign on llx_uptosign_uptosignlistmembers (member -> procedure)
  - Automatically populated when sending signatures from a list
NEW: getContactsWithProcedures() method on UptoSignList (LEFT JOIN members + procedures)
NEW: Selector modules promoted from experimental to production
  - uts_contacts: contacts of thirdparties (prospects, customers, suppliers)
  - uts_members: foundation/association members with status/type/category filters
  - uts_eventattendees: attendees of organized events
  - uts_inputfile: CSV file import
  - uts_inputmanual: multi-line manual input (paste from spreadsheet with tab or semicolon separator)
ENH: All selector modules renamed with uts_ prefix to avoid collision with Dolibarr core mailing modules
ENH: addTargetsToDatabase() now handles optional keys (mobile, other, source_url) with isset() for PHP 8.2 strict mode
FIX: UptoSign object reinitialized at each iteration in mass signing loop (was reused)
FIX: Removed stale require_once to Dolibarr core advthirdparties.modules.php
QUAL: 53 new tests (22 unit + 31 integration) covering selector modules, add_to_target, getContactsWithProcedures

## 2.3.35 -- 20260210

FIX: better logs in case of exclude user on admin page
ENH: add more debug logs to diagnose race condition on setup signer list
QUAL: phpstan fixes
QUAL: tests

## 2.3.34 -- 20260109

FIX: race condition for propal/propale template like azur/cyan

## 2.3.32 -- 20251204

FIX: no object id on hook, use parameter uuid data instead

## 2.3.30 -- 20251128

NEW: support for InfraS contracts
FIX: societe tab file path
NEW: setup option on workflow UPTOSIGN_WORKFLOW_AUTO_CLOSE_ORDER

## 2.3.18 -- 20251128

FIX: societe path, use id not name
NEW: compatibility with InfraSSalariesContracts module (thanks to InfraS)
NEW: new option for contracts (merge request from InfraS)
FIX: multicompany hook
QUAL: phpstan fixes

## 2.3.16 -- 20251029

NEW: add new type of documents (contracts from InfraS)
NEW: for #47 code factoring with uptosign_handle_all_type_of_objects (one place for all dolibarr objects)
FIX: hook thanks to Sylvain (InfraS)
FIX: employee links
FIX: header tabs
ENH: better display for showOutputField
ENH: activate sql for uptosignlistmembers + indexes

## 2.3.12 -- 20251029

NEW: hook add order support
NEW: compatibility with InfraS custom module (phpstan)
FIX: fix signature logos on contracts (thanks to InfraS)
FIX: fix page stamp and signatures display
FIX: fix listing of signatures/sealings
FIX: undefined vars thanks to phpstan
FIX: undef fko
ENH: use utsbackports_getDolGlobalString
QUAL: phpcs / indent / spaces cleanup (no functional changes)

## 2.3.10 -- 20250916

FIX: file choosed on thirdparty tab
FIX: search ref on uptosign_list_of_elements_with_extrafield
FIX: try to download proof even if there is an error before
FIX: undefined array return by reset()
FIX: phone -> phone_pro mapping
FIX: first page is zero
ENH: better log level and debug messages
QUAL: phpcs + whocansign cleanup

## 2.3.8 -- 20250902

FIX: try to use fk_soc if socid is not set (bug on sign project documents)

## 2.3.7 -- 20250829

FIX: non proof download "sometimes"

## 2.3.6 -- 20250725

FIX: non proof download "sometimes"

## 2.3.4 -- 20250611

FIX: can't sign files with apostrophe in file name
FIX: handle msg object properly
FIX: stancer logs renamed to uptosign
FIX: log/debug messages cleanup
NEW: UPTOSIGN_ADD_CONTACT_POSTE_FUNCTION option

## 2.3.2 -- 20250408

NEW: activate mass sign of same document (sign same doc by multiple users)
NEW: actions on customers OR prospects
NEW: option to enable/disable sms double auth (digitalsign_disable_sms)
NEW: workflow options on module setup for auto-tasks
NEW: uptosignlist_advthirdparties selector module
NEW: hook for proof file
NEW: suffixForMassSignProcess
ENH: better log collect on hooks (get error log)
ENH: user multi sign & add downloaded file
ENH: list docs returned
ENH: start workflow implementation
QUAL: phpstan fixes (old versions compat)
QUAL: code cleanup

## 2.3.1 -- 20250404

ENH: list of sign 1.0 (status, translations)

## 2.3.0 -- 20250315

Start of UptoSignList (mass sign) feature branch

## 2.2.82 -- 20250307

partial fix supplier order sign setup (via sign tab, not templates doc)

## 2.2.80 -- 20250303

new option in module setup to add a notification mail address for each
utosign process
fix localsign order process
fix sql requests for PostgreSQL thanks to Alexandre Janniaux contribution
fix link to thirdpart card thanks to Thomas Negre (easya partner)
QUAL fix lots of phpstan alerts (undef vars and so on)
Experimental functions (disabled) for mass sign

## 2.2.78 -- 20250207

race condition on commande sign with "case 2" on sign hash
add a test on module setup against dolibarr security (hash à model)

## 2.2.76 -- 20250116

fix default stamp position with magic keywords
allow file names with non ascii characters (french accents like "signé")
default file to sign is the first one

## 2.2.72 -- 20241212

odt templates >> sign button enabled
fix order clic on sign button >> white page

## 2.2.70 -- 20241125

Better search on who can sign documents
Change main email from for mails
Better message in event log on delete objects
Better messages for end user
Better messages in debug logs
Add prefix on all css / html objects to avoid collisions with other modules
Fix default seal position from template
phpstan level 2 automatic checks

## 2.2.67 -- 20240731

FIX dolibarr 17 and formsetup backport

## 2.2.66 -- 20240720

NEW thanks to InfraS : sign on projects documents is now available
NEW local direct sign is now available, you can start sign process locally
    without sending an email to your customer
Better code to find sign people/user to use
NEW in setup please choose a default user to use on anonymous actions

## 2.2.60 -- 20240629

Fix sign on multi page document with magic keywords

## 2.2.59 -- 20240625

Remove debug message on sign tab
Fix collision / empty user object on race condition
Fix sql search on user id / authenticated

## 2.2.56 -- 20240613

FIX: default sign position

## 2.2.54 -- 20240507

FIX: PHP8 error on empty var
FIX: user sign
FIX: collision with magic sign keywords and multiple signers (_00 ...)
NEW: sign supplier order
FIX: message in case of firstname AND lastname empty

## 2.2.44

FIX: better phone filters
FIX: sql request
FIX: check cron job to avoid too much requests on uptosign servers
NEW: enable multisign position UPTOSIGN_SIGN_TO_00_HERE ...UPTOSIGN_SIGN_TO_02_HERE

## 2.2.42

FIX: error messages from remote server are now displayed in user popup
FIX: sql request on fetchChilds
FIX: race condition on file name (missing extension?)

## 2.2.38

FIX: backports for isModEnabled
FIX: dol 18 & auto create sign account

## 2.2.36

ENH: start of multicompany support
ENH: search on config data
ENH: better debug logs
FIX: sign object base can be a company
FIX: who can sign algorithm with empty object
FIX: dolibarr < 15 and mail sign url withour securekey check


## 2.2.34

FIX: default file to sign could be other than last_main_doc
FIX: reseler mode on admin
NEW: internal build system (make)
FIX: dolibarr 18 special case for online print form

## 2.2.32 -- 20240213

FIX: online sign public page push bad revision file in case of multi propal revisions
FIX: include missing for dolibarr sign (thanks to Sylvain/InfraS)


## 2.2.30

FIX: public page with live create sign people : catch two new use case
     people exists into dolibarr without phone number -> add phone number and save
     people exists into dolibarr but with an other phone number, save old one into private note and update with new one
     remove internalExternal var, fix to "external" in that case it could only be external
FIX: handle pro phone number in case of mobile number is empty
     add filter against pro / mobile phone...
FIX: relay page online sign with dropdown select sign id
FIX: some php rules and bugs detected by phpstan
FIX: who can sign include people with sign profile on thirdparty
FIX: dolibarr userwho can sign is back in list
FIX: better filters agains PDF files to upload

## 2.2.24 -- 20231125

FIX: first page of document is page number 1 (end of that bug we hope)
FIX: sometimes trigger seems to be called twice then some users are two order when a propal is signed
FIX: page number & position of user sign (not customer sign but user company sign)
FIX: add a thirdparty fetch call in case of other dolibarr modules need it in trigger process (ATM)
FIX: some times actions could be prefixed with garbage and disturb uptosign process

OTH: Lot of code cleanup

Be carefull, TAGS in PDF to make auto-pos of seal are
  UPTOSIGN_STAMP_SIGN_HERE in case of a sign process
  UPTOSIGN_STAMP_SEAL_HERE in case on single seal process


## 2.2.22 -- 20231120

FIX: page count number stats #1

## 2.2.20 -- 20231107

FIX: race condition on check if a model could be enabled or not
     (bug with external modules with models)

## 2.2.18 -- 20231030

FIX: default page seal position from config / template

## 2.2.16 -- 20231024

FIX: #41 better configuration ui/ux for documents sign models
FIX: better reseller target page
FIX: #19 display events on agenda

## 2.2.14 -- 20231018

FIX: users sign position
FIX: auto download proof file (back)
FIX: code factoring for sign list (whocansign)
FIX: race condition on auto seal/sign on first page

## 2.2.6 -- 20231011

FIX: sometime user sign field is hidden by default
NEW: online sign for FichInter

## 2.2.4 -- 20230927

NEW: tons of options for reseller on admin part

## 2.2.2 -- 20230922

FIX: many bugs on uptosign_tab page (null errors white pages)
FIX: better code to get history
NEW: sign contact could become from thirdpart, not only from doc
FIX: bas sign position in case of "clic, no label move, sign"
FIX: white page on "save config" module
FIX: message in case of no PDF file available
NEW: preview PDF on onlinesign page instead of download PDF
NEW: onlinesign page can create a sign people in live by customer himself
NEW: onlinesign page can select who would make the sign in case of multi people
NEW: config module : you can "force" uptosign as default value for all new documents
NEW: config module : you can "force" uptosign as default value for all cloned old documents
NEW: new config : every people linked to document can sign
NEW: config new contact with new thirdpard : all rihgts to sign

## 2.2.0 -- 20230915

NEW: auto position with the use of magic keywords
FIX: disable uptosign columns in propal / other lists
FIX: do not add events loop
NEW: accept CGU into module setup (for next step with reseller configuration)
NEW: display process sign history into page info (details)
NEW: user object can use uptosign (sign contracts, seal paycheck ...)
NEW: enhance multi paper size and multi users signs with magic keywords
NEW: start of admin panel for reseller (my customers & billing data)

## 2.0.74 -- 20230727

Fix do not delete propal if status was != Propal::STATUS_NOTSIGNED

## 2.0.72 -- 20230711

Fix cleanup old SQL code
Fix SQL compatible with ONLY_FULL_GROUP_BY settings
Fix Handle PDF with different pages sizes (rotate and page break with size change)

## 2.0.69 -- dev in progress

Fix exec call for pdftotext
Fix project extrafields thanks to InfraS
Fix Dolibarr 10 compatibility

## 2.0.68 -- 20230628

Fix setEventMessages calls
Fix #10: multi files linked to an object
Fix require cmailfile include
New:
 Uuid priority on document tracking
 Try pdftotext on admin settings
 Better errors codes
 Sign on projects thanks to InfraS



## 2.0.66 -- 20230616

Fix contract sign process
Fix remove timestamp suffix : dolibarr core guideline is really a bad idea
Fix download proof file too when "download signed file" is clicked
Fix css style sheet (better display on easya solutions)
Fix PDF display for sign & seal drag&drop positions
Fix filter only on .pdf files
Fix tests and display message in case of composer vendor dir empty
New add a new keyword for magic stanp position on propal

## 2.0.58 -- 20230602

Fix mobile phone number detect / convert for customer
Fix render view of PDF with landscape pages
Fix url version to display last available version on admin panel
Fix file name suffix with timestamp to be aligned with dolibarr rules
Fix selected file name on popup was sometimes not really used
Fix uuid priority on webhook
Experimental better detection for magic keywords

## 2.0.56 -- 20230506

Fix proof file suffix
Fix PDF parser segfault
Fix js number NaN
Add Todo notes

## 2.0.54 -- 20230502

Fix / cleanup sql
Add vendor stuff in package


## 2.0.52 -- 20230427

End of hide private informations (mail & phone) of sign people
Add TAGS in PDF to make auto-pos of signs & seal : UPTOSIGN_SIGN_TO_HERE UPTOSIGN_STAMP_HERE

## 2.0.48 -- 20230421

Fix a race condition on model
Fix a bug on unselect users signs in setup
Fix a race condition on UptoSignErrorErrorStampPosition

## 2.0.46 -- 20230416

Fix a bug on stamp position when mass actions is used

## 2.0.42 -- 20230321

New uptosignCore object designed for dolibarr developpers : a very easy way to integrate uptosign into your code !
Add mass action : seal tons of invoices in one clic
Choose sign process: uptosign or dolibarr native via a new extrafield on propal, contract and commande
New sign is now possible on on RIB / Mandate
Better phone number autoconvert to international format
Better error handler, and more details on messages
Group events on object : single event and one line by event
Better document title on sign/seal tab
Add option on setup: choose landing page for the end of sign process

## 2.0.38

Fix online sign link with security token if not defined

## 2.0.36

New translation in italian thanks to Mavasoft

## 2.0.34 -- 20221201

Fix a bug on dolibarr-14 public sign page

## 2.0.30

Fix events properties
Fix dolibarr 15 function getDolGlobalString not found
Fix template validate button
Better messages on disabled button
Fix order template bug
Display message in case of sending mail with signature link but no contact linked

## 2.0.28 -- 20221128

Messages are now in tooltip css mode
Auto download sealed files (via webhook)
Add new hook to prepare support for other external modules like DoliLetter
Fix listing of available templates
Remove Seal filename customization (now sealed file overwrite original file)
Better messages in case of error
Messages on disabled buttons
Fix display on oblyon theme (thanks to InfraS)
Fix old "facture" to "invoice" keyword on database

## 2.0.20 -- 20221121

Fix race condition on online sign (mail link) with dolibarr 15
Fix a french typo
Big enhance of admin configuration panel
Big enhance of templace configuration tool (graphical wizzard and display specimen)
Sign of a previously seal document is nos possible
Seal document override native dolibarr PDF
Add symbols on listing to know if a document is sealed or signed (or both)
Disable sign and seal button if operation can't be done
Add events on agenda
Move seal & sign uptosign stamp position
Automatic download proof file with signed one
Catch dolibarr PDF rebuild file to handle and remove signed files if needed

## 2.0.10 -- 20221112
Real data & config migration from version 1.x to 2.0.10

## 2.0.8
Fix migration from version 1.x
Tests & validations for dolibarr 16

## 2.0.7
Fix camelcase backup path of module
Fix module config for dolibarr 13

## 2.0.6
Fix #5: big internal bug with model_pdf linked to not premanent external table rowid
Set default title on sign tab
model_pdf is back to varchar

## 2.0.5
Fix PHP8 compatibility check
Fix backup/restore data

## 2.0.4
Fix groupby + invoice/facture race case

## 2.0.3
Add more translations on objects type
Add more filter on french mobile phone
Fix show input field for template doc name/type
Fix backup / restore data (settings)

## 2.0.2
Fix setup error/success messages

## 2.0.1

Code cleanup
SQL cleanup (add foreign keys)
Add some more translations keys

## 2.0.0

Full plugin redesign from module builder
Massive code cleanup

