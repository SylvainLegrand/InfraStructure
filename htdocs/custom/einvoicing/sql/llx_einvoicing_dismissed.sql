-- InfraS add : fichier ajouté par InfraS, repris du module einvoicingsx (SYSAXES)
-- Copyright (C) 2026 SYSAXES
-- Copyright (C) 2026 InfraS
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

-- Invoices whose e-invoicing follow-up the user abandoned: excluded from the anomaly alerts,
-- the daily reports and the dashboard until reactivated. The platform synchronization never
-- writes to this table, unlike llx_einvoicing_extlinks.syncstatus.
CREATE TABLE llx_einvoicing_dismissed(
	rowid			integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	element_type	varchar(50) NOT NULL,		-- 'facture' or 'invoice_supplier'
	element_id		integer NOT NULL,
	comment			text,
	date_creation	datetime NOT NULL,
	fk_user_creat	integer,
	entity			integer DEFAULT 1 NOT NULL
) ENGINE=innodb;
