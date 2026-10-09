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
--	* 	\file		./infrasfiles/sql/llx_infrasfiles_document.key.sql
--	* 	\ingroup	InfraS
--	* 	\brief		Keys of table llx_infrasfiles_document
--	************************************************/

ALTER TABLE llx_infrasfiles_document ADD UNIQUE INDEX uk_infrasfiles_document (entity, element, fk_element);
ALTER TABLE llx_infrasfiles_document ADD INDEX idx_infrasfiles_document_element (element, fk_element);
