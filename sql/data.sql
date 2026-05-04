--	/************************************************
--	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
--	*
--	* This program is free software: you can redistribute it and/or modify
--	* it under the terms of the GNU General Public License as published by
--	* the Free Software Foundation, either version 3 of the License, or
--	* (at your option) any later version.
--	*
--	* This program is distributed in the hope that it will be useful,
--	* but WITHOUT ANY WARRANTY; without even the implied warranty of
--	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
--	* GNU General Public License for more details.
--	*
--	* You should have received a copy of the GNU General Public License
--	* along with this program.If not, see <http://www.gnu.org/licenses/>.
--	************************************************/

--	/************************************************
--	* 	\file		./infrastructure/sql/data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module Infrastructure
--	************************************************/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Data for table llx_const
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ADD_LINE_UNDER_TITLE_AT_END_BLOCK',					'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ALLOW_ADD_BLOCK',										'__ENTITY__', '1',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ALLOW_ADD_LINE_UNDER_TITLE',							'__ENTITY__', '1',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ALLOW_EDIT_BLOCK',										'__ENTITY__', '1',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE',							'__ENTITY__', '1',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ALLOW_DUPLICATE_BLOCK',								'__ENTITY__', '1',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ALLOW_DUPLICATE_LINE',									'__ENTITY__', '1',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK',									'__ENTITY__', '1',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_AUTO_ADD_INFRASTRUCTURE_ON_ADDING_NEW_TITLE',			'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_BLOC_FOLD_MODE',										'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_COMMANDE_ADD_RECAP',									'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL',			'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS',	'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_DISABLE_SUMMARY',										'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_DISPLAY_MARGIN_ON_TOTAL',					            '__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_HIDE_DOCUMENT_TOTAL',									'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_HIDE_FOLDERS_BY_DEFAULT',								'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_HIDE_OPTIONS_BREAK_PAGE_BEFORE',						'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_HIDE_OPTIONS_BUILD_DOC',								'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_HIDE_OPTIONS_TITLE',									'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_HIDE_PRICE_DEFAULT_CHECKED',							'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_IF_HIDE_PRICES_SHOW_QTY',								'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_INVOICE_ADD_RECAP',									'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_KEEP_RECAP_FILE',										'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_LIMIT_TVA_ON_CONDENSED_BLOCS',							'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET',						'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET',						'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET',						'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS',							'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_NO_TITLE_SHOW_ON_EXPED_GENERATION',					'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_NONCOMPRIS_UPDATE_PA_HT',								'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ONE_LINE_IF_HIDE_INNERLINES',							'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_ONLY_HIDE_SUBPRODUCTS_PRICES',							'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_PDF_TITLE_BACKGROUND_COLOR',							'__ENTITY__', '6b2c6b',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_PDF_TITLE_COLOR',										'__ENTITY__', '000000',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_COLOR',						   	'__ENTITY__', '999999',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_PDF_TOTAL_COLOR',										'__ENTITY__', '000000',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_PROPAL_ADD_RECAP',										'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_REPLACE_WITH_VAT_IF_HIDE_INNERLINES',					'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_SHOW_TVA_ON_INFRASTRUCTURE_LINES_ON_ELEMENTS',			'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_SHOW_QTY_ON_TITLES',									'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_SHIPPABLE_ORDER',										'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TOTAL_STYLE',									        '__ENTITY__', 'B',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TOTAL_BACKGROUND_COLOR',						        '__ENTITY__', '999999',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TOTAL_COLOR',									        '__ENTITY__', '000000',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TEXT_FOR_TITLE_ORDERS_TO_INVOICE',						'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TEXT_LINE_STYLE',										'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC',								'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE',		'__ENTITY__', '10',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TITLE_BACKGROUND_COLOR',								'__ENTITY__', '6b2c6b',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TITLE_COLOR',											'__ENTITY__', '000000',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TITLE_COLOR_BLOC',										'__ENTITY__', 'be3535',	'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TITLE_SIZE',											'__ENTITY__', '',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_TITLE_STYLE',											'__ENTITY__', 'BU',		'chaine', '0', 'Infrastructure module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTRUCTURE_USE_NUMEROTATION',										'__ENTITY__', '0',		'chaine', '0', 'Infrastructure module');

SET FOREIGN_KEY_CHECKS = 1;
