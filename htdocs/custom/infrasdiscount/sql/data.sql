--	/************************************************
--	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
--	* Copyright (C) 2016-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
--	* 	\file		./infrasdiscount/sql/data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Data for table llx_const
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_DEFAULT_REM_VALUE',		'__ENTITY__', '10',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_DESC_FREETEXT',			'__ENTITY__', '',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_FREE_LINE',				'__ENTITY__', '',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_LABEL_INVOICE',			'__ENTITY__', 'InfraSDiscountlabelDefault',	'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_LABEL_ORDER',				'__ENTITY__', 'InfraSDiscountlabelDefault',	'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_LABEL_PROPAL',				'__ENTITY__', 'InfraSDiscountlabelDefault',	'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_NUMBER_DISCOUNT_ALLOW',	'__ENTITY__', '',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_ON_INVOICE',				'__ENTITY__', '1',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_ON_ORDER',					'__ENTITY__', '1',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_ON_PROPALE',				'__ENTITY__', '1',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_PONDERATION',				'__ENTITY__', '',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_PRODUCT_AFFILIATE',		'__ENTITY__', '',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_PRODUCT_LINK_TO_DISCOUNT',	'__ENTITY__', '0',							'chaine', '0', 'InfraSDiscount module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASDISCOUNT_SERVICE_LINK_TO_DISCOUNT',	'__ENTITY__', '0',							'chaine', '0', 'InfraSDiscount module');

SET FOREIGN_KEY_CHECKS = 1;