-- ========================================================================
-- Copyright (C) 2021-2026 		Open-DSI      <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ========================================================================

-- V7.0.30
UPDATE llx_const set value = __ENCRYPT('https://annuaire-entreprises.data.gouv.fr/entreprise/')__ WHERE __DECRYPT('value')__ = 'https://annuaire-entreprises.data.gouv.fr/';

-- V10.3.0
-- Custom translate FR
INSERT IGNORE INTO llx_overwrite_trans (entity, lang, transkey, transvalue) VALUES
(__ENTITY__, 'fr_FR', 'ProfId6FR', 'Id. prof. 6 (numéro RNA)'),
(__ENTITY__, 'fr_FR', 'ProfId6ShortFR', 'RNA');

-- Custom translate FR
UPDATE llx_const set name = __ENCRYPT('ADVANCEDICTIONARIES_DICTIONARY_SIRENECOUNTRY_VERSION')__ WHERE __DECRYPT('name')__ = 'ADVANCEDICTIONARIES_DICTIONARY_SIRENE_VERSION';
ALTER TABLE llx_c_codenaf MODIFY rowid INTEGER NOT NULL AUTO_INCREMENT;

-- V10.3.4
ALTER TABLE llx_c_sirene_staff ADD COLUMN rowid INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT FIRST;
