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
--	* 	\file		../infrasproject/sql/update.sql
--	* 	\ingroup	InfraS
--	* 	\brief		SQL data for module InfraS
--	************************************************/

-- Change for table llx_facture_fourn_det
ALTER TABLE llx_facture_fourn_det ADD COLUMN entity		INTEGER DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_facture_fourn_det ADD COLUMN fk_soc		INTEGER AFTER fk_product;
ALTER TABLE llx_facture_fourn_det ADD COLUMN fk_projet	INTEGER AFTER fk_soc;

ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_soc (fk_soc);
ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_projet (fk_projet);

ALTER TABLE llx_facture_fourn_det ADD CONSTRAINT fk_facture_fourn_det_fk_soc	FOREIGN KEY (fk_soc)	REFERENCES llx_societe (rowid);
ALTER TABLE llx_facture_fourn_det ADD CONSTRAINT fk_facture_fourn_det_fk_projet	FOREIGN KEY (fk_projet)	REFERENCES llx_projet (rowid);