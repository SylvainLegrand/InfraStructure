--	/************************************************
--	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
--	* 	\file		../infrasproject/sql/data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraSProject
--	************************************************/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Data for table llx_const
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_ADD_SUPPLIER_INVOICE_IN_MARGIN_PROV',	'__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_CREATE_PROJECT_FROM_SIGNED_PROPAL',	    '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_DEFAULT_WAREHOUSE',					    '__ENTITY__', '',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV',		'__ENTITY__', '',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV',	        '__ENTITY__', '',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_FIRST_MARK_RATE_TO_BE_APPLIED',	        '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_HIDE_CUSTOMER_INVOICE_LIST',	        '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_HIDE_CUSTOMER_ORDERS_LIST',	            '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_HIDE_EMPTY_LIST',                       '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_HIDE_PROJECT_TASK_LIST',	            '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_HIDE_SHIPMENT_LIST',	                '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_HIDE_SUPPLIER_PROPOSAL_LIST',	        '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_INVCODEPREFIX',						    '__ENTITY__', 'CONSO',	'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_LINK_TO_USER',						    '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_PS_ACTIVE_PROJET_ELEMENT',			    '__ENTITY__', '1',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_SEARCHMODE',						    '__ENTITY__', '1',		'chaine', '0', 'InfraSProject module - 0 => par label; 1 => par Inventory Code; 2 => Mixte');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_SECOND_MARK_RATE_TO_BE_APPLIED',		'__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_SHOW_INVOICE_SUPPLIER_LIST',	        '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_SHOW_LAST_EXCHANGE',				    '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module - 0 => par label; 1 => par Inventory Code; 2 => Mixte');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_SHOW_MARGIN_PROV',					    '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module - 0 => par label; 1 => par Inventory Code; 2 => Mixte');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_SHOW_MARGIN_PROV_FIRST',			    '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module - 0 => par label; 1 => par Inventory Code; 2 => Mixte');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_SHOW_NEXT_ACTION',					    '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module - 0 => par label; 1 => par Inventory Code; 2 => Mixte');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_STOCK_PROD_CAT',					    '__ENTITY__', '',		'chaine', '0', 'InfraSProject module - Product categories to use for project consumption');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_THIRD_MARK_RATE_TO_BE_APPLIED',		    '__ENTITY__', '0',		'chaine', '0', 'InfraSProject module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASPROJECT_TYPE_FEES_NOT_INCLUDED_IN_MARGIN',	    '__ENTITY__', '',		'chaine', '0', 'InfraSProject module - Product categories to use for project consumption');

SET FOREIGN_KEY_CHECKS = 1;