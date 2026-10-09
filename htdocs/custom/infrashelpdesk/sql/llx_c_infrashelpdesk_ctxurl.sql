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
--	* 	\file		./infrashelpdesk/sql/llx_c_infrashelpdesk_ctxurl.sql
--	* 	\ingroup	InfraS
--	* 	\brief		Dictionary : Dolibarr page -> hook context -> hook functions -> wiki URLs (url_page : sub-chapter, url_wiki : chapter)
--	************************************************/

CREATE TABLE IF NOT EXISTS llx_c_infrashelpdesk_ctxurl (
    rowid       integer AUTO_INCREMENT PRIMARY KEY,
    page        varchar(128) NOT NULL,
    context     varchar(128) NOT NULL,
    hooks       text,
    url_page    varchar(255),
    url_wiki    varchar(255),
    active      tinyint DEFAULT 1 NOT NULL,
    entity      integer DEFAULT 1 NOT NULL
)ENGINE=innodb;
