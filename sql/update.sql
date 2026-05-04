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
--	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
--	* GNU General Public License for more details.
--	*
--	* You should have received a copy of the GNU General Public License
--	* along with this program. If not, see <http://www.gnu.org/licenses/>.
--	************************************************/

--	/************************************************
--	* 	\file		./infrastructure/sql/update.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL update script for module Infrastructure (migration tasks)
--	************************************************/

-- 18.2.0 : rename constant INFRASTRUCTURE_DISPLAY_MARGIN_ON_INFRASTRUCTURES → INFRASTRUCTURE_DISPLAY_MARGIN_ON_TOTAL
-- (le nouveau nom est plus cohérent avec la sémantique métier : la cellule est rendue sur les sous-totaux et la méthode est TInfrastructure::isTotal)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_DISPLAY_MARGIN_ON_INFRASTRUCTURES' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_DISPLAY_MARGIN_ON_TOTAL') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_DISPLAY_MARGIN_ON_TOTAL' WHERE name = 'INFRASTRUCTURE_DISPLAY_MARGIN_ON_INFRASTRUCTURES';

-- 18.2.0 : rename constants INFRASTRUCTURE_INFRASTRUCTURE_* → INFRASTRUCTURE_TOTAL_*
-- (alignement sémantique avec le renommage TInfrastructure::isInfrastructure() → TInfrastructure::isTotal() ; ces constantes contrôlent le rendu des lignes sous-total)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_INFRASTRUCTURE_STYLE' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_TOTAL_STYLE') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_TOTAL_STYLE' WHERE name = 'INFRASTRUCTURE_INFRASTRUCTURE_STYLE';
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_TOTAL_BACKGROUND_COLOR') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_TOTAL_BACKGROUND_COLOR' WHERE name = 'INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR';
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_INFRASTRUCTURE_COLOR' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_TOTAL_COLOR') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_TOTAL_COLOR' WHERE name = 'INFRASTRUCTURE_INFRASTRUCTURE_COLOR';
