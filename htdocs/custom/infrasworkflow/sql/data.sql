--	/************************************************
--	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>		InfraS - <https://www.infras.fr>
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
--	* 	\file		./infrasworkflow/sql/data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CLONE_EXTRAFIELDS',							'__ENTITY__', '1',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_COLOR_TO_IDENTIFY',					        '__ENTITY__', 'c3000f',	'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTRACT_EMAIL_PROV',							'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTRACT_PRODUCT_PARENT_CATEGORY',				'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE',				'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_EXPORT_FIELDS',	'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_FIELDS',			'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_INTRA_FIELDS',	'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_EXPORT_FIELDS',  '__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_FIELDS',			'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_INTRA_FIELDS',	'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_CODE_COMPTA_FIELDS',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_COND_REGLEMENT_FIELDS',				'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_CONTACT_FIELDS',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_COUNTRY_FIELDS',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_COUNTRY_ID_FIELDS',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_CLIENT_CODE_FIELDS',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_CUSTOM_CODE_FIELDS',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_DATE_SIGN_FIELDS',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_DEFAULT_WAREHOUSE_FIELDS',				'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_DESIRED_STOCK_FIELDS',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_PARENT_FIELDS',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_MAIL_FIELDS',							'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_MODE_REGLEMENT_FIELDS',				'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_SIRET_FIELDS',							'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_TVA_ASSUJ_FIELDS',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_TVA_FIELDS',							'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CONTROL_WEIGHT_FIELDS',						'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CREATE_FIRST_AUTO_DEPOSIT',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CREATE_ORDER_SUPPLIER',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_CREDIT_NOTE_TRANSFER_NOTES',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_DISABLE_PROSPECTSCUSTOMERS',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_DISPLAY_ZONE_COLUMN',							'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_DISPLAY_SORTED_EMPTY_STOCK',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_DOCUMENTS_DRAGDROP',							'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_DOCUMENTS_DRAGDROP_PRODUCT_NO_MASK',			'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_EXF_DEPOSIT',									'__ENTITY__', 'acpt1',	'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT',			'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_EXF_LIST_TYPE',								'__ENTITY__', '1',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_EXF_SECOND_DEPOSIT',							'__ENTITY__', 'acpt2',	'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_EXTRAFIELDS_TRASHMODE',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_HIDE_ITEMS_TAGGED_OBSOLETE',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_IDENTIFY_ORDER_LINES_NO_SHIPPABLE',			'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY',				'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_INVOICE_REGENARATION_ON_CLASSIFY_PAID',		'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_INVOICE_VALIDATION',							'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_INVOICES_CLASSIFY_BILLED_PROPALS',				'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_LINK_DEPOSITS_TO_FINAL_INVOICE',				'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_MASS_CREATEBILLS_COPY_NOTES',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_MASS_CREATEBILLS_DEDUP_REF',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_MASS_CREATEBILLS_SET_AUTHOR',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_MEDIAS_BROWSER_ALL_USERS',					    '__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_MERGE_CLEAN_DUPLICATE_SUPPLIER_PRICES',		'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_ORDER_SHOW_THIRDPARTY_TYPE',		            '__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_PRODUCT_VALIDATION_CONTROL',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_PS_ACTIVE_CONTRAT_CARD',						'__ENTITY__', '1',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_PS_ACTIVE_PRODUCT_INVENTORY_INVENTORY',		'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_SUPPLIER_INVOICE_MASS_PAID',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_SUPPLIER_ORDER_RECEIVED_ON_BILLED',			'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_TEST_EXF_V20PLUS',								'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_TRANSFER_FREETEXT',						    '__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_TRANSFER_PUBLIC_NOTES',						'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_USE_DOCUMENT_MODEL_INFRASPLUS_FR',				'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASWORKFLOW_VALIDATE_FIRST_AUTO_DEPOSIT',					'__ENTITY__', '',		'chaine', '0', 'InfraSWorkflow module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('CHANGE_TIERS_CONTRACT_FROM_PROPAL_COMMANDE',					'__ENTITY__', '0',		'chaine', '0', 'InfraSWorkflow module');