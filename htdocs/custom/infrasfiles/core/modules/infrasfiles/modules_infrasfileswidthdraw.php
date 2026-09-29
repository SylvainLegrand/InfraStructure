<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrasfiles/core/modules/infrasfiles/modules_infrasfileswidthdraw.php
	* 	\ingroup	InfraS
	* 	\brief		Parent class of the PDF models for direct debit / credit transfer orders (element 'widthdraw')
	*				Loaded by FormFile::showdocuments() with modulepart 'infrasfiles:infrasfileswidthdraw'
	************************************************/

	// Libraries ************************************
	dol_include_once('/infrasfiles/core/modules/infrasfiles/modules_infrasfiles.php');

	/************************************************
	* Class ModelePDFInfrasfileswidthdraw
	************************************************/
	abstract class ModelePDFInfrasfileswidthdraw extends ModelePDFInfrasFiles
	{
		public $infrasfiles_element = 'widthdraw';

		/**
		*	Return list of active generation models
		*
		*	@param		DoliDB		$db					Database handler
		*	@param		int			$maxfilenamelength	Max length of value to show
		*	@return		array							List of templates
		**/
		public static function liste_modeles($db, $maxfilenamelength = 0)
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			return getListOfModels($db, 'infrasfileswidthdraw', $maxfilenamelength);
		}
	}
