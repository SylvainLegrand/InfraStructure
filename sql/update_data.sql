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
--	* 	\file		./infrasfiles/sql/update_data.sql
--	* 	\ingroup	InfraS
--	* 	\brief		Normalize module constants (hidden and tagged) on each activation
--	************************************************/

UPDATE llx_const AS co SET co.visible = '0'				WHERE co.name LIKE 'INFRASFILES\_%';
UPDATE llx_const AS co SET co.note = 'InfraSFiles module'	WHERE co.name LIKE 'INFRASFILES\_%';
