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
--	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--	* GNU General Public License for more details.
--	*
--	* You should have received a copy of the GNU General Public License
--	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
--	************************************************/

--	/************************************************
--	* 	\file		./subtotal/sql/data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module SubTotal
--	************************************************/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Data for table llx_const
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK',			'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ALLOW_ADD_BLOCK',							'__ENTITY__', '1',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE',					'__ENTITY__', '1',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ALLOW_EDIT_BLOCK',							'__ENTITY__', '1',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ALLOW_EXTRAFIELDS_ON_TITLE',					'__ENTITY__', '1',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ALLOW_DUPLICATE_BLOCK',						'__ENTITY__', '1',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ALLOW_DUPLICATE_LINE',						'__ENTITY__', '1',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ALLOW_REMOVE_BLOCK',							'__ENTITY__', '1',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE',		'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_BLOC_FOLD_MODE',								'__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_COMMANDE_ADD_RECAP',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_CONCAT_TITLE_LABEL_IN_SUBTOTAL_LABEL',		'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS','__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_DISABLE_SUMMARY',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_DISPLAY_MARGIN_ON_SUBTOTALS',				'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_HIDE_DOCUMENT_TOTAL',						'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_HIDE_FOLDERS_BY_DEFAULT',					'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_HIDE_OPTIONS_BREAK_PAGE_BEFORE',				'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_HIDE_OPTIONS_BUILD_DOC',						'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_HIDE_OPTIONS_TITLE',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_HIDE_PRICE_DEFAULT_CHECKED',					'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY',					'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_INVOICE_ADD_RECAP',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_KEEP_RECAP_FILE',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_LIMIT_TVA_ON_CONDENSED_BLOCS',				'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET',			'__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET',				'__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET',				'__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS',					'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_NO_TITLE_SHOW_ON_EXPED_GENERATION',			'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_NONCOMPRIS_UPDATE_PA_HT',					'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ONE_LINE_IF_HIDE_INNERLINES',				'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_ONLY_HIDE_SUBPRODUCTS_PRICES',				'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_PROPAL_ADD_RECAP',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_REPLACE_WITH_VAT_IF_HIDE_INNERLINES',		'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_SHOW_TVA_ON_SUBTOTAL_LINES_ON_ELEMENTS',		'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_SHOW_QTY_ON_TITLES',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_SHIPPABLE_ORDER',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_SUBTOTAL_STYLE',								'__ENTITY__', 'B',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_SUBTOTAL_BACKGROUND_COLOR',					'__ENTITY__', '999999',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_TEXT_FOR_TITLE_ORDERS_TO_INVOICE',			'__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_TEXT_LINE_STYLE',							'__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_TFIELD_TO_KEEP_WITH_NC',						'__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_TITLE_AND_SUBTOTAL_BRIGHTNESS_PERCENTAGE',	'__ENTITY__', '10',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_TITLE_BACKGROUND_COLOR',						'__ENTITY__', '6b2c6b',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_TITLE_SIZE',									'__ENTITY__', '',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_TITLE_STYLE',								'__ENTITY__', 'BU',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_USE_NEW_FORMAT',								'__ENTITY__', '1',	'chaine', '0', 'SubTotal module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('SUBTOTAL_USE_NUMEROTATION',							'__ENTITY__', '0',	'chaine', '0', 'SubTotal module');

SET FOREIGN_KEY_CHECKS = 1;
