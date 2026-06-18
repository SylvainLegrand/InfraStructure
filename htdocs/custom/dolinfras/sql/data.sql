--	/************************************************
--	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
--	*
--	* This program is free software: you can redistribute it and/or modify
--	* it under the terms of the GNU General Public License as published by
--	* the Free Software Foundation, either version 3 of the License, or
--	* (at your option) any later version.
--	*
--	* This program is distributed in the hope that it will be useful,
--	* but WITHOUT ANY WARRANTY; without even the implied warranty of
--	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--	* GNU General Public License for more details.
--	*
--	* You should have received a copy of the GNU General Public License
--	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
--	************************************************/

--	/************************************************
--	* 	\file		./dolinfras/sql/data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Data for table llx_const
insert ignore into llx_const (name, entity, value, type, visible, note) values ('CATEGORIE_RECURSIV_ADD',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('CHECKLASTVERSION_EXTERNALMODULE',								__ENTITY__, '0',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('CRON_DISABLE_KEY_CHANGE',										__ENTITY__, '0',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('CRON_DISABLE_TUTORIAL_CRON',									__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('CRON_WARNING_DELAY_HOURS',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('DATABASE_PWD_ENCRYPTED',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_ALLOW_ANY_CONTENT',									__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_ENABLE_DETAILS_FULL',								__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_ENABLE_NOTE_PRIVATE',								__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS - WYSIWIG for private note');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_ENABLE_NOTE_PUBLIC',									__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS - WYSIWIG for public note');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_ENABLE_SCAYT_AUTOSTARTUP',							__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_ENABLE_SCAYT_LANG',									__ENTITY__, 'fr_FR',																																						'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_ENABLE_SPECIALCHAR',									__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_ENABLE_USERSIGN',									__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FCKEDITOR_SKIN',												__ENTITY__, 'infras',																																						'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('INFRASPACKPLUS_DISABLED_CORE_CHANGE',							__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('INFRASPACKPLUS_DISABLED_MODULE_CHANGE',						__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAILING_NO_USING_PHPMAIL',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAILING_SMTP_SETUP_EMAILS_FOR_QUESTIONS',						__ENTITY__, 'technique@infras.fr',																																			'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_ACTIVATE_FILECACHE',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE',					__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_BUGTRACK_ENABLELINK',										__ENTITY__, 'https://support.infras.fr/create_ticket.php',																													'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_ENABLE_AJAX_TOOLTIP',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_FILECHECK_LOCAL_SUFFIX',									__ENTITY__, '-saasbyinfras',																																				'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_HELPCENTER_DISABLELINK',									__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_HELP_DISABLELINK',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_MAIL_ADD_INLINE_IMAGES_IF_IN_MEDIAS',						__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS - encode image en base 64 dans les mails si provient de média');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_MENU_HIDE_UNAUTHORIZED',									__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_MOTD',													__ENTITY__, '<div style="text-align:center"><span style="color:#263c5c"><span style="font-size:20px"><strong>Bonjour&nbsp;__USER_FIRSTNAME__</strong></span></span></div>',	'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_SECURITY_DISABLEFORGETPASSLINK',							__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_SHOW_LOGO',												__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_UMASK',													__ENTITY__, '0660',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_UPLOAD_DOC',												__ENTITY__, '524288',																																						'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_USE_ADVANCED_PERMS',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_USE_DOL_EVAL_NEW',										__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SEPA_USE_IDS',													__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SYSLOG_DISABLE_LOGHANDLER_SYSLOG',								__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('USER_PASSWORD_GENERATED',										__ENTITY__, 'Perso',																																						'chaine', 0, 'SaaS by InfraS');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('USER_PASSWORD_PATTERN',										__ENTITY__, '16;1;1;1;2;1',																																					'chaine', 0, 'SaaS by InfraS - min longueur - min majuscules - min chiffres - min caractères spéciaux - max répétitions - éviter les caractères ambigus');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('WITHDRAW_ENABLED_EXTENDED_LIST',								__ENTITY__, '1',																																							'chaine', 0, 'SaaS by InfraS - Activation of the advanced supplier transfer management list');

insert ignore into llx_const (name, entity, value, type, visible, note) values ('ACCOUNTANCY_COMBO_FOR_AUX',									__ENTITY__, '1',	'chaine', 1, 'Add graphic option for ACCOUNTANCY_COMBO_FOR_AUX ► (v10.0)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('BANK_ASK_PAYMENT_BANK_DURING_ORDER',							__ENTITY__, '1',	'chaine', 1, 'Ask bank account during creation of an order');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL',						__ENTITY__, '1',	'chaine', 1, 'Ask bank account during creation of a proposal');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('BANK_CAN_RECONCILIATE_CASHACCOUNT',							__ENTITY__, '1',	'chaine', 1, 'Can reconciliate cash accounts');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('CATEGORY_GRAPHSTATS_ON_PRODUCTS',								__ENTITY__, '1',	'chaine', 1, 'Show graph of products with categories and totals in Products Area screen');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('CATEGORY_GRAPHSTATS_ON_THIRDPARTIES',							__ENTITY__, '1',	'chaine', 1, 'Show graph of products with categories and totals in Thirdparties Area screen');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FACTUREFOURN_REUSE_NOTES_ON_CREATE_FROM',						__ENTITY__, '1',	'chaine', 1, 'Reuse the Public Note and Private note of the previous object when creating the supplier bill ► (V13.0)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FACTURE_REUSE_NOTES_ON_CREATE_FROM',							__ENTITY__, '1',	'chaine', 1, 'Reuse the Public Note and Private note of the previous object (Proposal,...) when creating the bill.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('FICHINTER_CLASSIFY_BILLED',									__ENTITY__, '1',	'chaine', 1, 'Allow to classify an intervention card as Billed. This add also trigger FICHINTER_CLASSIFY_BILLED into list of possible automatic event into agenda.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('INVOICE_ALLOW_EXTERNAL_DOWNLOAD',								__ENTITY__, '1',	'chaine', 1, 'When a PDF is generated, a share key is automatically set so the file can be downloaded using the share key. Use the tag __DIRECTDOWNLOAD_URL_INVOICE__ in email template to insert it. ► (v7.0+)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('INVOICE_AUTO_NEXT_MONTH_ON_LINES',								__ENTITY__, '1',	'chaine', 1, 'Increase start date and end date by one month, if an invoice line is a clone of a time based service AND if this services started on the first day of month and ended on the last day of month ► (V13.0.1)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('INVOICE_USE_DEFAULT_DOCUMENT',									__ENTITY__, '1',	'chaine', 1, 'Allow user to select a default invoice documents models according to invoice type. On invoice create page, the model is dynamically changed on invoice type selection. ► (v9.0+)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_DISABLE_FORCE_SAVEAS',									__ENTITY__, '1',	'chaine', 1, 'If your browser ask always to save downloaded files on disk (like PDF), try to add this option. File might appears directly into your browser.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES',						__ENTITY__, '1',	'chaine', 1, 'Allow to import lines into current document from linked compatible documents');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_ENABLE_LOG_TO_HTML',										__ENTITY__, '1',	'chaine', 1, 'If this option is set to 1, it is possible to see log output at end of HTML sources by adding paramater logtohtml=1 on URL. Module log must also be enabled.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_EXTRAFIELDS_ENABLE_NEW_SELECT2',							__ENTITY__, '1',	'chaine', 1, 'always use AJAX select2 for sellist (no limit)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_FEATURES_LEVEL',											__ENTITY__, '0',	'chaine', 1, 'Level of features to show: -1=stable+deprecated, 0=stable only (default), 1=stable+experimental, 2=stable+experimental+development');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_FILL_SERVICE_DATES_FROM_LAST_SERVICE_LINE',				__ENTITY__, '1',	'chaine', 1, 'On add line form add a button to fill service dates from the last service line ► (v13.0+)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_KEEP_REF_CUSTOMER_ON_CLONING',							__ENTITY__, '1',	'chaine', 1, 'Keep the Customer Reference on cloned object (Propal/Invoice)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_MAIL_ADD_INLINE_IMAGES_IF_IN_MEDIAS',						__ENTITY__, '1',	'chaine', 1, 'encode image en base 64 dans les mails si provient de média');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_MAIL_FORCE_CONTENT_TYPE_TO_HTML',							__ENTITY__, '1',	'chaine', 1, 'Force to send all email (event with text only content) as HTML formatted email.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN',							__ENTITY__, '1',	'chaine', 1, 'When creating an order, contract, invoice from another object, specific contacts of objects are set as specific contact of the new object when possible.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_SHOW_PRODUCT_ACTIVITY_TRIM',								__ENTITY__, '1',	'chaine', 1, 'Show Product and Services turnover before for all four quarters tax over recent years recent years on the product area ► (v5.0+)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_SHOW_TUNING_INFO',										__ENTITY__, '1',	'chaine', 1, 'Add tuning information into javascript console. Better when xdebug is enabled.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_USE_PROPAL_REFCLIENT_FOR_ORDER',							__ENTITY__, '1',	'chaine', 1, 'Copy customer reference from proposal to order. ► (v7.0+)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_USE_TOP_MENU_BOOKMARK_DROPDOWN',							__ENTITY__, '1',	'chaine', 1, '(v11.0+) actually only for eldy theme, this conf move bookmark to top menu and use new design too');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('MAIN_USE_TOP_MENU_QUICKADD_DROPDOWN',							__ENTITY__, '1',	'chaine', 1, 'Add a dropdown menu with shortcuts to create new objects ►(v13.0)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('ORDER_ALLOW_EXTERNAL_DOWNLOAD',								__ENTITY__, '1',	'chaine', 1, 'When a PDF is generated, a share key is automatically set so the file can be downloaded using the share key. ► (v7.0+)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('PRODUCT_ADD_FORM_ADD_TO',										__ENTITY__, '1',	'chaine', 1, 'from product card add this to a commercial document');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('PROJECT_ALLOW_COMMENT_ON_TASK',								__ENTITY__, '1',	'chaine', 1, 'Add comment feature on project task');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('PROJECT_HIDE_UNSELECTABLES',									__ENTITY__, '1',	'chaine', 1, 'Hide into select list, all project that we can not select (closed or draft)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('PROPOSAL_ALLOW_EXTERNAL_DOWNLOAD',								__ENTITY__, '1',	'chaine', 1, 'When a PDF is generated, a share key is automatically set so the file can be downloaded using the share key. ► (v7.0+)');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('RELOAD_PAGE_ON_CUSTOMER_CHANGE',								__ENTITY__, '1',	'chaine', 1, 'Add customer settings when a document is created directly');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('RELOAD_PAGE_ON_SUPPLIER_CHANGE',								__ENTITY__, '1',	'chaine', 1, 'Add supplier settings when a document is created directly');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SOCIETE_ASK_FOR_SHIPPING_METHOD',								__ENTITY__, '1',	'chaine', 1, 'Shipping method can be predefined on customer card and will be used as default on order creation');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SOCIETE_ON_SEARCH_AND_LIST_GO_ON_CUSTOMER_OR_SUPPLIER_CARD',	__ENTITY__, '1',	'chaine', 1, 'Allow to change the link of the third party to customer/supplier card instead of contact card on List.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SOCIETE_SORT_ON_TYPEENT',										__ENTITY__, '1',	'chaine', 1, 'The combo list of "type of third party" is sorted on alphabetical order instead of the field "position" that appears into dictionary instead.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SUPPLIER_ORDER_AUTOADD_USER_CONTACT',							__ENTITY__, '1',	'chaine', 1, 'Add user approving supplier order as a contact automatically.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT',				__ENTITY__, '1',	'chaine', 1, 'Can modify the buying price used for PMP calculation when making a stock reception on a supplier order.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SUPPLIER_ORDER_USE_DISPATCH_STATUS',							__ENTITY__, '1',	'chaine', 1, 'Add a status on each dispatch order line when receiving products from suppliers');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('SUPPLIER_ORDER_WITH_NOPRICEDEFINED',							__ENTITY__, '1',	'chaine', 1, 'Can enter a product even if no supplier price defined.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('THIRDPARTY_INCLUDE_PARENT_IN_LINKTO',							__ENTITY__, '1',	'chaine', 1, 'Search also for elements on parent third party when using the link to object feature.');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('THIRDPARTY_INCLUDE_PROJECT_THIRDPARY_IN_LINKTO',				__ENTITY__, '1',	'chaine', 1, 'Search also for elements on third party that own the project of the current element when using the link to object feature (if project is owned by a different thirdparty than current one).');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('THIRDPARTY_NOTCUSTOMERPROSPECT_BY_DEFAULT',					__ENTITY__, '1',	'chaine', 1, 'Do not set status "Customer/Prospect" to "on" when creating a new third party from menu "New third party"');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('THIRDPARTY_NOTSUPPLIER_BY_DEFAULT',							__ENTITY__, '1',	'chaine', 1, 'Do not set status "Supplier" to "on" when creating a new third party from menu "New third party"');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('USER_HIDE_INACTIVE_IN_COMBOBOX',								__ENTITY__, '1',	'chaine', 1, 'Disable display inactive users in combobox');
insert ignore into llx_const (name, entity, value, type, visible, note) values ('WITHDRAW_ENABLED_EXTENDED_LIST',								__ENTITY__, '1',	'chaine', 1, 'Enable extended list for withdraw feature');

ALTER TABLE llx_const ENABLE KEYS;

SET FOREIGN_KEY_CHECKS = 1;