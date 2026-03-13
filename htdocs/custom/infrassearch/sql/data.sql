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
--	* 	\file		./infrassearch/sql/data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Data for table llx_const
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_BREADCRUMB',			'__ENTITY__', '1',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_NB_BREADCRUMB',		'__ENTITY__', '10',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_NB_CAR',				'__ENTITY__', '3',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_NB_SEC',				'__ENTITY__', '500',	'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_NB_ROWS',			'__ENTITY__', '5',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_ONLY_IN_ENTITY',		'__ENTITY__', '1',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_ON_TOP_MENU',		'__ENTITY__', '1',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_ORDER',				'__ENTITY__', '1',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_REPLACE_STD',		'__ENTITY__', '0',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_SHOW_FIND_FIELD',	'__ENTITY__', '0',		'chaine', '0', 'InfraSSearch module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASSEARCH_SORT',				'__ENTITY__', 'DESC',	'chaine', '0', 'InfraSSearch module');

ALTER TABLE llx_const ENABLE KEYS;

SET FOREIGN_KEY_CHECKS = 1;