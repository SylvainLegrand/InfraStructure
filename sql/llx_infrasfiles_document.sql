--	/************************************************
--	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
--	* 	\file		./infrasfiles/sql/llx_infrasfiles_document.sql
--	* 	\ingroup	InfraS
--	* 	\brief		Document state (last model used, last generated file) of native objects that have no model_pdf / last_main_doc columns
--	************************************************/

CREATE TABLE IF NOT EXISTS llx_infrasfiles_document (
	rowid 			integer			NOT NULL	AUTO_INCREMENT	PRIMARY KEY,
	entity			integer			NOT NULL	DEFAULT 1,
	element 		varchar(64)		NOT NULL,
	fk_element 		integer			NOT NULL,
	model_pdf		varchar(255)	DEFAULT NULL,
	last_main_doc	varchar(255)	DEFAULT NULL,
	date_creation	datetime		DEFAULT NULL,
	tms 			timestamp		NOT NULL	DEFAULT CURRENT_TIMESTAMP	ON UPDATE CURRENT_TIMESTAMP,
	fk_user_modif	integer			DEFAULT NULL
) ENGINE = innodb;
