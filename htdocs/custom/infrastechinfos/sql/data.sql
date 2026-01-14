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
--	* 	\file		./infrastechinfos/sql/data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Data for table llx_const
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTECHINFOS_DURATION_OF_WORKWEEK',	'__ENTITY__', '5',	'chaine', '0', 'InfraSTechInfos module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTECHINFOS_TOTAL_TIME_IN_DAYS',		'__ENTITY__', '0',	'chaine', '0', 'InfraSTechInfos module');
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES ('INFRASTECHINFOS_ONLY_TOTAL_TIME',			'__ENTITY__', '0',	'chaine', '0', 'InfraSTechInfos module');

SET FOREIGN_KEY_CHECKS = 1;