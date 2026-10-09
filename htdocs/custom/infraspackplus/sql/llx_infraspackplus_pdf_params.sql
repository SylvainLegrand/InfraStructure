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
--	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
--	* GNU General Public License for more details.
--	*
--	* You should have received a copy of the GNU General Public License
--	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
--	************************************************/

--	/************************************************
--	*	\file		./infraspackplus/sql/llx_infraspackplus_pdf_params.sql
--	*	\ingroup	InfraS
--	*	\brief		Create SQL table for module InfraS
--	************************************************/

-- Réglages PDF enregistrés par document, par client ou par utilisateur (depuis 21.11.0),
-- anciennement constantes INFRASPLUS_PDF_PARAMS_<element>_DOC|CUST|USER_<id> chargées à chaque requête.
CREATE TABLE IF NOT EXISTS llx_infraspackplus_pdf_params
(
	rowid			integer			AUTO_INCREMENT PRIMARY KEY,
	entity			integer			NOT NULL	DEFAULT 1,
	element			varchar(64)		NOT NULL,																-- $object->element (propal, commande, facture, ...)
	scope			varchar(8)		NOT NULL,																-- doc, cust or user
	fk_object		integer			NOT NULL	DEFAULT 0,													-- document, thirdparty or user id according to scope
	params			text,																						-- options as query string (key=value&key=value)
	-- last modification date. Pas de commentaire en fin de la dernière colonne : run_sql() ne retire un commentaire de fin de
	-- ligne qu'après une virgule, une parenthèse ou certaines lettres, sinon il avale la fin de l'instruction (erreur de syntaxe).
	tms				timestamp					DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;
