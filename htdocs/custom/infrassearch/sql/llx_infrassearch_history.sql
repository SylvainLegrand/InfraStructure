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
--	* 	\file		./infrassearch/sql/infrassearch_history.sql
--	* 	\ingroup	InfraS
--	* 	\brief		Create SQL table for module InfraS
--	************************************************/

CREATE TABLE IF NOT EXISTS llx_infrassearch_history (
	rowid 			integer			NOT NULL	AUTO_INCREMENT	PRIMARY KEY,
	entity			integer			NOT NULL	DEFAULT 0,
	element 		varchar(64)		NOT NULL	DEFAULT "",
	fk_element 		integer			NOT NULL,
	fk_user			integer			NOT NULL	DEFAULT 0,
	tms 			timestamp		NOT NULL	DEFAULT CURRENT_TIMESTAMP	ON UPDATE CURRENT_TIMESTAMP
) ENGINE = innodb;