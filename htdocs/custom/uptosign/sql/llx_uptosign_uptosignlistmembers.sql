-- Copyright (C) 2025 Eric Seigne <eric.seigne@cap-rel.fr>
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


CREATE TABLE llx_uptosign_uptosignlistmembers(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_uptosignlist integer NOT NULL, -- link to list id
	firstname varchar(255), -- prenom
	lastname varchar(255), -- nom de famille
	email varchar(255), -- adresse mail
	mobile varchar(255), -- num de tel mobile
	source_type varchar(255), -- contact,societe etc.
	source_id integer, -- rowid de l'objet source_type lie
	other varchar(255) NULL,
	source_url varchar(255),
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	status integer NOT NULL,
	fk_uptosign integer
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
