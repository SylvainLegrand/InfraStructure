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

-- 18.3.1 : rename constant INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES → INFRASTRUCTURE_PDF_TITLE_STYLE_IF_HIDDEN_LINES
-- (alignement avec la convention de nommage anglaise et le préfixe PDF_TITLE_STYLE_* utilisé dans la section « Paramètres d'impression PDF »)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_PDF_TITLE_STYLE_IF_HIDDEN_LINES') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_PDF_TITLE_STYLE_IF_HIDDEN_LINES' WHERE name = 'INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES';

-- 18.3.1 : rename constant INFRASTRUCTURE_TITLE_SIZE → INFRASTRUCTURE_PDF_TITLE_SIZE
-- (alignement avec le préfixe PDF_TITLE_* utilisé dans la section « Paramètres d'impression PDF » : la taille ne s'applique qu'au rendu PDF)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_TITLE_SIZE' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_PDF_TITLE_SIZE') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_PDF_TITLE_SIZE' WHERE name = 'INFRASTRUCTURE_TITLE_SIZE';

-- 18.3.1 : rename constant INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE_PDF → INFRASTRUCTURE_PDF_TITLE_AND_TOTAL_BRIGHTNESS_PERCENTAGE
-- (alignement avec le préfixe PDF_* en tête, et remplacement du segment "INFRASTRUCTURE" interne par "TOTAL" pour cohérence avec le renommage TInfrastructure::isInfrastructure → isTotal de la 18.2.0)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE_PDF' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_PDF_TITLE_AND_TOTAL_BRIGHTNESS_PERCENTAGE') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_PDF_TITLE_AND_TOTAL_BRIGHTNESS_PERCENTAGE' WHERE name = 'INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE_PDF';

-- 18.3.1 : rename constants INFRASTRUCTURE_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET / _POS_Y_OFFSET → INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_*
-- (ces 2 offsets ne s'appliquent qu'au rendu PDF du fond coloré des titres, alignement avec le préfixe PDF_TITLE_* utilisé dans la section « Paramètres d'impression PDF »)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET' WHERE name = 'INFRASTRUCTURE_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET';
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_TITLE_BACKGROUND_CELL_POS_Y_OFFSET' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_POS_Y_OFFSET') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_POS_Y_OFFSET' WHERE name = 'INFRASTRUCTURE_TITLE_BACKGROUND_CELL_POS_Y_OFFSET';

-- 18.3.1 : rename constants INFRASTRUCTURE_BACKGROUND_CELL_HEIGHT_OFFSET / _POS_Y_OFFSET → INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_*
-- (ces 2 offsets ne s'appliquent qu'au rendu PDF du fond coloré des sous-totaux, alignement avec le préfixe PDF_TOTAL_* utilisé dans la section « Paramètres d'impression PDF »)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_BACKGROUND_CELL_HEIGHT_OFFSET' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_HEIGHT_OFFSET') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_HEIGHT_OFFSET' WHERE name = 'INFRASTRUCTURE_BACKGROUND_CELL_HEIGHT_OFFSET';
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_BACKGROUND_CELL_POS_Y_OFFSET' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_POS_Y_OFFSET') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_POS_Y_OFFSET' WHERE name = 'INFRASTRUCTURE_BACKGROUND_CELL_POS_Y_OFFSET';

-- 18.3.1 : rename constant INFRASTRUCTURE_SHOW_TVA_ON_INFRASTRUCTURE_LINES_ON_ELEMENTS → INFRASTRUCTURE_SHOW_TVA_ON_TOTAL_LINES
-- (alignement avec le renommage TInfrastructure::isInfrastructure() → TInfrastructure::isTotal() et nom plus court/parlant)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_SHOW_TVA_ON_INFRASTRUCTURE_LINES_ON_ELEMENTS' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_SHOW_TVA_ON_TOTAL_LINES') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_SHOW_TVA_ON_TOTAL_LINES' WHERE name = 'INFRASTRUCTURE_SHOW_TVA_ON_INFRASTRUCTURE_LINES_ON_ELEMENTS';

-- 18.3.1 : remove obsolete constants INFRASTRUCTURE_IF_HIDE_PRICES_SHOW_QTY (ancien nom) et INFRASTRUCTURE_PDF_SHOW_QTY_IF_HIDE_DETAILS (nouveau nom temporairement introduit en 18.3.1, finalement supprimé)
-- Cette option est devenue inutile : la logique d'affichage de la quantité quand le détail des sous-blocs est masqué est maintenant gérée nativement par le module sans nécessiter de switch.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_IF_HIDE_PRICES_SHOW_QTY';
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_PDF_SHOW_QTY_IF_HIDE_DETAILS';

-- 18.4.0 : rename constant INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL → INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_TOTAL_LABEL
-- (alignement avec le renommage TInfrastructure::isInfrastructure() → TInfrastructure::isTotal() de 18.2.0)
-- Préserve la valeur en cas d'installation existante. Si la nouvelle constante existe déjà, supprime simplement l'ancienne.
DELETE FROM llx_const WHERE name = 'INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL' AND EXISTS (SELECT 1 FROM (SELECT name FROM llx_const WHERE name = 'INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_TOTAL_LABEL') AS t);
UPDATE llx_const SET name = 'INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_TOTAL_LABEL' WHERE name = 'INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL';

-- 18.4.2 : correction du fieldcomputed='1' parasite injecté à tort par d'anciens appels à infrastructure_addExtraField()
-- (paramètres mal positionnés : le '1' tombait sur $computed au lieu de $help/$list).
-- Conséquence avant correction : Dolibarr passait par commonobject::fetch_optionals() → dol_eval('1') qui retourne 1,
-- ce qui forçait $line->array_options['options_<name>'] à 1 à chaque chargement, indépendamment de la valeur réelle en base
-- (NULL ou 0). Pour les flags print_as_list / print_condensed / infrastructure_show_qty / hideblock portés par les titres,
-- cela cochait les cases par défaut et appliquait les modes "liste" ET "condensé" simultanément au rendu PDF.
-- Nettoie aussi les pollutions cosmétiques sur help/perms causées par les mêmes décalages de paramètres.
UPDATE llx_extrafields SET fieldcomputed = NULL WHERE fieldcomputed = '1' AND name IN ('infrastructure_show_qty', 'hideblock', 'print_as_list', 'print_condensed');
UPDATE llx_extrafields SET help = NULL WHERE help = '1' AND name IN ('show_total_ht', 'show_reduc', 'hideblock');
UPDATE llx_extrafields SET perms = NULL WHERE perms = '1' AND name IN ('hideblock', 'show_table_header_before');