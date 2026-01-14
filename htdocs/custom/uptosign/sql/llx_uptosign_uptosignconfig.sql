-- Copyright 2022-2023 ---Eric Seigne <eric.seigne@cap-rel.fr>---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_uptosign_uptosignconfig(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	label varchar(255) NOT NULL, 
	sign_or_seal varchar(8) NOT NULL, 
	entity integer DEFAULT 1 NOT NULL,
	sign_coordinate varchar(64), 
	page_sign integer NULL, 
	seal_coordinate varchar(64) NOT NULL, 
	page_seal integer DEFAULT 1 NOT NULL, 
	model_pdf varchar(128) NOT NULL, 
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	status integer DEFAULT 1 NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
